# Module 04 — Inventory & Stock

**Module:** Inventory & Stock (Stock)
**Category:** Supply
**Purpose:** Tracks on-hand stock levels across warehouses, records manual stock adjustments, and drives the low-stock information shown in the admin panel and storefront.

> Inventory is stock-line driven, not a movement ledger. Each `stocks` row represents a product batch/lot in a warehouse and stores its current quantity and prices. Sales and stock adjustments mutate that quantity; the latest prices and availability are read directly from the stock line.

## 1. Overview

The inventory module covers:

- **Warehouses** — physical or virtual locations holding stock.
- **Stocks** — current on-hand stock lines (per product / warehouse / batch / lot), with buy price, sale price, VAT treatment, and status.
- **Stock adjustments** — explicit correction/damage/return records that change a stock line's quantity.
- **Low-stock information** — driven by the `low_stock_threshold` configuration.

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `warehouses` | Storage locations | name, capacity, location, contact_number, email |
| `stocks` | Current stock lines | fk_product_id, fk_warehouses_id, batch, lot, quantity, buy_price, sale_price, status (active/inactive/archive), vat_code, vat_rate, journal_entry_id, accounting_version |
| `stock_adjustments` | Adjustment history | fk_stock_id, fk_warehouse_id, batch, lot, quantity, reason (correction/damage/return), journal_entry_id |

All three tables use soft deletes (`deleted_at`) and track `created_by` / `updated_by` / `deleted_by`.

### 2.1 Stock Lines vs. Movements

`stocks.quantity` is the current on-hand quantity for that line. There is no `type` / `ref_model` / `ref_id` movement ledger. Changes are made by:

| Event | Effect on `stocks.quantity` |
|---|---|
| POS checkout (`PosController::checkout`) | Decremented per sale line |
| Storefront checkout (`StorefrontController::placeOrder`) | Decremented per sale line |
| Sale return approval (`SaleReturnController::approve`) | Incremented by the returned quantity |
| Stock adjustment (`StockAdjustmentController::store`) | `return` adds; `correction` / `damage` subtracts (floored at 0) |
| Stock create/edit | Set directly by the admin form (quantity may be edited) |

Procurement shipments currently record tracking, warehouse, status, and landed costs but do not create stock lines automatically. Stock must be added/updated through the Stock screen or the POS API.

## 3. Key Models

- **Stock** (`app/Models/Stock.php`) — `product()`, `warehouse()` (via `fk_warehouses_id`), `journalEntry()`; `scopeAvailable()` filters `quantity > 0`.
- **Warehouse** (`app/Models/Warehouse.php`) — `stocks()`.
- **StockAdjustment** (`app/Models/StockAdjustment.php`) — `stock()`, `warehouse()`, `journalEntry()`.

## 4. Stock Adjustments

`stock_adjustments.reason` accepts `correction`, `damage`, or `return`:

- `return` — adds the quantity back to the stock line.
- `correction` and `damage` — subtract the quantity (never below zero).
- Every adjustment posts to the double-entry ledger through `AccountingPoster::stockAdjustment()`.

## 5. Admin Surface (Routes)

| Route Prefix | Controller | Permissions |
|---|---|---|
| `stocks` | StockController | `stocks.view`, `stocks.save`, `stocks.edit` |
| `stock-adjustments` | StockAdjustmentController | `stocks.view`, `stocks.save` |
| `warehouses` | WarehouseController | resource CRUD (`warehouses.*` permission group; routes rely on the admin auth group) |

### 5.1 Stock Listing

- `GET /stocks` — paginated stock lines; filters by product name/SKU, status, and `fk_product_id`.
- The page exposes the configured currency symbol and links to create/edit stock lines.
- There are no per-warehouse tabs or movement-type filters; the list is a stock-line table.

### 5.2 Warehouse Management

- Full CRUD (`warehouses` resource excluding `show`) with capacity, location, contact number, and email.

## 6. API Surface (React POS)

Sanctum-protected endpoints in `routes/api.php`:

| Endpoint | Method | Purpose |
|---|---|---|
| `GET /stock` | GET | Paginated stock line list with product; search by product name |
| `POST /save-stock` | POST | Create a stock line (product, warehouse, batch/lot, quantity, buy/sale price, VAT, status) |

There is no `/warehouse` API endpoint. Stock mutations from the POS use the same `Stock` model and `ActivityLogger` path as the admin panel, and `saveStock` also posts inventory to the ledger.

## 7. Low-Stock Information

- Threshold driven by `Configuration::get('low_stock_threshold', 10)`.
- **Admin dashboard / reports** — surface products whose available stock is below the threshold.
- **Storefront** — products without an available stock line, or whose stock has run out, remain visible but are marked **Out of Stock** and cannot be added to the cart.
- No automatic notification is currently emitted when a stock line crosses the threshold.

## 8. Permissions

| Group | Permissions |
|---|---|
| Stocks | `stocks.view/save/edit/delete` |
| Warehouses | `warehouses.view/save/edit/delete` |

## 9. Related Code Locations

- Models: `laravel/app/Models/{Stock,StockAdjustment,Warehouse}.php`
- Controllers: `laravel/app/Http/Controllers/{StockController,StockAdjustmentController,WarehouseController}.php`
- API: `laravel/app/Http/Controllers/Api/ApiController.php` (`stock`, `saveStock`)
- Accounting: `laravel/app/Accounting/AccountingPoster.php` (`stockIn`, `stockAdjustment`)
- Config: `low_stock_threshold` in `configurations` table / `ConfigurationSeeder`
