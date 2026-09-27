# Module 07 — Financial Management

**Module:** Financial Management (Finance)
**Category:** Accounting
**Purpose:** Tracks every rupee in and out of the business: income, expenses, the running cashbook, bank accounts, transaction journals, and payment methods.

> Finance is cash-flow driven. Every income/expense event posts to the cashbook and, optionally, to a bank account; the cashbook is the immutable daily ledger from which the balance sheet and P&L reports are derived.

## 1. Overview

The finance module includes:

- **Income** — revenue events (sales, services, other income).
- **Expenses** — operating spend (purchases, salaries, utilities).
- **Cashbook** — daily cash position and running balance.
- **Bank Accounts** — account balances reconciled with the cashbook.
- **Transactions** — line-item journal entries linked to sources.
- **Payment Methods** — how payments are settled (cash, card, etc.).

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `incomes` | Income ledger | fk_income_category_id, fk_admin_id, title, description, amount, date, file, fk_cashbook_entry_id |
| `expenses` | Expense ledger | fk_expense_category_id, fk_admin_id, title, description, amount, date, file, fk_cashbook_entry_id |
| `income_categories` | Income buckets | name, status |
| `expense_categories` | Expense buckets | name, status |
| `cashbook` | Daily cash ledger | fk_income_id, fk_expense_id, fk_admin_id, type (cash/due), sub_type (in/out), amount, status, date |
| `cashbook_entries` | Entry detail | fk_cashbook_id, type, ref_model, ref_id, amount, description, date, status |
| `bank_accounts` | Bank balances | name, account_name, account_number, branch, opening_balance, balance, status |
| `bank_transactions` | Bank activity | fk_bank_account_id, fk_admin_id, type (credit/debit), amount, title, description, date, transaction_id, status |
| `transactions` | Journal line | fk_customer_id, fk_supplier_id, fk_cashbook_entry_id, fk_bank_transaction_id, type, description, amount, date |
| `payment_methods` | Settlement types | name, status |

### 2.1 Income / Expense Flow

Each income or expense row:

1. Posts a **cashbook** record (`type = cash|due`, `sub_type = in|out`).
2. Optionally links a **cashbook entry** (`fk_cashbook_entry_id`) for line detail.
3. Optionally links a **bank transaction** (credit for income, debit for expense) against a bank account.

## 3. Key Models

- **Income** — `category()`, `admin()`, `cashbookEntry()`. Scopes for `incomeToday`, `incomeThisWeek`, `incomeThisMonth`, `incomeTotal`.
- **Expense** — `category()`, `admin()`, `cashbookEntry()`. Scopes mirroring income.
- **Cashbook** — `income()`, `expense()`, `admin()`, `entries()`. `getPreviousAmount` computes running balance; `balance` = opening + Σ(in − out).
- **BankAccount** — `bankTransactions()`; balance rolls forward from `opening_balance`.
- **PaymentMethod** — name/status; referenced by orders (`fk_payment_method_id`) and POS.

## 4. Admin Surface (Routes)

| Route Prefix | Controller | Permissions |
|---|---|---|
| `incomes` | IncomeController | incomes.{view,save,edit,delete} |
| `expenses` | ExpenseController | expenses.{view,save,edit,delete} |
| `cashbook` | CashbookController | cashbook.{view,save,edit,delete} |
| `bank-accounts` | BankAccountController | bankaccount.{view,save,edit,delete} |
| `transactions` | TransactionController | transactions.{view,save,edit,delete} |
| `payment-methods` | PaymentMethodController | settings.* |
| `income-categories` / `expense-categories` | CategoryController | settings.* |

### 4.1 Cashbook

- `GET /cashbook` — daily ledger; date filter; summary cards for today's in/out/net and closing balance.
- `POST /cashbook` — new entry (income or expense ref, or standalone), type cash/due, sub_type in/out; running balance recalculated.
- Payment entries created by POS/sales automatically land here.

### 4.2 Income & Expenses

- Full CRUD with date, amount, category, description, optional file attachment (stored under `public/uploads/`).
- Category-filtered listings with period summaries.

### 4.3 Bank Accounts

- Full CRUD; balance = opening balance ± transactions; status lifecycle (active/inactive/archive).
- Optional linkage: post income/expense to a bank account to generate a bank transaction.

## 5. API Surface (React POS)

| Endpoint | Method | Purpose |
|---|---|---|
| `GET /income` | GET | List income |
| `POST /save-income` | POST | Create income (posts to cashbook) |
| `GET /expense` | GET | List expenses |
| `POST /save-expense` | POST | Create expense (posts to cashbook) |

## 6. Reporting & Reconciliation

- **Balance Sheet / P&L** — derived from cashbook totals and bank balances (see Reports module).
- **Customer/Supplier balances** — tracked on `customers.balance` / `suppliers.balance` and `balances` tables, updated by cashbook `due` entries.
- **Fiscal year** — reports respect `fiscal_year_start`/`fiscal_year_end`.

## 7. Permissions

| Group | Permissions |
|---|---|
| Incomes | `incomes.view/save/edit/delete` |
| Expenses | `expenses.view/save/edit/delete` |
| Cashbook | `cashbook.view/save/edit/delete` |
| Bank Accounts | `bankaccount.view/save/edit/delete` |
| Transactions | `transactions.view/save/edit/delete` |
| Payment Methods / Categories | `settings.view/save` |

## 8. Related Code Locations

- Models: `laravel/app/Models/{Income,Expense,IncomeCategory,ExpenseCategory,Cashbook,CashbookEntry,BankAccount,BankTransaction,Transaction,PaymentMethod}.php`
- Controllers: `laravel/app/Http/Controllers/{IncomeController,ExpenseController,CashbookController,BankAccountController,TransactionController,PaymentMethodController}.php`
- API: `laravel/app/Http/Controllers/Api/ApiController.php` (income/expense methods)
