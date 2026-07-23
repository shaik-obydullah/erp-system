# ERP System — Laravel Backend

A comprehensive Enterprise Resource Planning (ERP) system built with Laravel 13, featuring full accounting, inventory management, POS, HRM, procurement, manufacturing, and an AI-powered ecommerce storefront.

## Features

### Core ERP
- **Multi-User Authentication** — Triple guard system (Admin, Customer, Supplier) with full auth flows
- **Role-Based Access Control** — Custom RBAC with 70+ granular permissions across 20 groups
- **Product Catalog** — Hierarchical categories, brands, units, sizes, colors, multi-image support
- **Inventory Management** — Multi-warehouse stock tracking with batch/lot numbers and adjustments
- **Point of Sale** — Full POS with cart, VAT/tax, discounts, due tracking, and checkout
- **Sales Management** — Invoices, sale returns with approval workflow, automatic stock restocking
- **Full Accounting** — Double-entry transactions, cashbook, income/expense, receivables/payables, fixed assets
- **Multi-Currency** — Currency management with exchange rates and base currency conversion

### Supply Chain & Manufacturing
- **Procurement Pipeline** — Need identification → Purchase Order → Shipment → Returns
- **Bill of Materials** — BOM management linked to products
- **Production Planning** — Planning with finalize workflow and cost tracking
- **Warehouse Management** — Warehouse CRUD with capacity and location info

### HRM
- **Employee Management** — Full employee CRUD with job titles and hire dates
- **Payroll** — Salary calculation (basic + allowances - deductions = net)
- **Task Management** — Task assignment with due dates and status tracking

### Ecommerce Storefront
- **Public Storefront** — Product listings, search/filter/sort, category and brand browsing
- **Multi-Vendor Marketplace** — Supplier storefronts with vendor pages
- **Shopping Cart & Checkout** — Full cart with Vue.js-powered checkout
- **Customer Portal** — Order history, profile management
- **Supplier Portal** — Purchase order viewing, product listing

### AI & BI
- **Ollama AI Integration** — Self-hosted LLM (llama3.2) for:
  - Product description generation
  - Natural language product search
  - Inventory insights and stockout risk analysis
  - 3-month sales forecasting
  - AI pricing optimization
  - Customer support chatbot
- **BI Dashboard** — Integration with Flask microservice for employee analysis, product analysis, Prophet forecasting, and combo analysis

### System
- **Activity Logging** — Full audit trail with IP tracking, geolocation, old/new data diffs
- **Notification System** — In-app notifications with seen/unseen tracking per admin
- **CSV Import/Export** — For customers, suppliers, transactions, categories, brands, units, sizes, colors
- **Reporting** — Sales, income, expense, stock, customer, supplier reports with Chart.js
- **CMS** — Content management with hero, page, and FAQ types
- **Maintenance Mode** — Database-stored toggle with super-admin bypass
- **Configurable Settings** — Company info, currency, timezone, tax rates, invoice prefixes

## Tech Stack

| Component | Technology |
|-----------|-----------|
| Framework | Laravel 13 |
| Language | PHP 8.3+ |
| Database | MySQL / SQLite |
| Auth | Laravel Sanctum |
| AI | Ollama (llama3.2) |
| BI | Flask microservice |
| Frontend | Vue.js 3, Alpine.js, Tailwind CSS, Vite |
| Queue | Database driver |
| Cache | Database driver |

## Project Structure

```
laravel/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/              # Admin authentication (8 controllers)
│   │   │   ├── Api/               # REST API + AI endpoints
│   │   │   ├── Customer/          # Customer auth + portal
│   │   │   ├── Supplier/          # Supplier auth + portal
│   │   │   ├── DashboardController.php
│   │   │   ├── ProductController.php
│   │   │   ├── SaleController.php
│   │   │   ├── PosController.php
│   │   │   ├── ReportController.php
│   │   │   ├── BiController.php
│   │   │   ├── StorefrontController.php
│   │   │   └── ...                # 67 controllers total
│   │   └── Middleware/
│   │       ├── RoleMiddleware.php
│   │       ├── PermissionMiddleware.php
│   │       ├── ActiveAdminMiddleware.php
│   │       └── MaintenanceModeMiddleware.php
│   ├── Models/                    # 45 Eloquent models
│   ├── Services/
│   │   ├── OllamaService.php      # AI integration
│   │   ├── ActivityLogger.php     # Audit logging
│   │   └── NotificationHelper.php # In-app notifications
│   └── View/                      # View composers
├── database/
│   ├── migrations/                # 67 migrations (~55 tables)
│   └── seeders/                   # EcommerceSeeder (35 products)
├── public/
│   ├── uploads/products/          # Product images
│   ├── css/                       # Compiled Tailwind
│   └── js/                        # Compiled Vue/Alpine
├── resources/
│   ├── views/
│   │   ├── layouts/               # Admin sidebar, header, pagination
│   │   ├── storefront/            # Public ecommerce pages
│   │   ├── products/              # Admin product views
│   │   └── vendor/                # Published pagination views
│   └── css/                       # Source Tailwind
├── routes/
│   ├── web.php                    # Admin + storefront routes
│   ├── api.php                    # REST API endpoints
│   ├── customer.php               # Customer auth routes
│   └── supplier.php               # Supplier auth routes
└── config/                        # Laravel + custom configs
```

## Database Schema

55+ tables across these modules:

| Module | Tables |
|--------|--------|
| Auth | `admins`, `users`, `customers`, `suppliers`, `roles`, `permissions`, `role_relations`, `role_permissions` |
| Catalog | `products`, `categories`, `brands`, `units`, `sizes`, `colors`, `reviews` |
| Inventory | `stocks`, `inventory`, `warehouses`, `stock_adjustments` |
| Sales | `sales`, `sale_details`, `sale_returns`, `cart`, `cart_details` |
| Finance | `transactions`, `cashbook`, `incomes`, `expenses`, `payable`, `receivable`, `balances`, `currencies` |
| Procurement | `needs`, `purchase_orders`, `shipments`, `shipment_returns` |
| Manufacturing | `bill_of_materials`, `production_plannings`, `productions` |
| HRM | `employees`, `payrolls`, `task_management` |
| System | `activities`, `notifications`, `configurations`, `contents`, `campaigns`, `fixed_assets` |

## API Endpoints

All API routes are prefixed with `/api/v1/`. Authentication uses Bearer tokens via Laravel Sanctum.

### Public
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/login` | Admin authentication |
| GET | `/configuration` | ERP settings |
| POST | `/ai/customer-support` | AI chatbot |

### Protected (`auth:sanctum`)
| Module | Endpoints |
|--------|-----------|
| Customers | `customer`, `save-customer`, `update-customer/{id}`, `delete-customer/{id}` |
| Categories | `category`, `save-category`, `update-category/{id}`, `delete-category/{id}` |
| Products | `product`, `save-product`, `update-product/{id}`, `delete-product/{id}` |
| Stocks | `stock`, `save-stock` |
| Sales | `sale`, `select-sale/{id}`, `save-sale`, `delete-sale/{id}` |
| Finance | `income`, `save-income`, `expense`, `save-expense`, `report` |
| AI | `ai/product-description`, `ai/product-search`, `ai/inventory-insights`, `ai/sales-forecast`, `ai/price-suggestion` |

## Setup

### Prerequisites
- PHP 8.3+
- Composer
- Node.js 18+
- MySQL or SQLite

### Installation
```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed          # Seeds 35 products with images
npm install && npm run build
```

### Docker (Development)
```bash
docker compose up -d
```
- Laravel: `http://localhost:8082`
- React POS: `http://localhost:3060`

### Default Credentials
```
Email:    admin@erp.com
Password: password
```

### Production Deployment
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
```

## License

Private use.
