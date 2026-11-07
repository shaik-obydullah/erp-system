# Module 05 — Sales & POS

**Module:** Sales & POS (Sales)
**Category:** Revenue
**Purpose:** Handles sales from cart to checkout across two surfaces — the admin/POS panel and the React 19 POS terminal — with VAT-aware totals, stock deduction, sale returns/credit notes, and due collection.

> Sales are the revenue backbone. Every sale references an optional customer, stores its own net/VAT/discount/due totals, decrements the selected stock lines, and posts transaction, income, cashbook, and double-entry journal records.

## 1. Overview

The sales module provides:

- **Sale management** — list, view, print invoice, and receive due payments.
- **POS terminals** — the Blade/Alpine admin POS screen and the standalone React 19 POS app.
- **Cart & checkout** — an admin cart persisted in `cart` / `cart_details`, plus direct checkout payloads from the POS.
- **Sale returns** — pending returns with approval/rejection, restocking, credit notes, and refund posting.
- **Storefront orders** — public checkout that creates a `Sale` with type `Ecommerce`.
- **Reviews** — published customer reviews displayed on product pages.

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `sales` | Sale header | table_name, fk_user_id (customer), invoice_id, type (POS/Ecommerce), net_price, vat_amount, tax_amount, shipping_cost, discount_amount, grand_total, paid_amount, buy_price, sale_due, status, send_notification, note, vat_breakdown |
| `sale_details` | Sale lines | fk_sale_id, fk_stock_id, stock_name, size, color, total_stock, sale_stock, subtotal, cost_price, vat_code, vat_rate, net_amount, vat_amount, gross_amount |
| `sale_returns` | Customer returns | fk_sale_id, fk_stock_id, quantity, refund_amount, reason, note, status (pending/approved/rejected), vat_code, vat_rate, net_amount, vat_amount, journal_entry_id, accounting_version |
| `cart` | Admin POS cart header | fk_admin_id, fk_user_id, net_total, vat_total, tax_total, discount_total, grand_total, buy_total |
| `cart_details` | Admin POS cart lines | fk_cart_id, fk_stock_id, stock_name, total_stock, qty, unit, vat, tax, discount, subtotal, buy_price, vat_code, vat_rate |
| `reviews` | Product reviews | fk_product_id, fk_user_id, rating, review, status |
| `shippings` | Legacy shipping-address table | fk_sale_id, full_name, phone, address, city, state, zip, country |

`shippings` exists in the schema but has no model, controller, or route in the current codebase. There are **no** `orders`, `order_details`, `payments`, `carts`, `cart_attributes`, `banners`, `order_status`, or `shipment_reviews` tables.

### 2.1 Sale Status

Current checkout flows create sales with `status = completed`; the storefront customer portal also filters on `pending`, and the dashboard counts `orderPlaced` as pending. The status is a plain string column — there is no status-transition controller or status-log table.

## 3. Key Models & Relationships

- **Sale** (`app/Models/Sale.php`) — `details()`, `saleItems()`, `transaction()`, `customer()` (via `fk_user_id`).
- **SaleDetail** — `stock()`, `product()` (through the stock line).
- **SaleReturn** — `sale()`, `stock()`.
- **Cart** — table `cart`; `details()` has-many `cart_details`.
- **CartDetail** — `cart()`, `stock()`.
- **Review** — `product()`, `customer()` (via `fk_user_id`).
- **ShippingMethod / ShippingZone** — used by storefront shipping options (see E-Commerce module).

## 4. Admin Surface (Routes)

| Route Prefix | Controller | Permissions |
|---|---|---|
| `pos` | PosController | `pos.view`, `pos.save` |
| `sales` | SaleController | `sales.view`, `sales.edit` (receive due) |
| `sale-returns` | SaleReturnController | `sales.view`, `sales.save`, `sales.edit` |

### 4.1 Sales

- `GET /sales` — paginated list.
- `GET /sales/{sale}` — sale detail.
- `GET /sales/{sale}/invoice` — printable invoice.
- `POST /sales/{sale}/receive-due` — records a due payment (capped at `sale_due`), updates `paid_amount` / `sale_due`, writes a `saleDue` transaction and cashbook row, and posts the journal entry.

### 4.2 POS Terminal (Admin)

`GET /pos` (`permission:pos.view`) — the in-browser cashier screen.

| Endpoint | Purpose |
|---|---|
| `POST /pos/cart/add` | Add a stock line to the admin cart |
| `POST /pos/cart/remove` | Remove a cart line |
| `POST /pos/checkout` | Complete the sale |

`checkout` accepts either the persisted cart or a direct `cart[]` payload, then within one DB transaction:

1. Recomputes line VAT from each stock's `vat_code` / `vat_rate`.
2. Applies an optional discount and shipping cost.
3. Creates `Sale` (type `POS`, status `completed`) and `SaleDetail` rows.
4. Decrements each stock line's `quantity`.
5. Creates a `saleIncome` `Transaction`, an `Income`, and cashbook rows for payment and/or due.
6. Posts the double-entry journal through `AccountingPoster::sale()`.
7. Clears the admin cart.

### 4.3 Sale Returns

- Create a return against a sale + stock line (`quantity`, `refund_amount`, `reason`, `note`); initial status `pending`.
- `POST /sale-returns/{saleReturn}/approve` — restocks the returned quantity, posts the refund/reversal through `AccountingPoster::saleReturn()`, and reduces the sale's `paid_amount` and `grand_total`.
- `POST /sale-returns/{saleReturn}/reject` — marks the return rejected.
- `GET /sale-returns/{saleReturn}/credit-note` — printable credit note.

## 5. API Surface (React POS)

Sanctum-protected endpoints in `routes/api.php`:

| Endpoint | Method | Purpose |
|---|---|---|
| `GET /sale` | GET | List sales (paginated, searchable) |
| `POST /select-sale/{id}` | POST | Fetch a single sale |
| `POST /save-sale` | POST | Create a sale from `items[]` (stock id + quantity); validates stock, rebuilds prices server-side, decrements stock, and writes the accounting rows |
| `GET /stock` | GET | Stock-line list used by the POS |
| `POST /save-stock` | POST | Create a stock line from the POS |
| `GET /product` | GET | Product list for the POS |
| `GET /sync/pull` | GET | Mobile offline-sync pull |
| `POST /sync/push` | POST | Mobile offline-sync push |

There are no API endpoints for payments, cart management, or reviews; the React POS builds its cart client-side and submits `save-sale`.

## 6. Tax & Discount Computation

- **VAT** — `VatCalculator` uses each stock line's `vat_code` / `vat_rate`; when absent it falls back to the configured `vat_rate` and derives a code from that rate.
- **Tax** — the `tax_amount` column exists on `sales`, but current POS and storefront checkout set it to `0`.
- **Discount** — applied as a flat amount (`discount_amount`) and distributed across lines by `VatCalculator::applyDiscount()`.
- **Totals** — `grand_total` = net + VAT + shipping; `sale_due` = `grand_total − paid_amount`.

## 7. Inventory & Finance Integration

- **On sale:** stock quantity decremented per line; `saleIncome` transaction, `Income`, cashbook payment/due rows, and journal entry created.
- **On due collection:** `saleDue` transaction + cashbook `in_amount`, remaining `sale_due` reduced, journal posted.
- **On approved return:** stock restocked; refund transaction/cashbook/expense and reversing journal posted; sale totals reduced.

## 8. Permissions

| Group | Permissions |
|---|---|
| POS | `pos.view/save/edit/delete` |
| Sales | `sales.view/save/edit/delete` |
| Orders | `orders.view/save/edit/delete` (permission group exists; no order routes) |
| Reviews | `reviews.view/save/edit/delete` (permission group exists; reviews are read-only in the UI) |

## 9. Related Code Locations

- Models: `laravel/app/Models/{Sale,SaleDetail,SaleReturn,Cart,CartDetail,Review}.php`
- Controllers: `laravel/app/Http/Controllers/{PosController,SaleController,SaleReturnController}.php`
- API: `laravel/app/Http/Controllers/Api/ApiController.php` (`sale`, `selectSale`, `saveSale`, `stock`, `saveStock`)
- Accounting: `laravel/app/Accounting/AccountingPoster.php` (`sale`, `saleDue`, `saleReturn`)
- POS UI: `laravel/resources/views/pos/` and `react-app/`
