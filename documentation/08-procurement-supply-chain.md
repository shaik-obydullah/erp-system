# Module 08 — Procurement & Supply Chain

**Module:** Procurement & Supply Chain (Procurement)
**Category:** Purchasing
**Purpose:** Manages the purchasing pipeline from supplier onboarding through purchase orders, goods receipt, supplier payments, and due-balance tracking.

> Procurement closes the loop with suppliers. Purchase orders reference suppliers and their products; receipts create IN stock movements; payments post to the cashbook and update the supplier balance. Stock levels and supplier dues therefore always reconcile.

## 1. Overview

The module covers:

- **Suppliers** — vendor master records with portal access.
- **Purchase Orders** — order header + detail lines for products.
- **Purchase Receipts** — goods-in, which generate IN stock movements.
- **Supplier Payments** — cashbook-posted payments and due tracking.
- **Supplier portal** — shipment uploads and invoice generation (see E-Commerce module).

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `suppliers` | Vendor master | name, email (unique), password, mobile, address, balance, status |
| `purchase_orders` | PO header | fk_supplier_id, fk_purchase_order_status_id, total, note, reference, invoice_no, purchased_at, delivered_at, fk_admin_id |
| `purchase_order_details` | PO lines | fk_purchase_order_id, fk_product_id, quantity, unit_price, subtotal, discount, fk_admin_id |
| `purchase_order_status` | Status reference | name (pending/confirmed/received/cancelled) |
| `purchase_details` | Receipt ledger | fk_purchase_order_id, fk_product_id, fk_supplier_id, fk_warehouse_id, quantity, unit_price, subtotal, discount, reference, fk_admin_id |
| `purchase_payments` | Supplier payments | fk_supplier_id, fk_purchase_order_id, amount, note, fk_admin_id |

### 2.1 Purchase Order Status Lifecycle

| Status | Meaning |
|---|---|
| `pending` | PO created; awaiting confirmation |
| `confirmed` | Supplier accepted; awaiting delivery |
| `received` | Goods received (creates stock IN + purchase_details rows) |
| `cancelled` | PO voided |

## 3. Key Models

- **Supplier** (`app/Models/Supplier.php`) — `purchaseOrders()`, `purchaseDetails()`, `purchasePayments()`, `products()`. `isDue()` when `balance < 0`.
- **PurchaseOrder** (`app/Models/PurchaseOrder.php`) — `supplier()`, `status()`, `details()` (purchase_order_details), `purchaseDetails()` (receipts), `payments()`.
- **PurchaseOrderDetail** — line items; totals roll up to the PO header.
- **PurchaseDetail** — goods-received ledger; drives stock IN movements and warehouse allocation.
- **PurchasePayment** — payment on a PO; updates supplier balance.

## 4. Admin Surface (Routes)

| Route Prefix | Controller | Permissions |
|---|---|---|
| `suppliers` | SupplierController | suppliers.{view,save,edit,delete} |
| `purchase-orders` | PurchaseOrderController | purchases.{view,save,edit,delete} |
| `purchase-details` | PurchaseDetailController | purchases.* |
| `purchase-payments` | PurchasePaymentController | purchases.* |

### 4.1 Purchase Orders

- `GET /purchase-orders` — status-filtered list with supplier, total, and due info.
- `POST /purchase-orders` — create PO: pick supplier, add product lines (name/SKU/barcode search), apply line discounts.
- `PUT /purchase-orders/{id}` — edit lines and header.
- Status transitions logged through the `purchase_order_status` reference.

### 4.2 Goods Receipt

- When a PO is received (`status = received`), `purchase_details` rows are written per line.
- Each receipt line creates an **IN stock movement** (`stocks.type = IN`) to the selected warehouse, increasing inventory quantity.
- Supplier balance is credited per `balance` update.

### 4.3 Supplier Payments

- `POST /purchase-payments` — record amount against a supplier/PO; posts a cashbook entry (type `cash`, sub_type `out`) and updates supplier balance.

## 5. Supplier Portal (Supply Chain Link)

Vendors log in via the supplier guard and:

- View assigned shipments and mark them processed.
- Upload shipment documents.
- Generate/download shipment invoices.

## 6. Procurement Reports

- **Supplier balances** — outstanding due per supplier (balance sheet input).
- **Purchase summary** — totals by supplier/period.
- **Low-stock reorder** — suggests purchase quantities for products under `stock_warning_level` (feeds the AI reorder assistant).

## 7. Permissions

| Group | Permissions |
|---|---|
| Suppliers | `suppliers.view/save/edit/delete` |
| Purchase Orders / Receipts / Payments | `purchases.view/save/edit/delete` |

## 8. Related Code Locations

- Models: `laravel/app/Models/{Supplier,PurchaseOrder,PurchaseOrderDetail,PurchaseOrderStatus,PurchaseDetail,PurchasePayment}.php`
- Controllers: `laravel/app/Http/Controllers/{SupplierController,PurchaseOrderController,PurchaseDetailController,PurchasePaymentController}.php`
- Views: `laravel/resources/views/purchases/`
