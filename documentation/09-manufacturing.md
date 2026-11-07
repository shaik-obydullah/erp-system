# Module 09 — Manufacturing

**Module:** Manufacturing (Manufacture)
**Category:** Production
**Purpose:** Defines bills of materials, plans production runs, and records executed production with cost accounting.

> Manufacturing links a finished product to a bill of materials (BOM) and records production plans and runs with production cost, other cost, expected profit, and quantity. Each production run posts its cost to the finance ledger.

## 1. Overview

The module covers:

- **Bills of Materials** — a named BOM attached to a product, with unit, quantity, and description.
- **Production Planning** — planned runs against a BOM with cost/profit expectations and a `finalized` flag.
- **Production** — recorded production runs against a BOM that post cost accounting.

There are **no** `materials`, `material_receipts`, `material_receipt_details`, or `production_details` tables. Raw-material consumption and finished-goods stock movements are not modelled; production records are cost/planning records only.

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `bill_of_materials` | BOM definition | fk_product_id, name, unit, quantity, description |
| `production_plannings` | Planned production | fk_bill_of_material_id, production_cost, other_cost, expected_profit, quantity, finalized (yes/no) |
| `productions` | Executed production | fk_bill_of_material_id, production_cost, other_cost, expected_profit, quantity, journal_entry_id, accounting_version |

All three tables track `created_by` / `updated_by` / `deleted_by` and use soft deletes.

### 2.1 Planning vs. Production

- **Planning** rows are non-financial: they capture expected costs/profit, quantity, and whether the plan is finalized.
- **Production** rows carry `accounting_version` and `journal_entry_id` and post a `bomExpense` transaction, a cashbook outflow, and a balanced journal entry (inventory debit / cash credit) equal to `production_cost + other_cost`.

## 3. Key Models

- **BillOfMaterial** (`app/Models/BillOfMaterial.php`) — `product()`, `productionPlannings()`. Table `bill_of_materials`.
- **ProductionPlanning** — `billOfMaterial()`. Table `production_plannings`.
- **Production** — `billOfMaterial()`, `journalEntry()`. Table `productions`.

## 4. Admin Surface (Routes)

| Route Prefix | Controller | Permissions |
|---|---|---|
| `bill-of-materials` | BillOfMaterialController | resource except `show`; relies on admin auth group |
| `production-plannings` | ProductionPlanningController | resource except `show`/`destroy`; relies on admin auth group |
| `productions` | ProductionController | resource except `destroy`; relies on admin auth group |

There is no dedicated Manufacturing permission group in `PermissionSeeder`.

### 4.1 Bills of Materials

- Create/edit a BOM by selecting a product and entering a name, unit, quantity, and description.
- BOMs are referenced by both planning and production records.

### 4.2 Production Planning

- Create a plan against a BOM with `production_cost`, `other_cost`, `expected_profit`, `quantity`, and `finalized` (`yes`/`no`).
- Plans do not post accounting entries.

### 4.3 Productions

- Create a production run against a BOM with `production_cost`, `other_cost`, `expected_profit`, and `quantity`.
- On save, `AccountingPoster::production()` posts the run cost and writes the journal entry.
- Production runs do not mutate stock quantities or create raw-material consumption records.

## 5. Inventory Integration

There is currently **no automatic inventory effect** from manufacturing:

| Event | Stock Ledger Effect |
|---|---|
| BOM created/updated | None |
| Production planned | None |
| Production recorded | None (finance/accounting only) |

To reflect finished goods or raw-material movement, stock lines must be created or adjusted through the Inventory module.

## 6. Finance Integration

- `AccountingPoster::production()` creates:
  - a `Transaction` of type `bomExpense`,
  - a `Cashbook` outflow for the production cost,
  - a balanced journal entry (inventory debit, cash credit).
- Costs are only posted when `production_cost + other_cost > 0`; repeated posting is prevented by `source_key` / `accounting_version` idempotency.

## 7. Permissions

There is no Manufacturing-specific permission group. The BOM, production-planning, and production resource routes are protected by `auth:admin` + `active.admin` and rely on menu visibility rather than per-route `permission:` checks.

## 8. Related Code Locations

- Models: `laravel/app/Models/{BillOfMaterial,ProductionPlanning,Production}.php`
- Controllers: `laravel/app/Http/Controllers/{BillOfMaterialController,ProductionPlanningController,ProductionController}.php`
- Accounting: `laravel/app/Accounting/AccountingPoster.php` (`production`)
- Views: `laravel/resources/views/manufacture/`
