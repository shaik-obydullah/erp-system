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

```
react-pos.obydullah.com (React POS terminal)
        │
        ▼
   Apache / Nginx
        │
        ▼
erp.obydullah.com (Laravel API + Admin + Storefront)
        │
        ├── MariaDB (single source of truth, 55+ tables)
        ├── Ollama (llama3.2) — generative AI
        ├── Flask BI (bi_flask:5000) — ML analytics
        └── Storage (product images)
```

The backend is a Laravel monolith serving three front-ends: the admin panel (Blade + Vue + Alpine), the public storefront, and the REST API consumed by the React POS. The React POS authenticates with Laravel Sanctum bearer tokens; all admin/storefront/portal sessions are guard-based.

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

## Project Structure

```
erp-system/
├── laravel/          # Laravel 13 API + admin panel + storefront
├── react-app/        # React 19 POS frontend
├── production/       # Deployment configs, build scripts, Apache configs
├── nginx/            # Nginx reverse proxy config
├── php/              # PHP-FPM Dockerfile
├── docs/             # Case study, feature list, module documentation, DB dump
│   └── modules/      # Per-module reference documentation (TinyMCE/HTML)
├── docker-compose.yml
└── setup.sh
```

See [laravel/README.md](laravel/README.md) and [react-app/README.md](react-app/README.md) for detailed documentation.

## Module Documentation

Each module has a comprehensive reference doc covering schema, models, routes, API surface, business flows, and permissions:

| Module                         | Document                                                                                               |
| ------------------------------ | ------------------------------------------------------------------------------------------------------ |
| Authentication & Authorization | [docs/modules/01-authentication-authorization.html](docs/modules/01-authentication-authorization.html) |
| Administration & System        | [docs/modules/02-administration-system.html](docs/modules/02-administration-system.html)               |
| Catalog Management             | [docs/modules/03-catalog-management.html](docs/modules/03-catalog-management.html)                     |
| Inventory & Stock              | [docs/modules/04-inventory-stock.html](docs/modules/04-inventory-stock.html)                           |
| Sales & POS                    | [docs/modules/05-sales-pos.html](docs/modules/05-sales-pos.html)                                       |
| E-Commerce & Storefront        | [docs/modules/06-ecommerce-storefront.html](docs/modules/06-ecommerce-storefront.html)                 |
| Financial Management           | [docs/modules/07-finance-accounting.html](docs/modules/07-finance-accounting.html)                     |
| Procurement & Supply Chain     | [docs/modules/08-procurement-supply-chain.html](docs/modules/08-procurement-supply-chain.html)         |
| Manufacturing                  | [docs/modules/09-manufacturing.html](docs/modules/09-manufacturing.html)                               |
| HRM                            | [docs/modules/10-hrm.html](docs/modules/10-hrm.html)                                                   |
| Marketing & Campaigns          | [docs/modules/11-marketing-campaigns.html](docs/modules/11-marketing-campaigns.html)                   |
| AI & Business Intelligence     | [docs/modules/12-ai-business-intelligence.html](docs/modules/12-ai-business-intelligence.html)         |

Also see the [Case Study](docs/Case%20Study.md), [Feature List](docs/ERP%20-%20Feature%20List.md), and [AGENTS.md](docs/AI%20Agents.md) for AI agents working in this repository.

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

## Common Gotchas

1. **Ollama down** → AI endpoints return `null`/error; React UI must degrade gracefully.
2. **API base URL** — react-app `.env` uses `VITE_API_URL` for dev and `VITE_API_URL_PRODUCTION` for prod builds.
3. **Session vs API auth** — admin panel routes use the `admin` guard; API routes use `auth:sanctum`. Don't mix them.
4. **RBAC enforcement** — new admin routes must be added inside permission middleware groups or they are public.
5. **Financial operations** — always wrap in `DB::transaction()`; verify cashbook/transaction rows are written and stock decremented exactly once.
6. **Vite asset pipeline** — admin assets are compiled from `resources/`; run `npm run build` after touching Tailwind/JS. The React app builds independently.
7. **Flask BI** — if the microservice is down, `/bi/*` pages render error/empty states; it is not required for core ERP operations.
8. **Do not commit secrets** — `.env` files are gitignored; keep them out of commits.

## License

Private use.
