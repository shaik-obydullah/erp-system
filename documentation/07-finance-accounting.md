# Module 07 — Financial Management & Accounting

**Module:** Financial Management (Finance)
**Category:** Accounting
**Purpose:** Tracks money in and out of the business through incomes, expenses, the cashbook, transaction records, payables/receivables, fixed assets, and a double-entry general ledger (chart of accounts, journal entries, trial balance, P&L, balance sheet, cash flow, and reconciliation).

> Finance is the single financial source of truth. Operational events (sales, purchases, stock, payroll, campaigns, production, shipment costs) write simple-layer rows (`transactions`, `incomes`, `expenses`, `cashbook`) and, when the general ledger is enabled, also post balanced journal entries through `AccountingPoster`.

## 1. Overview

The finance module includes:

- **Income** — miscellaneous/operating income events.
- **Expenses** — operating spend.
- **Cashbook** — money-in / money-out ledger with receivable/payable columns.
- **Transactions** — typed financial events (20 types) linked to their source business document.
- **Payables / Receivables** — outstanding balances.
- **Fixed assets** — capital purchases posted to the ledger.
- **Accounting (general ledger)** — chart of accounts, journal entries/lines, fiscal periods, opening balances, and financial statements.
- **Currencies** — multi-currency configuration (managed from Settings).

There are **no** `income_categories`, `expense_categories`, `cashbook_entries`, `bank_accounts`, `bank_transactions`, or `payment_methods` tables in the current schema.

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `incomes` | Income ledger | table_name, fk_transaction_id, description, amount, vat_code, vat_rate, net_amount, vat_amount, journal_entry_id |
| `expenses` | Expense ledger | table_name, fk_transaction_id, description, amount, vat_code, vat_rate, net_amount, vat_amount, journal_entry_id |
| `cashbook` | Cash/receivable ledger | table_name, fk_reference_id, description, in_amount, out_amount, amount_payable, amount_receivable, journal_entry_id |
| `transactions` | Typed financial events | date, type, fk_reference_id, amount, paid_amount, due_amount, journal_entry_id |
| `payable` | Amounts owed | table_name, fk_reference_id, description, amount |
| `receivable` | Amounts due in | table_name, fk_reference_id, description, amount |
| `balances` | Per-customer credit/balance | fk_user_id, amount |
| `fixed_assets` | Capital assets | name, price, description, journal_entry_id, accounting_version |
| `accounts` | Chart of accounts | code, name, system_key, type (asset/liability/equity/revenue/expense), normal_balance (debit/credit), parent_id, is_system, is_active, description |
| `journal_entries` | Double-entry headers | entry_no, date, memo, event_type, source_type, source_id, source_key, status, is_auto, posted_at |
| `journal_lines` | Double-entry lines | journal_entry_id, account_id, debit, credit, description, party_type, party_id |
| `fiscal_periods` | Accounting periods | name, start_date, end_date, status, closed_at |
| `opening_balances` | Account opening balances | account_id, fiscal_year, debit, credit, note |
| `fiscal_year` | Legacy fiscal-year definition | fiscal_year_start, fiscal_year_end |
| `currencies` | Multi-currency | code, name, symbol, exchange_rate, is_base, is_active |
| `billings` | Legacy billing-address table | fk_sale_id, full_name, email, phone, address, city, state, zip, country |

`billings` exists in the database but has no model, controller, or route in the current codebase.

All finance tables track `created_by` / `updated_by` / `deleted_by` and use soft deletes unless noted otherwise.

### 2.1 Transaction Types (20)

`Transaction` constants: `userFund`, `supplierDeposit`, `supplierPayment`, `bomExpense`, `salaryPayment`, `campaignExpense`, `stockIn`, `stockOut`, `saleIncome`, `saleDue`, `fixedAsset`, `income`, `expense`, `miscIncome`, `miscExpense`, `shipmentCost`, `purchase`, `purchaseReturn`, `stockAdjustmentLoss`, `stockAdjustmentGain`.

### 2.2 Income / Expense Flow

Each admin-created income or expense:

1. Creates a `Transaction` (`type = income` or `expense`) with amount/paid/due.
2. Creates the `incomes` / `expenses` row with a VAT split extracted by `VatCalculator`.
3. Creates a `cashbook` row (`in_amount` for income, `out_amount` for expense).
4. Posts the balanced journal entry via `AccountingPoster::income()` / `expense()`.

### 2.3 Chart of Accounts

`config/accounting.php` seeds **26 system accounts** across five types, including:

| Code | System key | Type | Normal balance |
|---|---|---|---|
| 1000 | `cash` | asset | debit |
| 1010 | `bank` | asset | debit |
| 1100 | `accounts_receivable` | asset | debit |
| 1200 | `inventory` | asset | debit |
| 1300 | `supplier_advances` | asset | debit |
| 1500 | `fixed_assets` | asset | debit |
| 2000 | `accounts_payable` | liability | credit |
| 2100 / 2110 | `sales_tax_payable` / `input_vat` | liability / asset | credit / debit |
| 2200 / 2300 | `customer_advances` / `salaries_payable` | liability | credit |
| 3000 / 3100 / 3900 | `owner_equity` / `retained_earnings` / `opening_balance_equity` | equity | credit |
| 4000 / 4010 / 4100 / 4110 | `sales_revenue` / `sales_returns` / `other_income` / `general_income` | revenue | credit (returns: debit) |
| 5000–5900 | `cost_of_goods_sold`, `salaries_expense`, `rent_expense`, `utilities_expense`, `marketing_expense`, `inventory_adjustment`, `manufacturing_expense`, `general_expense` | expense | debit |

GL behaviour is controlled by `ACCOUNTING_GL_ENABLED` (default `true`) and `ACCOUNTING_GL_ENFORCED` (default `false`; when false, ledger errors are logged rather than blocking the business operation).

## 3. Key Models

- **Income** — `transaction()`, `journalEntry()`. Table `incomes`.
- **Expense** — `transaction()`, `journalEntry()`. Table `expenses`.
- **Cashbook** — `reference()`, `customer()`, `supplier()`, `sale()`, `income()`, `expense()`, `journalEntry()`. Table `cashbook`.
- **Transaction** — `cashbookEntries()`, `journalEntry()`, `typeLabel()`; table `transactions`.
- **Account** — `parent()`, `children()`, `journalLines()`, `scopeActive()`, `scopeOfType()`, `getIsDebitAttribute()`.
- **JournalEntry** — `lines()`, `getDebitTotalAttribute()`, `getCreditTotalAttribute()`, `getIsBalancedAttribute()`, `scopePosted()`, `scopeBetween()`.
- **JournalLine** — `entry()`, `account()`.
- **Payable / Receivable** — simple ledger projections (tables `payable`, `receivable`).
- **Balance** — `user()`; table `balances`.
- **FixedAsset** — `journalEntry()`.
- **Currency** — `scopeActive()`, `scopeBase()`.

## 4. Admin Surface (Routes)

| Route Prefix | Controller | Permissions |
|---|---|---|
| `cashbook` | CashbookController | `cashbook.view` (read-only) |
| `incomes` | IncomeController | `incomes.view`, `incomes.save` |
| `expenses` | ExpenseController | `expenses.view`, `expenses.save` |
| `payables` | PayableController | `cashbook.view` (read-only) |
| `receivables` | ReceivableController | `cashbook.view` (read-only) |
| `transactions` | TransactionController | `transactions.view` (read-only) |
| `fixed-assets` | FixedAssetController | resource CRUD; relies on admin auth group |
| `currencies` | CurrencyController | resource CRUD + `settings.save` for `set-base` |
| `accounting` | AccountingController | `accounting.view` → `accounting.advanced` → `accounting.manage` |

### 4.1 Accounting Routes

| Route | Permission |
|---|---|
| `GET /accounting` | `accounting.view` |
| `GET /accounting/journal`, `/ledger`, `/trial-balance`, `/profit-loss`, `/balance-sheet`, `/cash-flow`, `/chart-of-accounts`, `/reconciliation` | `accounting.advanced` |
| `POST /accounting/accounts`, `PUT /accounting/accounts/{account}`, `DELETE /accounting/accounts/{account}` | `accounting.manage` |

The overview shows cash balance, receivable/payable, supplier advances, tax payable, debit/credit totals, unbalanced/broken journal entries, and reconciliation differences.

### 4.2 Cashbook

- `GET /cashbook` — read-only ledger view with in/out totals and receivable/payable columns.
- Cashbook rows are written automatically by sales, purchases, incomes, expenses, shipment costs, funds, and other financial events.

### 4.3 Income & Expenses

- List + create only (`index`, `create`, `store`); no edit/delete routes.
- Amount, description, and optional VAT code/rate; VAT is extracted server-side.

### 4.4 Fixed Assets

- Resource routes except `show`/`destroy` (`index`, `create`, `store`, `edit`, `update`).
- Each asset records a price and posts to the ledger via `AccountingPoster::fixedAsset()`.

## 5. API Surface (React POS)

| Endpoint | Method | Purpose |
|---|---|---|
| `POST /income` | POST | List income |
| `POST /save-income` | POST | Create income (posts transaction + cashbook + ledger) |
| `POST /expense` | POST | List expenses |
| `POST /save-expense` | POST | Create expense (posts transaction + cashbook + ledger) |
| `POST /report` | POST | Report data for the POS |

## 6. Double-Entry Ledger & Reconciliation

- `AccountingPoster` emits balanced entries for: sales, sale dues, sale returns, income, expenses, purchase orders, customer/supplier funds, supplier payments, shipment costs, stock adjustments, fixed assets, payroll, campaign spend, production, stock in, supplier opening balances, shipment returns, imports, and reversals.
- `LedgerProjector` replays existing simple-layer rows into the ledger idempotently (`accounting:backfill`).
- `accounting:verify` checks balance and reconciliation; it is scheduled daily at **02:00** via `routes/console.php`.
- `GET /accounting/reconciliation` compares ledger account balances with cashbook/simple-layer totals.

## 7. Reporting & Reconciliation

- **Trial balance, P&L, balance sheet, cash flow** — computed from posted journal lines.
- **Customer/supplier balances** — tracked on `customers.balance` / `suppliers.balance` and the `balances` table, and reconciled against ledger receivable/payable accounts.
- **VAT return** — `GET /reports/vat-return` (`dashboard.view`) summarises output/input VAT.
- **Fiscal periods** — `fiscal_periods` and `opening_balances` support period closing and opening balances.

## 8. Permissions

| Group | Permissions |
|---|---|
| Accounting | `accounting.view`, `accounting.advanced`, `accounting.manage` |
| Cashbook | `cashbook.view/save/edit/delete` |
| Incomes | `incomes.view/save/edit/delete` |
| Expenses | `expenses.view/save/edit/delete` |
| Transactions | `transactions.view/save/edit/delete` |

Payables/receivables use `cashbook.view`. Fixed assets and currencies rely on the admin auth group (currency base change uses `settings.save`).

## 9. Related Code Locations

- Models: `laravel/app/Models/{Income,Expense,Cashbook,Transaction,Account,JournalEntry,JournalLine,Payable,Receivable,Balance,FixedAsset,Currency,FiscalPeriod,OpeningBalance}.php`
- Controllers: `laravel/app/Http/Controllers/{IncomeController,ExpenseController,CashbookController,PayableController,ReceivableController,TransactionController,FixedAssetController,CurrencyController,AccountingController,ReportController,VatReturnController}.php`
- Accounting services: `laravel/app/Accounting/AccountingPoster.php`, `laravel/app/Services/LedgerProjector.php`
- Config: `laravel/config/accounting.php`
- Commands: `accounting:backfill`, `accounting:verify`
- API: `laravel/app/Http/Controllers/Api/ApiController.php` (income/expense/report methods)
