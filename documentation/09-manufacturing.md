# Module 09 — Manufacturing

**Module:** Manufacturing (Manufacture)
**Category:** Production
**Purpose:** Manages production from raw materials to finished goods: materials (BOM items), raw-material receipts, production batches, and finished-goods output.

> Manufacturing transforms materials into products. Each production run records what was consumed from raw stock and what was produced into finished stock, keeping both inventory ledgers consistent.

## 1. Overview

The module covers:

- **Materials** — raw-materials catalog (products tagged for production).
- **Material receipts** — raw material intake, generating IN stock movements.
- **Production** — batch production runs recording material consumption and finished output.
- **Finished goods** — completed batches posting OUT of raw stock and IN to finished stock.

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `materials` | Raw material master | name, fk_unit_id, fk_warehouse_id, material_group, specification, quantity, unit_price, status |
| `material_receipts` | Raw material intake | fk_material_id, fk_supplier_id, fk_warehouse_id, quantity, unit_price, total, reference, purchased_at, fk_admin_id |
| `material_receipt_details` | Receipt lines | fk_material_receipt_id, fk_material_id, fk_warehouse_id, quantity, unit_price, subtotal, discount, fk_admin_id |
| `productions` | Production batch header | fk_product_id, fk_warehouse_id, fk_admin_id, quantity, cost, reference, status, produced_at |
| `production_details` | Batch consumption lines | fk_production_id, fk_material_id, quantity, unit_price, subtotal, discount |

### 2.1 Production Status Lifecycle

| Status | Meaning |
|---|---|
| `pending` | Batch created; awaiting processing |
| `processing` | Materials being consumed |
| `completed` | Finished goods moved into stock |
| `cancelled` | Batch voided |

## 3. Key Models

- **Material** (`app/Models/Material.php`) — `unit()`, `warehouse()`, `materialReceipts()`, `productionDetails()`.
- **MaterialReceipt** — `supplier()`, `warehouse()`, `details()`.
- **Production** (`app/Models/Production.php`) — `product()` (finished good), `warehouse()`, `admin()`, `details()` (material consumption).
- **ProductionDetail** — material line: quantity, unit price, subtotal, discount.

## 4. Admin Surface (Routes)

| Route Prefix | Controller | Permissions |
|---|---|---|
| `materials` | MaterialController | materials.{view,save,edit,delete} |
| `material-receipts` | MaterialReceiptController | materials.* |
| `productions` | ProductionController | productions.{view,save,edit,delete} |

### 4.1 Materials

- Full CRUD: name, unit, warehouse, material group, specification, quantity on hand, unit price.
- Materials may reference a product; consumption is drawn from raw-material stock.

### 4.2 Material Receipts

- `POST /material-receipts` — intake raw material: pick supplier, warehouse, material lines with quantity/price.
- Each receipt line creates an **IN stock movement** for the material (stock type IN) and updates material quantity.

### 4.3 Productions

- `POST /productions` — create batch: finished product, target quantity, cost, warehouse.
- `PUT /productions/{id}` — update batch lines (material consumption) and status.
- On **completed**: raw materials are consumed (OUT movements per production detail) and finished goods increase (IN movement for the product) — both posted to the stock ledger.

## 5. Inventory Integration

| Event | Stock Ledger Effect |
|---|---|
| Material receipt | Raw material IN (warehouse) |
| Production consumption | Raw material OUT (per production detail qty) |
| Production completion | Finished product IN (batch quantity) |
| Production cancel | Reverse the above |

## 6. Permissions

| Group | Permissions |
|---|---|
| Materials | `materials.view/save/edit/delete` |
| Productions | `productions.view/save/edit/delete` |

## 7. Related Code Locations

- Models: `laravel/app/Models/{Material,MaterialReceipt,MaterialReceiptDetail,Production,ProductionDetail}.php`
- Controllers: `laravel/app/Http/Controllers/{MaterialController,MaterialReceiptController,ProductionController}.php`
- Views: `laravel/resources/views/manufacture/`
