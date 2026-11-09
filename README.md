# ERP System

AI-powered full-stack ERP and multi-vendor marketplace system featuring a Laravel API, standalone React POS terminal, Blade/Vue storefront, and intelligent automation with predictive analytics for smarter business operations.

## Tech Stack Badges

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![React](https://img.shields.io/badge/React-19-61DAFB?style=for-the-badge&logo=react&logoColor=black)
![Vue](https://img.shields.io/badge/Vue-3-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-3-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=black)
![MariaDB](https://img.shields.io/badge/MariaDB-10.6-003545?style=for-the-badge&logo=mariadb&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind%20CSS-3-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)
![Vite](https://img.shields.io/badge/Vite-6-646CFF?style=for-the-badge&logo=vite&logoColor=white)
![Ollama](https://img.shields.io/badge/Ollama-qwen2.5:0.5b-000000?style=for-the-badge&logo=ollama&logoColor=white)
![Python](https://img.shields.io/badge/Python-3.11-3776AB?style=for-the-badge&logo=python&logoColor=white)
![Flask](https://img.shields.io/badge/Flask-3.0-000000?style=for-the-badge&logo=flask&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-24-2496ED?style=for-the-badge&logo=docker&logoColor=white)
![Laravel Sanctum](https://img.shields.io/badge/Laravel%20Sanctum-4-E34F26?style=for-the-badge&logo=laravel&logoColor=white)
![Chart.js](https://img.shields.io/badge/Chart.js-4-FF6384?style=for-the-badge&logo=chart.js&logoColor=white)
![jsPDF](https://img.shields.io/badge/jsPDF-3-EA4AAA?style=for-the-badge&logo=adobeacrobatreader&logoColor=white)

## Table of Contents

- [Live Demo](#live-demo)
- [Feature Highlights](#feature-highlights)
- [Tech Stack](#tech-stack)
- [Key Modules](#key-modules)
- [Screenshots](#screenshots)
- [Project Structure](#project-structure)
- [Module Documentation](#module-documentation)
- [Authentication & RBAC](#authentication--rbac)
- [REST API](#rest-api)
- [AI Features](#ai-features)
- [Business Intelligence](#business-intelligence)
- [Financial Integrity](#financial-integrity)
- [Quick Start](#quick-start)
- [Development Workflow](#development-workflow)
- [Testing](#testing)
- [Production Deployment](#production-deployment)
- [Database](#database)
- [License](#license)

## Live Demo

- **Admin Panel:** [erp.obydullah.com](https://erp.obydullah.com)
- **React POS:** [react-pos.obydullah.com](https://react-pos.obydullah.com)

## Access Credentials

- **Email:** [demo@obydullah.com]
- **Password:** [11111111]

## Feature Highlights

- **Two POS terminals** — an in-admin Blade POS and a standalone React 19 terminal, both writing to one transactional ledger. The API also exposes pull/push sync endpoints for offline or mobile clients.
- **Multi-vendor ecommerce** — storefront, cart, checkout (billing + shipping), customer portal, supplier portal, reviews, flash sales, and newsletter subscriptions.
- **Self-hosted AI (zero API cost)** — product descriptions, natural-language search, inventory insights, sales forecast, price suggestions, and a support chatbot powered by Ollama (qwen2.5:0.5b by default — a tiny model for low-spec machines). Health checks verify the configured model is actually installed, not just that Ollama is reachable.
- **ML Business Intelligence** — a separate [Flask/scikit-learn BI service](https://github.com/shaik-obydullah/bi-system) with KMeans clustering, IsolationForest anomaly detection, recommendations, Apriori combos, and linear-regression/Prophet forecasting, consumed through `BiController`.
- **Full double-entry accounting** — journal entries and lines, a 26-account chart of accounts, ledger, trial balance, profit & loss, balance sheet, cash flow, reconciliation, fiscal periods, opening balances, and a UK 9-box VAT return reconciled against the ledger.
- **Automated ledger posting** — every operational flow (POS/storefront sale, purchase order, sale return, stock adjustment, shipment landed cost, payroll, production, fixed asset, campaign spend, and customer/supplier fund) posts a balanced journal entry through `AccountingPoster`; `accounting:backfill` replays legacy rows idempotently and `accounting:verify` runs nightly.
- **End-to-end UK VAT** — stock-level VAT codes/rates, VAT-inclusive pricing, per-line extraction on POS/storefront/purchases/expenses/returns, and a 9-box VAT return reconciled to the ledger.
- **Data operations** — CSV import/export for products, customers, suppliers, catalog lookups, and customer/supplier transactions; barcode sheets; product media uploads.
- **Operational cashbook** — every financial event also writes a typed transaction + cashbook row atomically inside `DB::transaction()`.
- **Granular RBAC** — 4 seeded roles and 127 `module.action` permissions across 34 groups; `super-admin` holds every permission (no hard-coded bypass).
- **Full audit trail** — every login, failed login, create/update/delete is logged with IP and before/after data.
- **Multi-currency** — base-currency model with exchange rates and a `set-base` action.
- **Complete supply chain** — need → purchase order → shipment → return, plus a supplier-facing portal.

## Tech Stack

| Layer      | Technology                              |
| ---------- | --------------------------------------- |
| Backend    | Laravel 13.17, PHP 8.3, Sanctum Auth    |
| Frontend   | React 19, Vite 6, Tailwind CSS 3        |
| Storefront | Laravel Blade, Vue.js 3, Alpine.js 3    |
| Database   | MariaDB 10.6 (77 migrated tables)       |
| AI         | Ollama (qwen2.5:0.5b) — self-hosted LLM |
| BI         | Flask, scikit-learn (separate service)  |
| Infra      | Docker, Nginx, Apache                   |
| PDF        | jsPDF + jspdf-autotable                 |
| Charts     | Chart.js 4 + react-chartjs-2            |

## Key Modules

| Module              | Description                                                                                                                                                                         |
| ------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **POS Terminal**    | Category browsing, cart, checkout, VAT/tax/discount, PDF invoices                                                                                                                   |
| **Product Catalog** | Categories, brands, units, sizes, colors, image upload                                                                                                                              |
| **Inventory**       | Multi-warehouse stock with batch/lot tracking                                                                                                                                       |
| **Sales**           | Full lifecycle — sale creation, returns, invoice generation                                                                                                                         |
| **Finance**         | Income/expense tracking, double-entry transactions, cashbook, multi-currency, receivables/payables, fixed assets                                                                    |
| **Accounting**      | Journal entries & lines, 26-account chart, ledger, trial balance, P&L, balance sheet, cash flow, reconciliation, fiscal periods, opening balances, automated posting, UK VAT return |
| **VAT**             | Stock-level VAT codes/rates, VAT-inclusive pricing, per-line extraction, 9-box return reconciled to the ledger                                                                      |
| **Data Tools**      | CSV import/export (products, customers, suppliers, catalog lookups, party transactions), barcode sheets, product media uploads                                                      |
| **Procurement**     | Need → Purchase Order → Shipment → Returns pipeline, shipping zones/methods, landed shipment costs                                                                                  |
| **Manufacturing**   | Bill of Materials, production planning, cost tracking                                                                                                                               |
| **HRM**             | Employees, payroll, task management                                                                                                                                                 |
| **Ecommerce**       | Multi-vendor storefront with cart, checkout, customer/supplier portals, reviews, newsletter subscriptions                                                                           |
| **RBAC**            | 4 seeded roles, 127 permissions across 34 groups                                                                                                                                    |
| **AI**              | Product descriptions, inventory insights, sales forecasting, pricing, support chatbot                                                                                               |
| **Reporting**       | Sales trends, income vs expenses, profit analysis, annual performance charts                                                                                                        |

## Screenshots

A few highlights first; expand any group for the full gallery.

<table>
  <tr>
    <td align="center" width="33%"><img src="screenshots/Admin-Login.png" alt="Login" width="100%"/><br/><sub><strong>Login</strong></sub></td>
    <td align="center" width="33%"><img src="screenshots/Dashboard.png" alt="Dashboard" width="100%"/><br/><sub><strong>Dashboard</strong></sub></td>
    <td align="center" width="33%"><img src="screenshots/POS-Terminal.png" alt="Blade POS" width="100%"/><br/><sub><strong>Blade POS</strong></sub></td>
  </tr>
</table>

<details>
<summary><strong>Authentication &amp; System</strong> — 8 screenshots</summary>

**Admin Login**

![Admin Login](screenshots/Admin-Login.png)

**Dashboard Login / Admin Management**

![Admin Management](screenshots/Admin-Management.png)

**Create Admin**

![Create Admin](screenshots/Create-Admin.png)

**Roles & Permissions**

![Roles & Permissions](screenshots/Roles-Permissions.png)

**Create Role**

![Create Role](screenshots/Create-Role.png)

**Permissions**

![Permissions](screenshots/Permissions.png)

**ERP Settings**

![ERP Settings](screenshots/ERP-Settings.png)

**Activity Manager**

![Activity Manager](screenshots/Activity-Manager.png)

</details>

<details>
<summary><strong>Dashboard &amp; AI / BI Analytics</strong> — 11 screenshots</summary>

**Dashboard**

![Dashboard](screenshots/Dashboard.png)

**Business Intelligence Dashboard 1**

![Business Intelligence Dashboard 1](screenshots/Business-Intelligence-Dashboard-1.png)

**Business Intelligence Dashboard 2**

![Business Intelligence Dashboard 2](screenshots/Business-Intelligence-Dashboard-2.png)

**Business Intelligence Dashboard 3**

![Business Intelligence Dashboard 3](screenshots/Business-Intelligence-Dashboard-3.png)

**Product Analytics**

![Product Analytics](screenshots/Product-Analytics.png)

**Product Combo Analysis**

![Product Combo Analysis](screenshots/Product-Combo-Analysis.png)

**Prophet Sales Forecast**

![Prophet Sales Forecast](screenshots/Prophet-Sales-Forecast.png)

**Sales Forecast (Linear)**

![Sales Forecast Linear](screenshots/Sales-Forecast-Linear.png)

**Content-Based Product Recommendations**

![Content-Based Product Recommendations](screenshots/Content-Based-Product-Recommendations.png)

**Personalized Product Recommendations**

![Personalized Product Recommendations](screenshots/Personalized-Product-Recommendations.png)

**Popular Product Recommendations**

![Popular Product Recommendations](screenshots/Popular-Product%20Recommendations.png)

</details>

<details>
<summary><strong>AI Copilot</strong> — 7 screenshots</summary>

**AI Product Description (Create Product)**

![AI Create Product](screenshots/AI-Create-Product.png)

**AI Inventory Insights (Stocks)**

![AI Stock Insights](screenshots/AI-Stock-Insights.png)

**AI Price Suggestion (Stock Pricing)**

![AI Suggest Price](screenshots/AI-Suggest-Price.png)

**AI Product Search**

![AI Product Search](screenshots/AI-Product-Search.png)

**React POS — AI Inventory Insights**

![AI POS Inventory Insights](screenshots/AI-POS-Inventory-Insights.png)

**React POS — AI Price Suggestion**

![AI POS Price Suggestion](screenshots/AI-POS-Price-Suggestion.png)

**AI Sales Forecast**

![AI Sales Forecast](screenshots/AI-Sales-Forecast.png)

</details>

<details>
<summary><strong>Catalog Management</strong> — 7 screenshots</summary>

**All Products**

![All Products](screenshots/All-Products.png)

**Create Product**

![Create Product](screenshots/Create-Product.png)

**Categories**

![Categories](screenshots/Categories.png)

**All Brands**

![All Brands](screenshots/All-Brands.png)

**Sizes**

![Sizes](screenshots/Sizes.png)

**Colors**

![Colors](screenshots/Colors.png)

**Units**

![Units](screenshots/Units.png)

</details>

<details>
<summary><strong>Inventory &amp; Stock</strong> — 6 screenshots</summary>

**Stocks**

![Stocks](screenshots/Stocks.png)

**Add New Stock**

![Add New Stock](screenshots/Add-New-Stock.png)

**New Stock Adjustment**

![New Stock Adjustment](screenshots/New-Stock-Adjustment.png)

**Warehouses**

![Warehouses](screenshots/Warehouses.png)

**Stock Report**

![Stock Report](screenshots/Stock-Report.png)

**Stock Report — Stock by Category**

![Stock Report Stock by Category](screenshots/Stock-Report-Stock-by-Category.png)

</details>

<details>
<summary><strong>Sales &amp; POS</strong> — 4 screenshots</summary>

**All Sales**

![All Sales](screenshots/All-Sales.png)

**Sale Invoice**

![Sale Invoice](screenshots/Sale-Invoice.png)

**Sale Return**

![Sale Return](screenshots/Sale-Return.png)

**Blade POS Terminal**

![Blade POS Terminal](screenshots/POS-Terminal.png)

</details>

<details>
<summary><strong>Customers</strong> — 4 screenshots</summary>

**Customers**

![Customers](screenshots/Customers.png)

**Customer Fund**

![Customer Fund](screenshots/Customer-Fund.png)

**Outstanding Customer Dues**

![Outstanding Customer Dues](screenshots/Outstanding-Customer-Dues.png)

**Customer Report**

![Customer Report](screenshots/Customer-Report.png)

</details>

<details>
<summary><strong>Suppliers</strong> — 6 screenshots</summary>

**Suppliers**

![Suppliers](screenshots/Suppliers.png)

**Create New Supplier**

![Create New Supplier](screenshots/Create-New-Supplier.png)

**Supplier Fund**

![Supplier Fund](screenshots/Supplier-Fund.png)

**Supplier Due &amp; Advances**

![Supplier Due & Advances](screenshots/Supplier-Due-Advances.png)

**Supplier Transaction History**

![Supplier Transaction History](screenshots/Supplier-Transaction-History.png)

**Supplier Report**

![Supplier Report](screenshots/Supplier-Report.png)

</details>

<details>
<summary><strong>Finance &amp; Accounting</strong> — 20 screenshots</summary>

**Accounting Overview**

![Accounting](screenshots/Accounting.png)

**Chart of Accounts**

![Chart of Accounts](screenshots/Accounting-Chart-of-Accounts.png)

**Accounting Journal**

![Accounting Journal](screenshots/Accounting-Journal.png)

**General Ledger**

![General Ledger](screenshots/Accounting-General%20Ledger.png)

**Trial Balance**

![Trial Balance](screenshots/Accounting-Trial-Balance.png)

**Balance Sheet**

![Balance Sheet](screenshots/Accounting-Balance-Sheet.png)

**Profit &amp; Loss**

![Profit & Loss](screenshots/Accounting-Profit-Loss.png)

**Cash Flow**

![Cash Flow](screenshots/Accounting-Cash-Flow.png)

**Reconciliation**

![Reconciliation](screenshots/Accounting-Reconciliation.png)

**Incomes**

![Incomes](screenshots/Incomes.png)

**Income Report**

![Income Report](screenshots/Income-Report.png)

**Expenses**

![Expenses](screenshots/Expenses.png)

**Expense Report**

![Expense Report](screenshots/Expense-Report.png)

**Cashbook**

![Cashbook](screenshots/Cashbook.png)

**Accounts Payable**

![Accounts Payable](screenshots/Accounts-Payable.png)

**Accounts Receivable**

![Accounts Receivable](screenshots/Accounts-Receivable.png)

**Fixed Assets**

![Fixed Assets](screenshots/Fixed-Assets.png)

**VAT Return 1**

![VAT Return 1](screenshots/VAT-Return-1.png)

**VAT Return 2**

![VAT Return 2](screenshots/VAT-Return-2.png)

**Transaction Log**

![Transaction Log](screenshots/Transaction-Log.png)

</details>

<details>
<summary><strong>Procurement &amp; Supply Chain</strong> — 6 screenshots</summary>

**Procurement Needs**

![Procurement Needs](screenshots/Procurement-Needs.png)

**Purchase Orders**

![Purchase Orders](screenshots/Purchase-Orders.png)

**Create Purchase Order**

![Create Purchase Order](screenshots/Create-Purchase-Order.png)

**Add New Shipment**

![Add New Shipment](screenshots/Add-New-Shipment.png)

**Edit Shipment Return**

![Edit Shipment Return](screenshots/Edit-Shipment-Return.png)

**Shipping Zone**

![Shipping Zone](screenshots/Shipping-Zone.png)

</details>

<details>
<summary><strong>Manufacturing</strong> — 4 screenshots</summary>

**Bill of Materials**

![Bill of Materials](screenshots/Bill-of-Materials.png)

**Productions**

![Productions](screenshots/Productions.png)

**Create Production**

![Create Production](screenshots/Create-Production.png)

**Production Plannings**

![Production Plannings](screenshots/Production-Plannings.png)

</details>

<details>
<summary><strong>HRM</strong> — 4 screenshots</summary>

**Employees**

![Employees](screenshots/Employees.png)

**Employee Performance**

![Employee Performance](screenshots/Employee-Performance.png)

**Payrolls**

![Payrolls](screenshots/Payrolls.png)

**Tasks**

![Tasks](screenshots/Tasks.png)

</details>

<details>
<summary><strong>Marketing</strong> — 1 screenshot</summary>

**Campaigns**

![Campaigns](screenshots/Campaigns.png)

</details>

<details>
<summary><strong>Reporting</strong> — 2 screenshots</summary>

**Reports**

![Reports](screenshots/Reports.png)

**Sales Report**

![Sales Report](screenshots/Sales-Report.png)

</details>

<details>
<summary><strong>Ecommerce Storefront</strong> — 5 screenshots</summary>

**Homepage — Hero &amp; Navigation**

![Storefront Homepage 1](screenshots/Ecommerce-Homepage-1.png)

**Homepage — Featured Sections**

![Storefront Homepage 2](screenshots/Ecommerce-Homepage-2..png)

**Product Listing**

![Storefront Product Listing](screenshots/Ecommerce-Product-Listing.png)

**Product Details**

![Storefront Product Details](screenshots/Ecommerce-Product-Details.png)

**AI Shopping Assistant**

![Storefront AI Assistant](screenshots/Ecommerce-AI-Assistant.png)

</details>

<details>
<summary><strong>React POS</strong> — 9 screenshots</summary>

**POS Dashboard**

![React POS Dashboard](screenshots/React-POS-Dashboard.png)

**Categories**

![React POS Categories](screenshots/React-POS-Categories.png)

**Customers**

![React POS Customers](screenshots/React-POS-Customers.png)

**Products**

![React POS Products](screenshots/React-POS-Products.png)

**Stocks**

![React POS Stocks](screenshots/React-POS-Stocks.png)

**Sales**

![React POS Sales](screenshots/React-POS-Sales.png)

**Income**

![React POS Income](screenshots/React-POS-Income.png)

**Expense**

![React POS Expense](screenshots/React-POS-Expense.png)

**Reports**

![React POS Reports](screenshots/React-POS-Reports.png)

</details>

## Project Structure

```
erp-system/
├── laravel/          # Laravel 13 API + admin panel + storefront
├── react-app/        # React 19 POS frontend
├── production/       # Deployment configs, build scripts, Apache configs
├── nginx/            # Nginx reverse proxy config
├── php/              # PHP-FPM Dockerfile
├── documentation/    # Per-module reference documentation (Markdown)
├── screenshots/      # Admin panel, storefront, and POS screenshots
├── docker-compose.yml
└── setup.sh
```

See [laravel/README.md](laravel/README.md) and [react-app/README.md](react-app/README.md) for detailed documentation.

The Business Intelligence service is maintained separately in [shaik-obydullah/bi-system](https://github.com/shaik-obydullah/bi-system); this repository contains the Laravel proxy and admin views that consume it.

## Module Documentation

Per-module reference documentation (schema, models, routes, API surface, business flows, and permissions) lives in [`documentation/`](documentation/):

| Module                         | Document                                                                                             |
| ------------------------------ | ---------------------------------------------------------------------------------------------------- |
| Authentication & Authorization | [documentation/01-authentication-authorization.md](documentation/01-authentication-authorization.md) |
| Administration & System        | [documentation/02-administration-system.md](documentation/02-administration-system.md)               |
| Catalog Management             | [documentation/03-catalog-management.md](documentation/03-catalog-management.md)                     |
| Inventory & Stock              | [documentation/04-inventory-stock.md](documentation/04-inventory-stock.md)                           |
| Sales & POS                    | [documentation/05-sales-pos.md](documentation/05-sales-pos.md)                                       |
| E-Commerce & Storefront        | [documentation/06-ecommerce-storefront.md](documentation/06-ecommerce-storefront.md)                 |
| Financial Management           | [documentation/07-finance-accounting.md](documentation/07-finance-accounting.md)                     |
| Procurement & Supply Chain     | [documentation/08-procurement-supply-chain.md](documentation/08-procurement-supply-chain.md)         |
| Manufacturing                  | [documentation/09-manufacturing.md](documentation/09-manufacturing.md)                               |
| HRM                            | [documentation/10-hrm.md](documentation/10-hrm.md)                                                   |
| Marketing & Campaigns          | [documentation/11-marketing-campaigns.md](documentation/11-marketing-campaigns.md)                   |
| AI & Business Intelligence     | [documentation/12-ai-business-intelligence.md](documentation/12-ai-business-intelligence.md)         |

## Authentication & RBAC

Four independent guards, never mixed:

| Guard      | Audience            | Auth Mechanism       | Middleware                                   |
| ---------- | ------------------- | -------------------- | -------------------------------------------- |
| `admin`    | Admin panel         | Session              | `auth:admin`, `active.admin`, `permission:*` |
| `customer` | Storefront + portal | Session              | `auth:customer`, `active.customer`           |
| `supplier` | Supplier portal     | Session              | `auth:supplier`, `active.supplier`           |
| `api`      | React POS           | Sanctum bearer token | `auth:sanctum`                               |

- **RBAC:** roles → permissions via `role_permissions` (many-to-many). Permission names follow `<module>.<action>` (e.g. `products.view`, `sales.edit`). The `permission` middleware uses `hasAnyPermission()`, so it passes when the admin holds **at least one** of the listed permissions and aborts with 403 only when none match. There is no hard-coded bypass — `super-admin` simply holds all 127 permissions.
- **Seeded roles:** `super-admin` (all 127 permissions), `admin` (all except `roles.delete`), `manager` (30 operational permissions), `cashier` (9 POS-focused permissions).
- **Audit trail:** `App\Services\ActivityLogger` records logins, failed logins, and CRUD mutations with IP and before/after data.
- ⚠️ New admin routes **must** be wrapped in a `permission:` middleware; the admin group only checks `auth:admin` + `active.admin`, so any active admin can reach an unprotected route.

## REST API

Base path `/api/v1` (see `laravel/routes/api.php`) — 33 route definitions (3 public + 30 Sanctum-protected), including auth, POS CRUD, AI, and pull/push sync endpoints.

### Public Endpoints

| Method | Endpoint               | Purpose                                    |
| ------ | ---------------------- | ------------------------------------------ |
| POST   | `/login`               | Sanctum token login (returns Bearer token) |
| GET    | `/ai/status`           | Ollama health + model name                 |
| POST   | `/ai/customer-support` | AI support chat (public storefront)        |

### Protected Endpoints (`auth:sanctum`)

| Method | Endpoint                  | Purpose                                 |
| ------ | ------------------------- | --------------------------------------- |
| POST   | `/logout`                 | Revoke current token                    |
| GET    | `/sync/pull`              | Pull changes for offline/mobile clients |
| POST   | `/sync/push`              | Push local changes from offline clients |
| POST   | `/configuration`          | System settings for POS                 |
| POST   | `/dashboard`              | Category tree for POS                   |
| POST   | `/category-product/{id}`  | In-stock items for a category           |
| POST   | `/customer`               | Customer list                           |
| POST   | `/save-customer`          | Create customer                         |
| PUT    | `/update-customer/{id}`   | Update customer                         |
| POST   | `/category`               | Category list                           |
| POST   | `/save-category`          | Create category                         |
| PUT    | `/update-category/{id}`   | Update category                         |
| GET    | `/product`                | Product list                            |
| POST   | `/save-product`           | Create product                          |
| POST   | `/update-product/{id}`    | Update product                          |
| GET    | `/stock`                  | Stock list                              |
| POST   | `/save-stock`             | Create stock                            |
| GET    | `/sale`                   | Sales list                              |
| POST   | `/select-sale/{id}`       | Sale detail                             |
| POST   | `/save-sale`              | Checkout                                |
| POST   | `/income`                 | Income list                             |
| POST   | `/save-income`            | Create income                           |
| POST   | `/expense`                | Expense list                            |
| POST   | `/save-expense`           | Create expense                          |
| POST   | `/report`                 | YTD vs last-year financial report       |
| POST   | `/ai/product-description` | Generate product description            |
| POST   | `/ai/product-search`      | Natural-language product search         |
| POST   | `/ai/inventory-insights`  | Inventory risk & reorder insights       |
| POST   | `/ai/sales-forecast`      | 12-month revenue forecast               |
| POST   | `/ai/price-suggestion`    | Price range & strategy advice           |

## AI Features

All **generative** AI is self-hosted via `App\Services\OllamaService` (`POST {OLLAMA_URL}/api/chat`). Env vars: `OLLAMA_URL` (default `http://erp_ollama:11434`) and `AI_MODEL` (default `qwen2.5:0.5b`, a tiny model suitable for low-spec machines). All methods return `null` when Ollama is down — **callers must handle null and degrade gracefully**; the React UI should disable AI widgets based on `GET /ai/status`. The status check verifies the configured model is present in `GET /api/tags`, so a running Ollama with no pulled model correctly reports unavailable.

> **Scope:** this section covers the in-app generative AI copilot (Ollama). The deterministic ML/analytics layer is a separate project — see [Business Intelligence](#business-intelligence) and the [bi-system repository](https://github.com/shaik-obydullah/bi-system).

All AI endpoints below are relative to the `/api/v1` base path.

| Feature              | OllamaService Method           | Input                               |
| -------------------- | ------------------------------ | ----------------------------------- |
| Product descriptions | `generateProductDescription()` | name, category, specs               |
| Product search       | `searchProducts()`             | natural-language query + catalog    |
| Inventory insights   | `inventoryInsights()`          | stock data with margins             |
| Sales forecast       | `salesForecast()`              | 12 months of completed-sale revenue |
| Price suggestion     | `suggestPrice()`               | current price + competitor prices   |
| Customer support     | `customerSupport()`            | message + product context           |

## Business Intelligence

The deterministic ML layer is a **separate project**, [shaik-obydullah/bi-system](https://github.com/shaik-obydullah/bi-system), built with Flask, scikit-learn, and Facebook Prophet. It connects read-only to the ERP MariaDB database and is consumed by `BiController` on the admin panel (`/bi`, `/bi/employees`, `/bi/products`, `/bi/recommendations`, `/bi/forecast`, `/bi/prophet-forecast`, `/bi/combos`). The Laravel proxy and Blade views are part of this repository; the Flask app, Nginx config, ML models, and in-depth write-ups live in the BI repository.

The service is internal-only (`bi_nginx:80` → `bi_flask:5000`) on the shared `erp-system_default` Docker network and is **not** included in this repository's `docker-compose.yml`. Every request requires the `X-API-Key` header; set `BI_API_URL` (e.g. `http://bi_nginx:80/api/v1`) and `BI_API_KEY` in the ERP environment. Forecast payloads are cached (`FORECAST_CACHE_TTL`, default 6 h); the BI service exposes `POST /api/v1/internal/refresh` for scheduled recomputation and `GET /api/v1/internal/cache-status` to report cache age. When the service is unavailable, the BI pages fall back to empty states.

| Method   | Endpoint                           | Analytics                                                                                       |
| -------- | ---------------------------------- | ----------------------------------------------------------------------------------------------- |
| POST     | `/api/v1/analyze`                  | KMeans (n=3) + IsolationForest employee performance evaluation                                  |
| GET/POST | `/api/v1/product-analysis`         | KMeans + weighted scoring product performance tiers                                             |
| GET      | `/api/v1/sales-forecast`           | Linear-regression next-month forecast with confidence interval                                  |
| GET      | `/api/v1/product-combos`           | Apriori association-rule market-basket analysis                                                 |
| POST     | `/api/v1/recommendations/products` | 6 strategies (popular, trending, content-based KNN, collaborative, similar users, personalized) |
| GET      | `/api/v1/prophet-forecast`         | Facebook Prophet forecast with seasonality, trend, and confidence intervals                     |

## Financial Integrity

The POS checkout is the canonical transactional flow (`ApiController::saveSale`, `PosController::checkout`): a single sale atomically writes **Sale + SaleDetails + stock decrement + Transaction + Income + Cashbook + journal entry** inside one `DB::beginTransaction()` … `DB::commit()` with rollback on exception.

- **20 transaction types** are defined as constants on `App\Models\Transaction`: `userFund`, `supplierDeposit`, `supplierPayment`, `bomExpense`, `salaryPayment`, `campaignExpense`, `stockIn`, `stockOut`, `saleIncome`, `saleDue`, `fixedAsset`, `income`, `expense`, `miscIncome`, `miscExpense`, `shipmentCost`, `purchase`, `purchaseReturn`, `stockAdjustmentLoss`, `stockAdjustmentGain`.
- **`cashbook` remains the operational money ledger** — every financial event writes a row (`in_amount`, `out_amount`, `amount_payable`, `amount_receivable`).
- **Double-entry layer** — `journal_entries` + `journal_lines` + `accounts` (26 seeded accounts) power the ledger, trial balance, P&L, balance sheet, cash flow, reconciliation, fiscal periods, opening balances, and the UK 9-box VAT return.
- **Automated posting** — `App\Accounting\AccountingPoster` posts balanced entries for sales, dues, sale returns, purchases, stock adjustments, shipment landed costs and returns, payroll, production, fixed assets, campaign spend, customer/supplier funds, imports, and opening balances. `App\Services\LedgerProjector` replays historical simple-layer rows with stable `source_key` values, so `php artisan accounting:backfill` is idempotent.
- **Health checks** — `php artisan accounting:verify` checks unbalanced entries, the trial balance, cash/customer/supplier reconciliation, closed fiscal periods, and source coverage. It runs nightly at 02:00 and is mirrored in the **Books health** widget on `/accounting`.
- **VAT** — `App\Services\VatCalculator` extracts VAT from VAT-inclusive prices per line (standard 20%, reduced 5%, zero, exempt, outside scope), and the VAT return reconciles output/input VAT against `sales_tax_payable` and `input_vat`.
- **Feature flags** — `ACCOUNTING_GL_ENABLED` (default `true`) pauses new postings without touching cashbook/POS data; `ACCOUNTING_GL_ENFORCED` (default `false`) makes ledger errors block the underlying operation instead of being logged.
- **Any new money-moving or stock-moving operation must follow the same transactional pattern** and verify ledger rows are written and stock is decremented exactly once.

## Quick Start

### Prerequisites

- Docker Engine 24+ with Docker Compose v2
- Git
- ~4 GB free RAM if you want to run the Ollama model locally (the rest of the stack runs without it)
- For the native (non-Docker) path: PHP 8.3+, Composer, Node.js 22+, and npm

```bash
# Clone
git clone <repo-url>
cd erp-system

# Laravel environment (Docker Compose provides MariaDB)
cp laravel/.env.example laravel/.env
# set DB_CONNECTION=mysql, DB_HOST=erp_db, DB_DATABASE=laravel_db,
# DB_USERNAME=laravel, DB_PASSWORD=secret in laravel/.env

# Start the stack
docker compose up -d --build

# Install, initialise and seed (includes the chart of accounts)
docker compose exec erp_laravel composer install
docker compose exec erp_laravel php artisan key:generate
docker compose exec erp_laravel php artisan migrate --seed
```

If host port `3309` is already in use, start with the local override:

```bash
docker compose -f docker-compose.yml -f docker-compose.local.yml up -d --build
```

Or run the Laravel app natively (from `laravel/`):

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate --seed
npm install && npm run build
```

### Default Credentials

```
Email:    admin@erp.com
Password: password
```

These are created by `DatabaseSeeder` (`Admin::firstOrCreate`) and the super-admin role is assigned by `PermissionSeeder`.

### Optional AI Model

Ollama must have the configured model pulled before AI features work:

```bash
docker compose exec erp_ollama ollama pull qwen2.5:0.5b
```

`GET /api/v1/ai/status` reports `available: false` until the model is installed.

### Services

| Service         | Port  | URL                   |
| --------------- | ----- | --------------------- |
| Laravel (Nginx) | 8084  | http://localhost:8084 |
| Laravel (Vite)  | 5174  | http://localhost:5174 |
| React POS       | 3060  | http://localhost:3060 |
| phpMyAdmin      | 8083  | http://localhost:8083 |
| MariaDB         | 3309  | localhost:3309        |
| Ollama AI       | 11435 | localhost:11435       |

### Configuration

Key variables in `laravel/.env`:

| Variable                 | Recommended (Docker)        | Purpose                                                           |
| ------------------------ | --------------------------- | ----------------------------------------------------------------- |
| `APP_URL`                | `http://localhost`          | Public base URL                                                   |
| `DB_CONNECTION`          | `mysql`                     | Database driver (the `.env.example` default is `sqlite`)          |
| `DB_HOST`                | `erp_db`                    | MariaDB service name on the Docker network                        |
| `DB_PORT`                | `3306`                      | MariaDB port inside Docker                                        |
| `DB_DATABASE`            | `laravel_db`                | Database name                                                     |
| `DB_USERNAME`            | `laravel`                   | Database user                                                     |
| `DB_PASSWORD`            | `secret`                    | Database password (change in production)                          |
| `OLLAMA_URL`             | `http://erp_ollama:11434`   | Ollama API base URL                                               |
| `AI_MODEL`               | `qwen2.5:0.5b`              | Model used for generative AI (tiny default for low-spec machines) |
| `BI_API_URL`             | `http://bi_nginx:80/api/v1` | BI service base URL                                               |
| `BI_API_KEY`             | —                           | Must match the BI service's `BI_API_KEY`                          |
| `ACCOUNTING_GL_ENABLED`  | `true`                      | Pause or resume automatic ledger postings                         |
| `ACCOUNTING_GL_ENFORCED` | `false`                     | Block operations when ledger posting fails                        |

Run `php artisan key:generate` after copying the env file; `APP_KEY` must be set before the app boots.

## Development Workflow

| Command                                      | Purpose                                                               |
| -------------------------------------------- | --------------------------------------------------------------------- |
| `composer run dev`                           | Parallel: `php artisan serve`, queue listen, pail logs, `npm run dev` |
| `composer test`                              | Run PHPUnit tests                                                     |
| `./vendor/bin/pint`                          | PSR-12 code style (Laravel Pint)                                      |
| `php artisan migrate` / `db:seed`            | Schema / seeders                                                      |
| `php artisan accounting:backfill`            | Replay legacy simple-layer rows into the ledger (idempotent)          |
| `php artisan accounting:verify`              | Check ledger balance, reconciliation, and source coverage             |
| `npm run build` (laravel/)                   | Compile Tailwind + JS admin assets                                    |
| `npm run dev` / `npm run build` (react-app/) | React POS dev server (3060) / production build                        |

## Testing

PHPUnit tests live under `laravel/tests/Feature` and `laravel/tests/Unit`. Coverage includes auth, RBAC, activity logging, accounting posting and VAT, ledger reconciliation, deletion guards, settings, and POS/storefront checkout. Run them with:

```bash
composer test
```

## Production Deployment

```bash
cd production
./deploy.sh
```

Generates `laravel-production.tar.gz` and `react-production.tar.gz` for Apache-based shared hosting. After deploying, cache the config and routes:

```bash
php artisan config:cache && php artisan route:cache && php artisan view:cache
```

On the first deploy over existing cashbook/POS data, populate the double-entry ledger once and verify it:

```bash
php artisan accounting:backfill
php artisan accounting:verify
```

For the React app, `.env` sets `VITE_API_URL=/api/v1` for local dev (Vite proxies `/api` to `erp_nginx`). Production builds use `production/react-app/.env.production`, which sets `VITE_API_URL=https://erp.obydullah.com/api/v1`. There is no `VITE_API_URL_PRODUCTION` variable.

## Database

77 migrated tables (85 migration files; 78 total in the live database including the `migrations` table) organized into modules: **Auth** (admins, customers, suppliers, roles, permissions, sessions, tokens), **Catalog** (products, categories, brands, sizes, colors, units, reviews, flash_sales), **Inventory** (stocks, warehouses, stock_adjustments), **Sales** (sales, sale_details, sale_returns, cart, cart_details), **Finance & Accounting** (transactions, cashbook, incomes, expenses, payable, receivable, balances, currencies, accounts, journal_entries, journal_lines, fiscal_periods, opening_balances, fixed_assets), **Procurement** (needs, purchase_orders, shipments, shipment_returns, shipment_costs, shipping_zones, shipping_methods), **Manufacturing** (bill_of_materials, production_plannings, productions), **HRM** (employees, payrolls, task_management), **System** (activities, notifications, configurations, contents, campaigns, newsletter_subscribers). Framework tables (`cache`, `jobs`, `sessions`, password resets, personal access tokens) sit alongside these domain tables.

- Most tables use soft deletes and track `created_by` / `updated_by` / `deleted_by` where applicable.

See [laravel/README.md](laravel/README.md#database-schema) for the full schema.

## License

[MIT License](LICENSE)
