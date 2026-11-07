# Module 08 — Procurement & Supply Chain

**Module:** Procurement & Supply Chain (Procurement)
**Category:** Purchasing
**Purpose:** Manages the purchasing pipeline from supplier onboarding through identified needs, purchase orders, shipment receipts, landed shipment costs, supplier advances/payments, and shipment returns.

> Procurement closes the loop with suppliers. Needs identify what must be bought; purchase orders commit an amount to a supplier; shipments record receipt into a warehouse with tracking and landed costs; payments and returns post through the finance layer. Stock quantities are managed separately in the Inventory module.

## 1. Overview

The module covers:

- **Suppliers** — vendor master records with portal access, balances, VAT details, funds, dues, and transaction history.
- **Needs** — identified purchasing requirements.
- **Purchase Orders** — a supplier commitment with an order number, amount, VAT split, and due amount (header-level; no PO line table).
- **Shipments** — receipt/receiving records linked to a PO and warehouse, with tracking number and status.
- **Shipment Costs** — landed costs (freight, duty, handling, insurance, other), paid or on credit.
- **Shipment Returns** — returns against a PO with an invoice amount and status.
- **Shipping Zones / Methods** — storefront shipping configuration (see E-Commerce module).
- **Supplier portal** — read-only PO/product/profile access (see E-Commerce module).

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `suppliers` | Vendor master | name, email (unique), password, mobile, address, balance, status, vat_number, country, vat_treatment, journal_entry_id, accounting_version |
| `needs` | Identified purchasing needs | description, date_identified |
| `purchase_orders` | PO header | fk_need_id, fk_supplier_id, order_number (unique), total_amount, due_amount, remarks, vat_code, vat_rate, net_amount, vat_amount, journal_entry_id |
| `shipments` | Receipts / incoming shipments | fk_po_id, fk_warehouse_id, tracking_number (unique), received_date, status, remark |
| `shipment_costs` | Landed costs | fk_shipment_id, fk_supplier_id, cost_type, amount, cost_date, payment_status, paid_date, invoice_number, payment_method, description, fk_transaction_id, fk_cashbook_id, fk_payment_transaction_id, fk_payment_cashbook_id, journal_entry_id |
| `shipment_returns` | Returns against a PO | fk_po_id, invoice_amount, return_reason, status, remark, journal_entry_id, accounting_version |
| `shipping_zones` | Storefront zones | name, countries (JSON), status, sort_order |
| `shipping_methods` | Storefront methods | fk_shipping_zone_id, name, type, cost, min_order_amount, status, sort_order |
| `shippings` | Legacy shipping-address table | fk_sale_id, full_name, phone, address, city, state, zip, country |

There are **no** `purchase_order_details`, `purchase_order_status`, `purchase_details`, or `purchase_payments` tables. A purchase order stores a single `total_amount` rather than product lines, and shipment costs/payments replace a separate purchase-payment table. The legacy `shippings` table has no model, controller, or route.

### 2.1 Status Values

| Entity | Values |
|---|---|
| Shipment | `pending`, `shipped`, `in_transit`, `delivered`, `canceled` |
| Shipment cost | `paid`, `unpaid` |
| Shipment return | `pending`, `processed`, `completed`, `rejected` |
| Shipping zone / method | `active`, `inactive` |

Purchase orders have no status column; outstanding commitment is represented by `due_amount`.

## 3. Key Models

- **Supplier** (`app/Models/Supplier.php`) — `isActive()`, `journalEntry()`; Authenticatable for the supplier guard. Balances are updated by funds, shipment costs, and payments.
- **Need** — `purchaseOrders()`.
- **PurchaseOrder** — `need()`, `supplier()`. Table `purchase_orders`.
- **Shipment** — `purchaseOrder()`, `warehouse()`, `costs()`, `landedCostTotal()`.
- **ShipmentCost** — `shipment()`, `supplier()`, `transaction()`, `cashbook()`, `paymentTransaction()`, `paymentCashbook()`, `journalEntry()`, `isPaid()`, `typeLabel()`.
- **ShipmentReturn** — `purchaseOrder()`, `journalEntry()`. Table `shipment_returns`.
- **ShippingZone** — `methods()`, `activeMethods()`, `matchesCountry()`.
- **ShippingMethod** — `zone()`, `isAvailable()`, `calculateCost()`.

## 4. Admin Surface (Routes)

| Route Prefix | Controller | Permissions |
|---|---|---|
| `suppliers` | SupplierController | `suppliers.view/save/edit/delete` |
| `suppliers/fund` | SupplierFundController | `suppliers.view` (list/balance), `suppliers.save` (add funds) |
| `suppliers/due` | SupplierDueController | `suppliers.view` |
| `suppliers/transactions` | SupplierTransactionController | `suppliers.view/save` (export/import) |
| `needs` | NeedController | resource CRUD; relies on admin auth group |
| `purchase-orders` | PurchaseOrderController | resource except show/destroy; relies on admin auth group |
| `shipments` | ShipmentController | resource except destroy; relies on admin auth group |
| `shipments/{shipment}/costs` | ShipmentCostController | `shipments.save` / `shipments.edit` / `shipments.delete` |
| `shipment-returns` | ShipmentReturnController | resource except show/destroy; relies on admin auth group |
| `shipping-zones` | ShippingZoneController | resource except destroy |
| `shipping-zones/{zone}/methods` | ShippingMethodController | `shipping-methods.save/edit/delete` |

### 4.1 Purchase Orders

- `GET /purchase-orders` — list.
- `POST /purchase-orders` — pick a **need** and supplier, enter order number, total amount, due amount, and VAT treatment. The controller extracts net/VAT from the gross amount and posts the commitment via `AccountingPoster::purchaseOrder()`.
- `PUT /purchase-orders/{id}` — update the header and re-post accounting as needed.
- No product-line entry screen exists; the PO amount is a single total.

### 4.2 Needs

- Full resource CRUD for identified purchasing requirements (`description`, `date_identified`). A PO must reference a need.

### 4.3 Shipments (Receiving)

- Create a shipment against a PO and warehouse with tracking number, received date, status, and remark.
- Optionally create a landed cost at the same time; this delegates to `ShipmentCostService::create()`.
- Shipments do **not** create or increment `stocks` rows automatically. Inventory must be added/updated through the Stock module or POS API.

### 4.4 Shipment Costs

- `POST /shipments/{shipment}/costs` — record a cost (`freight`, `duty`, `handling`, `insurance`, `other`) with amount, date, supplier, payment status, invoice number, and method.
- Paid costs create a `shipmentCost` transaction + cashbook outflow; unpaid costs require a supplier and reduce the supplier balance.
- `POST /shipments/costs/{shipmentCost}/pay` — mark an unpaid cost paid: creates a `supplierPayment` transaction + cashbook outflow, restores the supplier balance, and posts the supplier payment journal entry.
- `DELETE /shipments/costs/{shipmentCost}` — removes the cost and reverses its accounting.

### 4.5 Shipment Returns

- Create a return against a PO with invoice amount, reason (`damaged`, `incorrect_item`, `excess_quantity`, `other`), and status.
- Saving a return posts the reversal via `AccountingPoster::shipmentReturn()`.

### 4.6 Supplier Funds, Dues & Transactions

- `GET/POST /suppliers/fund` — add an advance to a supplier; increments `suppliers.balance`, creates a `supplierDeposit` transaction and cashbook outflow, and posts the supplier-fund journal entry. `GET /suppliers/fund/balance` returns the current balance.
- `GET /suppliers/due` — suppliers with outstanding dues.
- `GET /suppliers/transactions` with CSV export/import — supplier transaction history.

## 5. Supplier Portal (Supply Chain Link)

Vendors log in through the supplier guard and can:

- View dashboard KPIs (active product count, total/pending POs, balance, recent POs).
- Browse their purchase orders and PO detail.
- Browse their active products.
- View/update their profile.

The portal is read-only for procurement data — no shipment upload or invoice generation.

## 6. Procurement Reports

- **Supplier balances/dues** — outstanding amounts per supplier.
- **Supplier transactions** — fund/payment history with export.
- **Purchase commitments** — PO totals and due amounts.
- **Shipment landed costs** — per-shipment cost totals and payment status.
- **Low-stock reorder** — products under `low_stock_threshold` (feeds purchasing decisions).

## 7. Permissions

| Group | Permissions |
|---|---|
| Suppliers | `suppliers.view/save/edit/delete` |
| Shipments | `shipments.view/save/edit/delete` |
| Shipping Zones | `shipping-zones.view/save/edit/delete` |
| Shipping Methods | `shipping-methods.view/save/edit/delete` |

There is no separate `purchases` permission group; needs, purchase orders, and shipment returns rely on the admin auth group.

## 8. Related Code Locations

- Models: `laravel/app/Models/{Supplier,Need,PurchaseOrder,Shipment,ShipmentCost,ShipmentReturn,ShippingZone,ShippingMethod}.php`
- Controllers: `laravel/app/Http/Controllers/{SupplierController,SupplierFundController,SupplierDueController,SupplierTransactionController,NeedController,PurchaseOrderController,ShipmentController,ShipmentCostController,ShipmentReturnController,ShippingZoneController,ShippingMethodController}.php`
- Services: `laravel/app/Services/ShipmentCostService.php`, `SupplierDeletionGuard.php`
- Accounting: `laravel/app/Accounting/AccountingPoster.php` (`purchaseOrder`, `shipmentCost`, `supplierFund`, `supplierPayment`, `shipmentReturn`)
- Views: `laravel/resources/views/purchases/`, `laravel/resources/views/shipments/`, `laravel/resources/views/suppliers/`, `laravel/resources/views/shipping/`
