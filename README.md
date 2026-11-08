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
- [Architecture](#architecture)
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

## Architecture

> Full deep-dive (services, data flow, AI/BI layers, security, deployment): [docs/erp-architecture.md](docs/erp-architecture.md)

### System Context

The system is a **monolithic backend with decoupled frontends**, replacing five-plus disconnected tools (spreadsheets, separate POS, offline accounting) with one platform.

```
                    ┌──────────────────────────────────────────────┐
                    │                 DNS / Domains                │
                    │   erp.obydullah.com   react-pos.obydullah.com│
                    └───────────────────┬──────────────────────────┘
                                        │
                    ┌───────────────────▼──────────────────────────┐
                    │            Web Server (Nginx/Apache)         │
                    │   serves static assets + reverse proxies API │
                    └───────────────────┬──────────────────────────┘
                                        │
        ┌───────────────────────────────▼───────────────────────────────┐
        │                    LARAVEL MONOLITH (PHP 8.3)                 │
        │                                                               │
        │   ┌───────────────┐  ┌───────────────┐  ┌────────────────────┐│
        │   │ Admin Panel   │  │  Storefront   │  │  REST API (/api/v1)││
        │   │ (Blade+Vue+   │  │ (Blade +      │  │  Sanctum protected ││
        │   │  Alpine.js)   │  │  Vue/Alpine)  │  │  + Customer/       ││
        │   │               │  │               │  │  Supplier portals  ││
        │   └───────────────┘  └───────────────┘  └───────────────────┘ │
        │                              │                                │
        │   Controllers → Services → Eloquent Models → DB::transaction()│
        └──────────────┬────────────────────┬──────────────────┬────────┘
                       │                    │                  │
          ┌────────────▼──────┐   ┌─────────▼───────┐  ┌───────▼──────────┐
          │  MariaDB (10.6)   │   │  Ollama AI      │  │  Flask BI        │
          │  77 tables ·      │   │  qwen2.5:0.5b   │  │  scikit-learn ·  │
          │  single source    │   │  port 11434     │  │  port 5000       │
          │  of truth         │   │  (self-hosted,  │  │  (run separately)│
          └───────────────────┘   │  zero API cost) │  └──────────────────┘
                                  └─────────────────┘
```

**Design principles:**

- **One source of truth** — every frontend (admin, storefront, React POS) reads/writes the same MariaDB via the Laravel API layer. No duplicated business logic in frontends.
- **Server-side integrity** — all multi-table mutations (sales, returns, payments) run inside `DB::transaction()` with automatic rollback.
- **Self-hosted intelligence** — Ollama runs alongside the backend in Docker, and the optional Flask BI service is deployed separately; customer data is never sent to third-party AI APIs.
- **Guard-per-persona** — admins, customers, and suppliers authenticate with independent guards; machines (React POS) use Sanctum tokens.

### Components

| Component                            | Role                             | Consumer                                       |
| ------------------------------------ | -------------------------------- | ---------------------------------------------- |
| **Laravel API + Admin + Storefront** | Monolithic application server    | Admin browsers, storefront browsers, React POS |
| **React 19 POS**                     | Standalone POS terminal (SPA)    | Cashiers                                       |
| **MariaDB**                          | Relational database (77 tables)  | Laravel                                        |
| **Ollama (qwen2.5:0.5b)**            | Self-hosted LLM inference        | Laravel (via HTTP `/api/chat`)                 |
| **Flask BI**                         | Separate ML analytics service ([bi-system](https://github.com/shaik-obydullah/bi-system)) | Laravel (via HTTP proxy) |
| **phpMyAdmin**                       | DB admin UI (dev only)           | Developers                                     |
| **Nginx**                            | Reverse proxy + static server    | Public traffic                                 |

**Communication flows:**

| Flow                       | Transport                              | Direction         |
| -------------------------- | -------------------------------------- | ----------------- |
| React POS → Laravel API    | HTTPS + Bearer token (`auth:sanctum`)  | POS → Backend     |
| Laravel → MariaDB          | PDO/MySQL                              | Backend → DB      |
| Laravel → Ollama           | HTTP `POST /api/chat`, `GET /api/tags` | Backend → AI      |
| Laravel → Flask BI         | HTTP `bi_nginx:80/api/v1/*` + `X-API-Key` | Backend → BI   |
| Admin/Storefront → Laravel | HTTPS + session cookie                 | Browser → Backend |

> **Note:** Ollama and Flask BI are not exposed through the public web server; only the Laravel backend reaches them over the internal Docker network. Ollama's host port (`11435`) is bound for local development only. The React POS talks exclusively to the Laravel API.

### Services & Ports (Dev — Docker Compose)

| Service          | Image                 | Host Port | Purpose                                  |
| ---------------- | --------------------- | --------- | ---------------------------------------- |
| `erp_laravel`    | PHP 8.3-FPM (custom)  | 5174      | Laravel + Vite dev server; builds assets |
| `erp_nginx`      | nginx:stable-alpine   | 8084      | Reverse proxy → PHP-FPM                  |
| `erp_db`         | mariadb:10.6          | 3309      | Database (`laravel_db`)                  |
| `erp_react`      | node:22-alpine        | 3060      | React POS Vite dev server                |
| `erp_ollama`     | alpine/ollama         | 11435     | AI inference (2-core CPU limit)          |
| `erp_phpmyadmin` | phpmyadmin/phpmyadmin | 8083      | DB admin UI                              |

Persistent volumes: `erp_db_data` (database), `ollama_data` (AI models). The optional `docker-compose.local.yml` removes the host `3309` binding when another local project already uses it; containers still reach MariaDB over the internal Docker network.

### Frontend Layers

- **Admin panel** — Blade + Vue 3 + Alpine.js, Tailwind, Chart.js; permission-gated sidebar, responsive down to mobile, an in-admin Blade POS, and a full accounting suite.
- **Storefront & portals** — public catalog/cart/checkout (customer guard), product quick view, wishlist, reviews, newsletter subscription, CMS pages; customer order portal; read-only supplier portal for purchase orders and products (supplier guard).
- **React POS (SPA)** — React 19, Vite 6, Tailwind, React Router, Axios (single instance + bearer-token interceptor), Chart.js, jsPDF; 300 ms debounced search, cart persisted to localStorage, PDF invoices.

### Backend Layer (Laravel)

Layering rules: **Routes** map URL → controller (middleware gating here) → **Controllers** validate + orchestrate → **Services** (ActivityLogger, NotificationHelper, OllamaService, LedgerProjector, ShipmentCostService, VatCalculator) hold cross-cutting logic → **Models** own relationships/scopes/casts → **`DB::transaction()`** wraps any multi-table mutation. The double-entry engine is isolated under `app/Accounting` (`AccountingPoster`, `LedgerService`, `ChartOfAccounts`, `Reconciliation`); `AccountingPoster` is the single entry point that turns an operational event into a balanced journal entry.

Route files: `web.php` (admin + storefront, permission-gated), `api.php` (Sanctum REST + AI), `customer.php`, `supplier.php`, `auth.php`.

### Authentication & Authorization

- **Five guards:** `web` (Laravel default, user provider) plus the four application guards — `admin` / `customer` / `supplier` (session) and `sanctum` (bearer token for React POS).
- **RBAC:** 4 seeded roles (`super-admin`, `admin`, `manager`, `cashier`), 127 `module.action` permissions across 34 groups, `permission:` middleware on many routes (several resource CRUD groups rely on admin auth + menu visibility). There is no hard-coded super-admin bypass — `super-admin` simply holds all 127 permissions.
- **Middleware:** RoleMiddleware, PermissionMiddleware, Active\*Middleware, MaintenanceModeMiddleware.

### AI Layer (Ollama)

Single gateway `app/Services/OllamaService.php` → `http://erp_ollama:11434` with model `qwen2.5:0.5b` by default (a tiny model for low-spec machines), configurable temperature, 120 s timeout, exception-safe. `isAvailable()` calls `GET /api/tags` and confirms the configured model (e.g. `qwen2.5:0.5b` or `qwen2.5:0.5b-instruct-q4_K_M`) is actually installed, so the status endpoint does not report a false positive when Ollama is running with no models pulled.

| Capability              | Endpoint (`/api/v1`)                 | Temperature |
| ----------------------- | ------------------------------------ | ----------- |
| Product descriptions    | `POST /ai/product-description`       | 0.8         |
| Natural-language search | `POST /ai/product-search`            | 0.3         |
| Inventory insights      | `POST /ai/inventory-insights`        | 0.4         |
| Sales forecast          | `POST /ai/sales-forecast`            | 0.5         |
| Customer support        | `POST /ai/customer-support` (public) | 0.6         |
| Price suggestion        | `POST /ai/price-suggestion`          | 0.5         |

> The AI is **generative** (text/insights). For deterministic analytics the system delegates to the Flask BI service.

### BI Layer (Flask)

`BiController` proxies to a separately deployed Python/Flask service maintained in the [shaik-obydullah/bi-system](https://github.com/shaik-obydullah/bi-system) repository (30 s timeout). The Flask app, Nginx proxy, scikit-learn/Prophet pipelines, and trained models live there; this repository contains the Laravel proxy, admin views, and `BI_API_URL` / `BI_API_KEY` config. The service is **not** part of the default `docker-compose.yml`; it runs internal-only (`bi_nginx:80` → `bi_flask:5000`) on the `erp-system_default` Docker network, and every request requires the `X-API-Key` header. Admin views live under `/bi/*` behind the admin guard (`auth:admin` + `active.admin`); if the BI service is unavailable they render empty states rather than failing.

Forecast payloads are cached (`FORECAST_CACHE_TTL`, default 6 h) in the BI service, which exposes `POST /api/v1/internal/refresh` for scheduled recomputation and `GET /api/v1/internal/cache-status` to report cache age. Configure `BI_API_URL` (e.g. `http://bi_nginx:80/api/v1`) and `BI_API_KEY` in `laravel/.env`. See [Business Intelligence](#business-intelligence) for the full endpoint table and admin views.

### Data Layer

77 migrated tables (85 migration files; 78 total in the live database including `migrations`) across 9 modules (Auth, Catalog, Inventory, Sales, Finance & Accounting, Procurement, Manufacturing, HRM, System), plus framework tables (`cache`, `jobs`, sessions, password resets, personal access tokens). Conventions: `created_by`/`updated_by`/`deleted_by`, soft deletes, `fk_`-prefixed foreign keys. Every financially-significant mutation is atomic — e.g. POS checkout wraps sale + sale_details + stock decrement + transaction/income + cashbook entry in one `DB::transaction()`.

The ledger ships with a 26-account chart of accounts (`config/accounting.php`, seeded by `ChartOfAccountsSeeder`). Posting is enabled by default (`ACCOUNTING_GL_ENABLED=true`); set `ACCOUNTING_GL_ENFORCED=true` to make ledger failures block the underlying operation, or leave it `false` (default) to log and continue.

### Security

HTTPS · 5 guards · permission middleware on gated routes · CSRF · Blade XSS escaping · image-only uploads · full audit trail with IP capture · `.env` secrets · soft deletes with `deleted_by`.

### Deployment

- **Dev:** `docker compose up` — Laravel (Vite 5174 / nginx 8084), MariaDB (3309), React POS (3060), Ollama (11435), phpMyAdmin (8083).
- **Prod (Hostinger):** Laravel via `production/laravel-production.tar.gz` (Apache + cron queue worker), React POS as static build with `VITE_API_URL=https://erp.obydullah.com/api/v1`.

## Tech Stack

| Layer      | Technology                                       |
| ---------- | ------------------------------------------------ |
| Backend    | Laravel 13.17, PHP 8.3, Sanctum Auth             |
| Frontend   | React 19, Vite 6, Tailwind CSS 3                 |
| Storefront | Laravel Blade, Vue.js 3, Alpine.js 3             |
| Database   | MariaDB 10.6 (77 migrated tables)                |
| AI         | Ollama (qwen2.5:0.5b) — self-hosted LLM          |
| BI         | Flask, scikit-learn (separate service)           |
| Infra      | Docker, Nginx, Apache                            |
| PDF        | jsPDF + jspdf-autotable                          |
| Charts     | Chart.js 4 + react-chartjs-2                     |

## Key Modules

| Module              | Description                                                                           |
| ------------------- | ------------------------------------------------------------------------------------- |
| **POS Terminal**    | Category browsing, cart, checkout, VAT/tax/discount, PDF invoices                     |
| **Product Catalog** | Categories, brands, units, sizes, colors, image upload                                |
| **Inventory**       | Multi-warehouse stock with batch/lot tracking                                         |
| **Sales**           | Full lifecycle — sale creation, returns, invoice generation                           |
| **Finance**         | Income/expense tracking, double-entry transactions, cashbook, multi-currency, receivables/payables, fixed assets |
| **Accounting**      | Journal entries & lines, 26-account chart, ledger, trial balance, P&L, balance sheet, cash flow, reconciliation, fiscal periods, opening balances, automated posting, UK VAT return |
| **VAT**             | Stock-level VAT codes/rates, VAT-inclusive pricing, per-line extraction, 9-box return reconciled to the ledger |
| **Data Tools**      | CSV import/export (products, customers, suppliers, catalog lookups, party transactions), barcode sheets, product media uploads |
| **Procurement**     | Need → Purchase Order → Shipment → Returns pipeline, shipping zones/methods, landed shipment costs |
| **Manufacturing**   | Bill of Materials, production planning, cost tracking                                 |
| **HRM**             | Employees, payroll, task management                                                   |
| **Ecommerce**       | Multi-vendor storefront with cart, checkout, customer/supplier portals, reviews, newsletter subscriptions |
| **RBAC**            | 4 seeded roles, 127 permissions across 34 groups                                      |
| **AI**              | Product descriptions, inventory insights, sales forecasting, pricing, support chatbot |
| **Reporting**       | Sales trends, income vs expenses, profit analysis, annual performance charts          |

## Screenshots

A few highlights first; expand any group for the full gallery.

<table>
  <tr>
    <td align="center" width="33%"><img src="screenshots/ERP_Login.png" alt="Login" width="100%"/><br/><sub><strong>Login</strong></sub></td>
    <td align="center" width="33%"><img src="screenshots/ERP_Dashboard.png" alt="Dashboard" width="100%"/><br/><sub><strong>Dashboard</strong></sub></td>
    <td align="center" width="33%"><img src="screenshots/POS.png" alt="Blade POS" width="100%"/><br/><sub><strong>Blade POS</strong></sub></td>
  </tr>
</table>

<details>
<summary><strong>Authentication &amp; System</strong> — 5 screenshots</summary>

**Settings**

![Settings](screenshots/ERP_Settings.png)

**Roles & Permissions**

![Roles & Permissions](screenshots/Role_Permission.png)

**Permissions**

![Permissions](screenshots/Permissions.png)

**Maintenance Mode**

![Maintenance Mode](screenshots/Maintenance_Mode.png)

**Activity Manager**

![Activity Manager](screenshots/Activity_Manager.png)

</details>

<details>
<summary><strong>Catalog Management</strong> — 6 screenshots</summary>

**Products**

![Products](screenshots/Products.png)

**Categories**

![Categories](screenshots/Categories.png)

**Brands**

![Brands](screenshots/Brands.png)

**Sizes**

![Sizes](screenshots/Sizes.png)

**Colors**

![Colors](screenshots/Colors.png)

**Units**

![Units](screenshots/Units.png)

</details>

<details>
<summary><strong>Inventory &amp; Stock</strong> — 5 screenshots</summary>

**Stocks**

![Stocks](screenshots/Stocks.png)

**Stock Add**

![Stock Add](screenshots/Stock_Add.png)

**Stock Adjustments**

![Stock Adjustments](screenshots/Stock_Adjustments.png)

**New Stock Adjustment**

![New Stock Adjustment](screenshots/New_Stock_Adjustment.png)

**Warehouses**

![Warehouses](screenshots/Warehouses.png)

</details>

<details>
<summary><strong>Sales &amp; POS</strong> — 2 screenshots</summary>

**Sales**

![Sales](screenshots/Sales.png)

**Sales Return**

![Sales Return](screenshots/Sales_Return.png)

</details>

<details>
<summary><strong>Customers</strong> — 5 screenshots</summary>

**Customers**

![Customers](screenshots/Customers.png)

**Add Customer**

![Add Customer](screenshots/Add_Customer.png)

**Customer Fund**

![Customer Fund](screenshots/Customer_Fund.png)

**Customer Transactions**

![Customer Transactions](screenshots/Customer_Transactions.png)

**Outstanding Customer Dues**

![Outstanding Customer Dues](screenshots/Outstanding_Customer_Dues.png)

</details>

<details>
<summary><strong>Suppliers</strong> — 3 screenshots</summary>

**Suppliers**

![Suppliers](screenshots/Suppliers.png)

**Supplier Due**

![Supplier Due](screenshots/Supplier_Due.png)

**Supplier Transactions**

![Supplier Transactions](screenshots/Supplier_Transactions.png)

</details>

<details>
<summary><strong>Finance &amp; Accounting</strong> — 8 screenshots</summary>

**Income**

![Income](screenshots/Income.png)

**Expense**

![Expense](screenshots/Expense.png)

**Transactions Log**

![Transactions Log](screenshots/Transactions_Log.png)

**Cashbook**

![Cashbook](screenshots/Cashbook.png)

**Account Payable**

![Account Payable](screenshots/Account_Payable.png)

**Account Receivable**

![Account Receivable](screenshots/Account_Receivable.png)

**Currencies**

![Currencies](screenshots/Currencies.png)

**Fixed Assets**

![Fixed Assets](screenshots/Fixed_Assets.png)

</details>

<details>
<summary><strong>Procurement &amp; Supply Chain</strong> — 3 screenshots</summary>

**Procurement Needs**

![Procurement Needs](screenshots/Procurement_Needs.png)

**Create Purchase Order**

![Create Purchase Order](screenshots/Create_Purchase_Order.png)

**Shipments**

![Shipments](screenshots/Shipments.png)

</details>

<details>
<summary><strong>Manufacturing</strong> — 2 screenshots</summary>

**Bill of Materials**

![Bill of Materials](screenshots/Bill_of_Materials.png)

**Production Planning**

![Production Planning](screenshots/Production_Planning.png)

</details>

<details>
<summary><strong>HRM</strong> — 3 screenshots</summary>

**Employees**

![Employees](screenshots/Employees.png)

**Payrolls**

![Payrolls](screenshots/Payrolls.png)

**Tasks**

![Tasks](screenshots/Tasks.png)

</details>

<details>
<summary><strong>Marketing &amp; Content</strong> — 2 screenshots</summary>

**Campaigns**

![Campaigns](screenshots/Campaigns.png)

**Content**

![Content](screenshots/Content.png)

</details>

<details>
<summary><strong>Reporting</strong> — 2 screenshots</summary>

**Reports**

![Reports](screenshots/Reports.png)

**Supplier Report**

![Supplier Report](screenshots/Supplier_Report.png)

</details>

<details>
<summary><strong>Ecommerce Storefront</strong> — 9 screenshots</summary>

**Homepage**

![Homepage](screenshots/Ecommerce/E_Commerce_Homepage.png)

**Featured Products**

![Featured Products](screenshots/Ecommerce/E_Commerce_Featured_Products.png)

**New Arrivals**

![New Arrivals](screenshots/Ecommerce/E_Commerce_New_Arrivals.png)

**Homepage Footer**

![Homepage Footer](screenshots/Ecommerce/Homepage_Footer.png)

**Product Listing**

![Product Listing](screenshots/Ecommerce/Product_Listing.png)

**Product Details**

![Product Details](screenshots/Ecommerce/Product_Details.png)

**Related Products**

![Related Products](screenshots/Ecommerce/Related_Products.png)

**Vendor List**

![Vendor List](screenshots/Ecommerce/Vendor_List.png)

**Vendor Page**

![Vendor Page](screenshots/Ecommerce/Vendor_Page.png)

</details>

<details>
<summary><strong>React POS</strong> — 9 screenshots</summary>

**POS Terminal**

![POS](react-app/screenshots/POS.png)

**Categories**

![Categories](react-app/screenshots/Categories.png)

**Customers**

![Customers](react-app/screenshots/Customers.png)

**Products**

![Products](react-app/screenshots/Products.png)

**Stocks**

![Stocks](react-app/screenshots/Stocks.png)

**Sales**

![Sales](react-app/screenshots/Sales.png)

**Incomes**

![Incomes](react-app/screenshots/Incomes.png)

**Expense**

![Expense](react-app/screenshots/Expense.png)

**Reports**

![Reports](react-app/screenshots/Reports.png)

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
├── docs/             # Case study, feature list, full documentation (md + html), academic papers
├── docker-compose.yml
└── setup.sh
```

See [laravel/README.md](laravel/README.md) and [react-app/README.md](react-app/README.md) for detailed documentation.

The Business Intelligence service is maintained separately in [shaik-obydullah/bi-system](https://github.com/shaik-obydullah/bi-system); this repository contains the Laravel proxy and admin views that consume it.

## Module Documentation

The complete documentation is available as a single page, covering every module with schema, models, routes, API surface, business flows, permissions, and screenshots:

- [**docs/erp-architecture.md**](docs/erp-architecture.md) — comprehensive system architecture (services, data flow, AI/BI layers, security, deployment)
- [**docs/erp-documentation.md**](docs/erp-documentation.md) — Markdown version (readable directly on GitHub)
- [**docs/erp-documentation.html**](docs/erp-documentation.html) — styled HTML version
- [**docs/case-study.html**](docs/case-study.html) — narrative case study (problem, architecture, accounting, AI/BI, results)

Individual per-module reference docs (schema, models, routes, API surface, business flows, and permissions) also live in [`documentation/`](documentation/):

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

| Method | Endpoint                        | Purpose                                 |
| ------ | ------------------------------- | --------------------------------------- |
| POST   | `/logout`                       | Revoke current token                    |
| GET    | `/sync/pull`                    | Pull changes for offline/mobile clients |
| POST   | `/sync/push`                    | Push local changes from offline clients |
| POST   | `/configuration`                | System settings for POS                 |
| POST   | `/dashboard`                    | Category tree for POS                   |
| POST   | `/category-product/{id}`        | In-stock items for a category           |
| POST   | `/customer`                     | Customer list                           |
| POST   | `/save-customer`                | Create customer                         |
| PUT    | `/update-customer/{id}`         | Update customer                         |
| POST   | `/category`                     | Category list                           |
| POST   | `/save-category`                | Create category                         |
| PUT    | `/update-category/{id}`         | Update category                         |
| GET    | `/product`                      | Product list                            |
| POST   | `/save-product`                 | Create product                          |
| POST   | `/update-product/{id}`          | Update product                          |
| GET    | `/stock`                        | Stock list                              |
| POST   | `/save-stock`                   | Create stock                            |
| GET    | `/sale`                         | Sales list                              |
| POST   | `/select-sale/{id}`             | Sale detail                             |
| POST   | `/save-sale`                    | Checkout                                |
| POST   | `/income`                       | Income list                             |
| POST   | `/save-income`                  | Create income                           |
| POST   | `/expense`                      | Expense list                            |
| POST   | `/save-expense`                 | Create expense                          |
| POST   | `/report`                       | YTD vs last-year financial report       |
| POST   | `/ai/product-description`       | Generate product description            |
| POST   | `/ai/product-search`            | Natural-language product search         |
| POST   | `/ai/inventory-insights`        | Inventory risk & reorder insights       |
| POST   | `/ai/sales-forecast`            | 12-month revenue forecast               |
| POST   | `/ai/price-suggestion`          | Price range & strategy advice           |

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

| Service         | Port  | URL                    |
| --------------- | ----- | ---------------------- |
| Laravel (Nginx) | 8084  | http://localhost:8084  |
| Laravel (Vite)  | 5174  | http://localhost:5174  |
| React POS       | 3060  | http://localhost:3060  |
| phpMyAdmin      | 8083  | http://localhost:8083  |
| MariaDB         | 3309  | localhost:3309         |
| Ollama AI       | 11435 | localhost:11435        |

### Configuration

Key variables in `laravel/.env`:

| Variable | Recommended (Docker) | Purpose |
| -------- | -------------------- | ------- |
| `APP_URL` | `http://localhost` | Public base URL |
| `DB_CONNECTION` | `mysql` | Database driver (the `.env.example` default is `sqlite`) |
| `DB_HOST` | `erp_db` | MariaDB service name on the Docker network |
| `DB_PORT` | `3306` | MariaDB port inside Docker |
| `DB_DATABASE` | `laravel_db` | Database name |
| `DB_USERNAME` | `laravel` | Database user |
| `DB_PASSWORD` | `secret` | Database password (change in production) |
| `OLLAMA_URL` | `http://erp_ollama:11434` | Ollama API base URL |
| `AI_MODEL` | `qwen2.5:0.5b` | Model used for generative AI (tiny default for low-spec machines) |
| `BI_API_URL` | `http://bi_nginx:80/api/v1` | BI service base URL |
| `BI_API_KEY` | — | Must match the BI service's `BI_API_KEY` |
| `ACCOUNTING_GL_ENABLED` | `true` | Pause or resume automatic ledger postings |
| `ACCOUNTING_GL_ENFORCED` | `false` | Block operations when ledger posting fails |

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
- A development database dump lives at [`u181095087_erp.sql`](u181095087_erp.sql).

See [laravel/README.md](laravel/README.md#database-schema) for the full schema.

## License

[MIT License](LICENSE)
