# ERP System

AI-powered full-stack ERP and multi-vendor marketplace system featuring a Laravel API, standalone React POS terminal, Blade/Vue storefront, and intelligent automation with predictive analytics for smarter business operations.

## Tech Stack Badges

![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![React](https://img.shields.io/badge/React-19-61DAFB?style=for-the-badge&logo=react&logoColor=black)
![Vue](https://img.shields.io/badge/Vue-3-4FC08D?style=for-the-badge&logo=vue.js&logoColor=white)
![Alpine.js](https://img.shields.io/badge/Alpine.js-3-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=black)
![MariaDB](https://img.shields.io/badge/MariaDB-11-003545?style=for-the-badge&logo=mariadb&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind%20CSS-3-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)
![Vite](https://img.shields.io/badge/Vite-6-646CFF?style=for-the-badge&logo=vite&logoColor=white)
![Ollama](https://img.shields.io/badge/Ollama-llama3.2-000000?style=for-the-badge&logo=ollama&logoColor=white)
![Python](https://img.shields.io/badge/Python-3.10-3776AB?style=for-the-badge&logo=python&logoColor=white)
![Flask](https://img.shields.io/badge/Flask-2.3-000000?style=for-the-badge&logo=flask&logoColor=white)
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
- [Common Gotchas](#common-gotchas)
- [License](#license)

## Live Demo

- **Admin Panel:** [erp.obydullah.com](https://erp.obydullah.com)
- **React POS:** [react-pos.obydullah.com](https://react-pos.obydullah.com)

## Feature Highlights

- **Two POS terminals** — an in-admin Blade POS and a standalone React 19 terminal, both writing to one transactional ledger.
- **Multi-vendor ecommerce** — storefront, cart, checkout (billing + shipping), customer portal, supplier portal, reviews, flash sales.
- **Self-hosted AI (zero API cost)** — product descriptions, natural-language search, inventory insights, sales forecast, price suggestions, and a support chatbot powered by Ollama (llama3.2).
- **ML Business Intelligence** — Flask microservice with KMeans clustering, IsolationForest anomaly detection, recommendations, association-rule combos, and forecasting.
- **Double-entry cashbook** — every financial event writes a typed transaction + cashbook row atomically inside `DB::transaction()`.
- **Granular RBAC** — 4 seeded roles and 120 `module.action` permissions across 32 groups, with a super-admin bypass.
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
          │  55+ tables ·     │   │  llama3.2       │  │  scikit-learn ·  │
          │  single source    │   │  port 11434     │  │  Prophet · 5000  │
          │  of truth         │   │  (self-hosted,  │  │  (ML analytics)  │
          └───────────────────┘   │  zero API cost) │  └──────────────────┘
                                  └─────────────────┘
```

**Design principles:**

- **One source of truth** — every frontend (admin, storefront, React POS) reads/writes the same MariaDB via the Laravel API layer. No duplicated business logic in frontends.
- **Server-side integrity** — all multi-table mutations (sales, returns, payments) run inside `DB::transaction()` with automatic rollback.
- **Self-hosted intelligence** — Ollama and the Flask BI service run in-process with the backend so customer data never leaves the server.
- **Guard-per-persona** — admins, customers, and suppliers authenticate with independent guards; machines (React POS) use Sanctum tokens.

### Components

| Component                            | Role                             | Consumer                                       |
| ------------------------------------ | -------------------------------- | ---------------------------------------------- |
| **Laravel API + Admin + Storefront** | Monolithic application server    | Admin browsers, storefront browsers, React POS |
| **React 19 POS**                     | Standalone POS terminal (SPA)    | Cashiers                                       |
| **MariaDB**                          | Relational database (55+ tables) | Laravel                                        |
| **Ollama (llama3.2)**                | Self-hosted LLM inference        | Laravel (via HTTP `/api/chat`)                 |
| **Flask BI**                         | ML analytics microservice        | Laravel (via HTTP proxy)                       |
| **phpMyAdmin**                       | DB admin UI (dev only)           | Developers                                     |
| **Nginx**                            | Reverse proxy + static server    | Public traffic                                 |

**Communication flows:**

| Flow                       | Transport                              | Direction         |
| -------------------------- | -------------------------------------- | ----------------- |
| React POS → Laravel API    | HTTPS + Bearer token (`auth:sanctum`)  | POS → Backend     |
| Laravel → MariaDB          | PDO/MySQL                              | Backend → DB      |
| Laravel → Ollama           | HTTP `POST /api/chat`, `GET /api/tags` | Backend → AI      |
| Laravel → Flask BI         | HTTP `:5000/api/*`                     | Backend → BI      |
| Admin/Storefront → Laravel | HTTPS + session cookie                 | Browser → Backend |

> **Note:** Ollama and Flask BI are _never_ exposed to the public. Only the Laravel backend can reach them. The React POS talks exclusively to the Laravel API.

### Services & Ports (Dev — Docker Compose)

| Service          | Image                 | Host Port | Purpose                                  |
| ---------------- | --------------------- | --------- | ---------------------------------------- |
| `erp_laravel`    | PHP 8.3-FPM (custom)  | 5173      | Laravel + Vite dev server; builds assets |
| `erp_nginx`      | nginx:stable-alpine   | 8082      | Reverse proxy → PHP-FPM                  |
| `erp_db`         | mariadb:10.6          | 3307      | Database (`laravel_db`)                  |
| `erp_react`      | node:22-alpine        | 3060      | React POS Vite dev server                |
| `erp_ollama`     | ollama/ollama         | 11434     | AI inference (2-core CPU limit)          |
| `erp_phpmyadmin` | phpmyadmin/phpmyadmin | 8083      | DB admin UI                              |

Persistent volumes: `erp_db_data` (database), `ollama_data` (AI models).

### Frontend Layers

- **Admin panel** — Blade + Vue 3 + Alpine.js, Tailwind, Quill.js, Tom Select, Chart.js; permission-gated sidebar.
- **Storefront & portals** — public catalog/cart/checkout (customer guard); customer order portal; supplier portal with document upload + invoice PDF (supplier guard).
- **React POS (SPA)** — React 19, Vite 6, Tailwind, React Router, Axios (single instance + bearer-token interceptor), Chart.js, jsPDF; cart persisted to localStorage; PWA manifest.

### Backend Layer (Laravel)

Layering rules: **Routes** map URL → controller (middleware gating here) → **Controllers** validate + orchestrate → **Services** (ActivityLogger, NotificationHelper, OllamaService) hold cross-cutting logic → **Models** own relationships/scopes/casts → **`DB::transaction()`** wraps any multi-table mutation.

Route files: `web.php` (admin + storefront, permission-gated), `api.php` (Sanctum REST + AI), `customer.php`, `supplier.php`, `auth.php`.

### Authentication & Authorization

- **Four guards:** `admin` / `customer` / `supplier` (session) + `sanctum` (bearer token for React POS).
- **RBAC:** roles (`super-admin`, `admin`, `manager`, `cashier`), 120 `module.action` permissions across 32 groups, `permission:` middleware per route, super-admin bypass.
- **Middleware:** RoleMiddleware, PermissionMiddleware, Active\*Middleware, MaintenanceModeMiddleware.

### AI Layer (Ollama)

Single gateway `app/Services/OllamaService.php` → `http://erp_ollama:11434` with model `llama3.2`, configurable temperature, 120 s timeout, exception-safe.

| Capability              | Endpoint                             | Temperature |
| ----------------------- | ------------------------------------ | ----------- |
| Product descriptions    | `POST /ai/product-description`       | 0.8         |
| Natural-language search | `POST /ai/product-search`            | 0.3         |
| Inventory insights      | `POST /ai/inventory-insights`        | 0.4         |
| Sales forecast          | `POST /ai/sales-forecast`            | 0.5         |
| Customer support        | `POST /ai/customer-support` (public) | 0.6         |
| Price suggestion        | `POST /ai/price-suggestion`          | 0.5         |

> The AI is **generative** (text/insights). For deterministic analytics the system delegates to the Flask BI service.

### BI Layer (Flask)

`BiController` proxies to `bi_flask:5000` (30 s timeout). Admin views under `/bi/*` (gated by `reports.view`).

| Method | Endpoint                    | Analytics                                                                                   |
| ------ | --------------------------- | ------------------------------------------------------------------------------------------- |
| POST   | `/analyze`                  | Employee performance (KMeans + IsolationForest + score)                                     |
| GET    | `/product-analysis`         | Product performance tiers                                                                   |
| GET    | `/sales-forecast`           | Next-month forecast + confidence interval                                                   |
| GET    | `/product-combos`           | Frequently-bought-together analysis                                                         |
| POST   | `/recommendations/products` | 6 strategies (popular, trending, content-based, collaborative, similar-users, personalized) |
| GET    | `/prophet-forecast`         | Prophet forecast with confidence intervals                                                  |

### Data Layer

55+ tables across 9 modules (Auth, Catalog, Inventory, Sales, Finance, Procurement, Manufacturing, HRM, System). Conventions: `created_by`/`updated_by`/`deleted_by`, soft deletes, `fk_`-prefixed foreign keys. Every financially-significant mutation is atomic — e.g. POS checkout wraps sale + sale_details + stock decrement + transaction/income + cashbook entry in one `DB::transaction()`.

### Security

HTTPS · 4 guards · per-route permission middleware · CSRF · Blade XSS escaping · image-only uploads · full audit trail with IP capture · `.env` secrets · soft deletes with `deleted_by`.

### Deployment

- **Dev:** `docker compose up` — Laravel (Vite 5173 / nginx 8082), MariaDB (3307), React POS (3060), Ollama (11434), phpMyAdmin (8083).
- **Prod (Hostinger):** Laravel via `production/laravel-production.tar.gz` (Apache + cron queue worker), React POS as static build with `VITE_API_URL=https://erp.obydullah.com/api/v1`.

## Tech Stack

| Layer      | Technology                          |
| ---------- | ----------------------------------- |
| Backend    | Laravel 13, PHP 8.3, Sanctum Auth   |
| Frontend   | React 19, Vite 6, Tailwind CSS 3    |
| Storefront | Laravel Blade, Vue.js 3, Alpine.js  |
| Database   | MariaDB 10.6                        |
| AI         | Ollama (llama3.2) — self-hosted LLM |
| BI         | Flask 2.3, scikit-learn             |
| Infra      | Docker, Nginx, Apache               |
| PDF        | jsPDF + jspdf-autotable             |
| Charts     | Chart.js 4 + react-chartjs-2        |

## Key Modules

| Module              | Description                                                                           |
| ------------------- | ------------------------------------------------------------------------------------- |
| **POS Terminal**    | Category browsing, cart, checkout, VAT/tax/discount, PDF invoices                     |
| **Product Catalog** | Categories, brands, units, sizes, colors, image upload                                |
| **Inventory**       | Multi-warehouse stock with batch/lot tracking                                         |
| **Sales**           | Full lifecycle — sale creation, returns, invoice generation                           |
| **Finance**         | Income/expense tracking, double-entry transactions, cashbook, multi-currency          |
| **Procurement**     | Need → Purchase Order → Shipment → Returns pipeline                                   |
| **Manufacturing**   | Bill of Materials, production planning, cost tracking                                 |
| **HRM**             | Employees, payroll, task management                                                   |
| **Ecommerce**       | Multi-vendor storefront with cart, checkout, customer/supplier portals                |
| **RBAC**            | 4 seeded roles, 120 permissions across 32 groups                                      |
| **AI**              | Product descriptions, inventory insights, sales forecasting, pricing, support chatbot |
| **Reporting**       | Sales trends, income vs expenses, profit analysis, annual performance charts          |

## Screenshots

### Authentication & System

**Login**

![Login](screenshots/ERP_Login.png)

**Dashboard**

![Dashboard](screenshots/ERP_Dashboard.png)

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

### Catalog Management

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

### Inventory & Stock

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

### Sales & POS

**POS Terminal**

![POS](screenshots/POS.png)

**Sales**

![Sales](screenshots/Sales.png)

**Sales Return**

![Sales Return](screenshots/Sales_Return.png)

### Customers

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

### Suppliers

**Suppliers**

![Suppliers](screenshots/Suppliers.png)

**Supplier Due**

![Supplier Due](screenshots/Supplier_Due.png)

**Supplier Transactions**

![Supplier Transactions](screenshots/Supplier_Transactions.png)

### Finance & Accounting

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

### Procurement & Supply Chain

**Procurement Needs**

![Procurement Needs](screenshots/Procurement_Needs.png)

**Create Purchase Order**

![Create Purchase Order](screenshots/Create_Purchase_Order.png)

**Shipments**

![Shipments](screenshots/Shipments.png)

### Manufacturing

**Bill of Materials**

![Bill of Materials](screenshots/Bill_of_Materials.png)

**Production Planning**

![Production Planning](screenshots/Production_Planning.png)

### HRM

**Employees**

![Employees](screenshots/Employees.png)

**Payrolls**

![Payrolls](screenshots/Payrolls.png)

**Tasks**

![Tasks](screenshots/Tasks.png)

### Marketing & Content

**Campaigns**

![Campaigns](screenshots/Campaigns.png)

**Content**

![Content](screenshots/Content.png)

### Reporting

**Reports**

![Reports](screenshots/Reports.png)

**Supplier Report**

![Supplier Report](screenshots/Supplier_Report.png)

### Ecommerce Storefront

**Homepage**

![Homepage](screenshots/Ecommerce/E_Commerce_Homepage.png)

**Featured Products**

![Featured Products](screenshots/Ecommerce/E_Commerce_Featured_Products.png)

**New Arrivals**

![New Arrivals](screenshots/Ecommerce/E_Commerce_New_Arrivales.png)

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

### React POS

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

## Project Structure

```
erp-system/
├── laravel/          # Laravel 13 API + admin panel + storefront
├── react-app/        # React 19 POS frontend
├── production/       # Deployment configs, build scripts, Apache configs
├── nginx/            # Nginx reverse proxy config
├── php/              # PHP-FPM Dockerfile
├── documentation/    # Per-module reference documentation (HTML)
├── screenshots/      # Admin panel, storefront, and POS screenshots
├── docs/             # Case study, feature list, full documentation (md + html), DB dump
├── docker-compose.yml
└── setup.sh
```

See [laravel/README.md](laravel/README.md) and [react-app/README.md](react-app/README.md) for detailed documentation.

## Module Documentation

The complete documentation is available as a single page, covering every module with schema, models, routes, API surface, business flows, permissions, and screenshots:

- [**docs/erp-architecture.md**](docs/erp-architecture.md) — comprehensive system architecture (services, data flow, AI/BI layers, security, deployment)
- [**docs/erp-documentation.md**](docs/erp-documentation.md) — Markdown version (readable directly on GitHub)
- [**docs/erp-documentation.html**](docs/erp-documentation.html) — styled HTML version

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

- **RBAC:** roles → permissions via `role_permissions` (many-to-many). Permission names follow `<module>.<action>` (e.g. `products.view`, `sales.edit`). The `permission` middleware aborts with 403 if the admin lacks any listed permission; **super-admin bypasses all checks**.
- **Seeded roles:** `super-admin` (everything), `admin` (everything except `roles.delete`), `manager` (operational access), `cashier` (POS only).
- **Audit trail:** `App\Services\ActivityLogger` records logins, failed logins, and CRUD mutations with IP and before/after data.
- ⚠️ New admin routes **must** be wrapped in a `permission:` middleware group or they are public.

## REST API

Base path `/api/v1` (see `laravel/routes/api.php`).

### Public Endpoints

| Method | Endpoint               | Purpose                                    |
| ------ | ---------------------- | ------------------------------------------ |
| POST   | `/login`               | Sanctum token login (returns Bearer token) |
| GET    | `/ai/status`           | Ollama health + model name                 |
| POST   | `/ai/customer-support` | AI support chat (public storefront)        |

### Protected Endpoints (`auth:sanctum`)

| Method              | Endpoint                                                        | Purpose                                 |
| ------------------- | --------------------------------------------------------------- | --------------------------------------- |
| POST                | `/logout`                                                       | Revoke current token                    |
| POST                | `/configuration`                                                | System settings for POS                 |
| POST                | `/dashboard`                                                    | Category tree for POS                   |
| POST                | `/category-product/{id}`                                        | In-stock items for a category           |
| GET/POST/PUT/DELETE | `/customer*`                                                    | Customer CRUD                           |
| GET/POST/PUT/DELETE | `/category*`                                                    | Category CRUD                           |
| GET/POST/DELETE     | `/product*`                                                     | Product CRUD (multipart image)          |
| GET/POST            | `/stock`, `/save-stock`                                         | Stock list / create                     |
| GET/POST/DELETE     | `/sale`, `/select-sale/{id}`, `/save-sale`, `/delete-sale/{id}` | Sales list / detail / checkout / delete |
| POST                | `/income`, `/save-income`                                       | Income list / create                    |
| POST                | `/expense`, `/save-expense`                                     | Expense list / create                   |
| POST                | `/report`                                                       | YTD vs last-year financial report       |
| POST                | `/ai/product-description`                                       | Generate product description            |
| POST                | `/ai/product-search`                                            | Natural-language product search         |
| POST                | `/ai/inventory-insights`                                        | Inventory risk & reorder insights       |
| POST                | `/ai/sales-forecast`                                            | 3-month revenue forecast                |
| POST                | `/ai/price-suggestion`                                          | Price range & strategy advice           |

## AI Features

All AI is self-hosted via `App\Services\OllamaService` (`POST {OLLAMA_URL}/api/chat`). Env vars: `OLLAMA_URL` (default `http://erp_ollama:11434`) and `AI_MODEL` (default `llama3.2`). All methods return `null` when Ollama is down — **callers must handle null and degrade gracefully**; the React UI should disable AI widgets based on `GET /ai/status`.

| Feature              | OllamaService Method           | Input                               |
| -------------------- | ------------------------------ | ----------------------------------- |
| Product descriptions | `generateProductDescription()` | name, category, specs               |
| Product search       | `searchProducts()`             | natural-language query + catalog    |
| Inventory insights   | `inventoryInsights()`          | stock data with margins             |
| Sales forecast       | `salesForecast()`              | 12 months of completed-sale revenue |
| Price suggestion     | `suggestPrice()`               | current price + competitor prices   |
| Customer support     | `customerSupport()`            | message + product context           |

## Business Intelligence

A Flask microservice (referenced as `bi_flask:5000`) provides ML analytics, consumed by `BiController` on the admin panel (`/bi`, `/bi/employees`, `/bi/products`, `/bi/recommendations`, `/bi/forecast`, `/bi/prophet-forecast`, `/bi/combos`).

| Endpoint                             | Technique                                                                                       |
| ------------------------------------ | ----------------------------------------------------------------------------------------------- |
| `POST /api/analyze`                  | KMeans (n=3) + IsolationForest employee performance clustering                                  |
| `GET /api/product-analysis`          | KMeans product performance tiers                                                                |
| `GET /api/sales-forecast`            | Linear regression forecast with confidence interval                                             |
| `POST /api/recommendations/products` | 6 strategies (popular, trending, content-based KNN, collaborative, similar users, personalized) |
| `GET /api/product-combos`            | Apriori association-rule combos                                                                 |
| `GET /api/prophet-forecast`          | Period-based time-series forecast                                                               |

## Financial Integrity

The POS checkout is the canonical transactional flow (`ApiController::saveSale`, `PosController::checkout`): a single sale atomically writes **Sale + SaleDetails + stock decrement + Transaction + Income + Cashbook** inside one `DB::beginTransaction()` … `DB::commit()` with rollback on exception.

- **15 transaction types** are defined as constants on `App\Models\Transaction` (saleIncome, saleDue, userFund, supplierPayment, expense, income, salaryPayment, bomExpense, campaignExpense, fixedAsset, stockIn/Out, miscIncome/Expense, supplierDeposit).
- **`cashbook` is the double-entry ledger** — every financial event writes a row (`in_amount`, `out_amount`, `amount_payable`, `amount_receivable`).
- **Any new money-moving or stock-moving operation must follow the same transactional pattern** and verify ledger rows are written and stock is decremented exactly once.

## Quick Start

```bash
# Clone and setup
git clone <repo-url>
cd erp-system
./setup.sh
```

Or manually:

```bash
docker compose up -d --build
docker compose exec erp_laravel php artisan key:generate
docker compose exec erp_laravel php artisan migrate
docker compose exec erp_laravel php artisan db:seed
```

Or run the Laravel app natively (from `laravel/`):

```bash
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate && php artisan db:seed
npm install && npm run build
```

### Default Credentials

```
Email:    demp@obydullah.com
Password: 11111111
```

Verify against the seeder — `admin@erp.com` / `password` also appears in the Laravel README.

### Services

| Service         | Port  | URL                   |
| --------------- | ----- | --------------------- |
| Laravel (Nginx) | 8082  | http://localhost:8082 |
| React POS       | 3060  | http://localhost:3060 |
| phpMyAdmin      | 8083  | http://localhost:8083 |
| MariaDB         | 3307  | localhost:3307        |
| Ollama AI       | 11434 | localhost:11434       |

## Development Workflow

| Command                                      | Purpose                                                               |
| -------------------------------------------- | --------------------------------------------------------------------- |
| `composer run dev`                           | Parallel: `php artisan serve`, queue listen, pail logs, `npm run dev` |
| `composer test`                              | Run PHPUnit tests                                                     |
| `./vendor/bin/pint`                          | PSR-12 code style (Laravel Pint)                                      |
| `php artisan migrate` / `db:seed`            | Schema / seeders                                                      |
| `npm run build` (laravel/)                   | Compile Tailwind + JS admin assets                                    |
| `npm run dev` / `npm run build` (react-app/) | React POS dev server (3060) / production build                        |

## Testing

PHPUnit tests live under `laravel/tests/Feature` covering auth, RBAC, and activity logging. Run them with:

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

For the React app, `.env` uses `VITE_API_URL` for dev and `VITE_API_URL_PRODUCTION` for production builds.

## Database

55+ tables organized into modules: **Auth** (admins, customers, suppliers, roles, permissions), **Catalog** (products, categories, brands, sizes, colors, units), **Inventory** (stocks, inventory, warehouses, stock_adjustments), **Sales** (sales, sale_details, sale_returns, cart, cart_details), **Finance** (transactions, cashbook, incomes, expenses, payable, receivable, balances, currencies), **Procurement** (needs, purchase_orders, shipments), **Manufacturing** (bill_of_materials, production_plannings, productions), **HRM** (employees, payrolls, task_management), **System** (activities, notifications, configurations, contents).

- All tables use soft deletes and track `created_by` / `updated_by` / `deleted_by` where applicable.
- A full dump lives at `docs/u181095087_erp.sql`.

See [laravel/README.md](laravel/README.md#database-schema) for the full schema.

## License

© Shaik Obydullah. All Rights Reserved.
