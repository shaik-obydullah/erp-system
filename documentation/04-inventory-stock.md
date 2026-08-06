# Module 04 — Inventory & Stock

**Module:** Inventory & Stock (Stock)
**Category:** Supply
**Purpose:** Tracks stock levels across multiple warehouses, records every stock movement (in/out/adjustment), and drives the low-stock alerts that feed both the admin panel and the storefront.

> Inventory is transaction-driven: every change is a `stocks` row recording direction, quantity, and reference. Balances are never guessed — they are computed from the movement ledger, so reports always reconcile with the log.

## 1. Overview

The inventory module covers:

- **Warehouses** — physical or virtual locations holding stock.
- **Stocks** — the movement ledger: purchases, sales, transfers, adjustments.
- **Inventory** — snapshots of current quantities per warehouse/product.
- **Low-stock alerts** — driven by the `stock_warning_level` configuration.

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `warehouses` | Storage locations | name, description, contact_person_name, contact_person_phone, contact_person_email, address, status |
| `stocks` | Stock movement ledger | fk_product_id, fk_warehouse_id, fk_supplier_id (nullable), quantity, type (IN/OUT/adjust), description, ref_model, ref_id, reference (tracking no.), fk_admin_id |
| `inventories` | Current stock snapshot per warehouse | fk_product_id, fk_warehouse_id, quantity, status (active/inactive/archive), closed_date |

### 2.1 Stocks Reference (Polymorphic)

The `ref_model` / `ref_id` columns link a stock movement to its originating business document (a purchase, sale, adjustment, etc.), enabling drill-down from the stock ledger to the source transaction.

## 3. Key Models

- **Stock** (`app/Models/Stock.php`) — `product()`, `warehouse()`, `supplier()`, and polymorphic `reference()`. `latestProducts` scope returns the newest movements grouped by product.
- **Inventory** (`app/Models/Inventory.php`) — `product()`, `warehouse()`, `purchaseDetails()`, `saleDetails()`, `stockMovements()`.
- **Warehouse** (`app/Models/Warehouse.php`) — `stocks()`, `inventories()`, `purchaseDetails()`, `saleDetails()`.

## 4. Movement Types (stocks.type)

| Type | Meaning | Example |
|---|---|---|
| `IN` | Stock received / added | Purchase receipt, initial stock, return-in |
| `OUT` | Stock dispatched / consumed | Sales dispatch, transfer-out, return-out |
| `adjust` | Manual correction | Stock-count reconciliation |

Every movement is tagged with the acting admin (`fk_admin_id`) and an optional `reference` (tracking number) for reconciliation.

## 5. Admin Surface (Routes)

| Route Prefix | Controller | Permissions |
|---|---|---|
| `stocks` | StockController | stocks.{view,save,edit,delete} |
| `inventories` | InventoryController | inventory.{view,save,edit,delete} |
| `warehouses` | WarehouseController | warehouses.{view,save,edit,delete} |

### 5.1 Stock Listing

- `GET /stocks` — paginated ledger; filters by product, warehouse, movement type, admin.
- Summary cards: total stock in, total stock out, total balance (sum of inventory quantity), number of low-stock products.
- Per-warehouse tabs when multiple warehouses exist.

### 5.2 Inventory Listing

- `GET /inventories` — current quantity per product per warehouse.
- Status lifecycle (`active`/`inactive`/`archive`) with `closed_date` stamping on closure.

### 5.3 Warehouse Management

- Full CRUD (`warehouses.*` permissions) including contact person details and address.

## 6. API Surface (React POS)

Sanctum-protected endpoints in `routes/api.php`:

| Endpoint | Method | Purpose |
|---|---|---|
| `GET /stock` | GET | Stock movements list |
| `POST /save-stock` | POST | Record a movement (IN/OUT/adjust) |
| `GET /warehouse` | GET | Warehouse list |

Stock mutations from the POS go through the same `StockController`/`ActivityLogger` path, keeping the ledger consistent with the admin panel.

## 7. Low-Stock Alerting

- Threshold driven by `Configuration::get('stock_warning_level')`.
- **Admin dashboard** — dashboard KPI and charts surface low-stock products.
- **Reports** — the low-stock report lists products whose total quantity falls below the threshold, with current quantity and recommended reorder qty.
- **Notifications** — `NotificationHelper` posts a warning-type notification when a product crosses the threshold.

## 8. Permissions

| Group | Permissions |
|---|---|
| Stocks | `stocks.view/save/edit/delete` |
| Inventory | `inventory.view/save/edit/delete` |
| Warehouses | `warehouses.view/save/edit/delete` |

## 9. Related Code Locations

- Models: `laravel/app/Models/{Stock,Inventory,Warehouse}.php`
- Controllers: `laravel/app/Http/Controllers/{StockController,InventoryController,WarehouseController}.php`
- API: `laravel/app/Http/Controllers/Api/ApiController.php` (stock/warehouse methods)
- Config: `stock_warning_level` in `configurations` table / ConfigurationSeeder
