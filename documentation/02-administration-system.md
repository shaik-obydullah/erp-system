# Module 02 — Administration & System

**Module:** Administration & System (System)
**Category:** Core Platform
**Purpose:** Provides the global control center: executive dashboard, system configuration, audit trail, notifications, CMS content, and maintenance mode.

> This module wires together every other module. The dashboard aggregates sales, stock, and workforce KPIs; the settings panel drives company identity, currency, VAT/tax rates, date formats and more; and the audit trail records every significant mutation in the system.

## 1. Overview

The Administration & System module includes six functional areas:

- **Dashboard** — KPI summary, top products, category revenue, recent sales.
- **System Settings (Configurations)** — key-value store consumed across all modules.
- **Activity Log** — full audit trail with IP capture and optional geolocation.
- **Notifications** — in-app alerts with seen/unseen state and per-admin targeting.
- **CMS Content** — editable storefront content blocks (hero, slider, page, banner, FAQ, settings).
- **Maintenance Mode** — global on/off switch guarded by settings permission.

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `configurations` | Key-value system settings | name (unique, e.g. `currency_symbol`), setting (text) |
| `activities` | Audit trail | fk_admin_id, type (success/warning/error), name, description, subject_type, subject_id, ip_address, visitor_country/state/city/address, old_data, new_data |
| `notifications` | In-app notifications | fk_admin_id (nullable, per-admin), title, link, notification, type (info/success/warning/error), module, view_status (seen/unseen) |
| `contents` | CMS pages & content blocks | name, attribute, media, content (HTML), type (hero/page/banner/faq/settings/slider), slug (unique), status, sort_order |
| `fiscal_year` | Accounting period definition | fiscal_year_start, fiscal_year_end |
| `currencies` | Multi-currency support | code (unique), name, symbol, exchange_rate, is_base, is_active |
| `cache` / `jobs` | Framework support tables | — |
| `usermeta` | Per-user metadata | fk_user_id, key, value |

## 3. Key Models

- **Configuration** (`app/Models/Configuration.php`) — static helper `Configuration::get('key', $default)`; supports bulk get/set via `getMany([...])` / `setMany([...])`.
- **Activity** — audit entry; polymorphic subject (subject_type/subject_id), captures old/new data on updates.
- **AdminNotification / Notification** — typed in-app alerts, optionally scoped to an admin via `fk_admin_id`.
- **Content** — CMS records for the storefront; type-driven, slug-addressable, sortable. Scopes: `type()`, `active()`, `slug()`.

## 4. Dashboard (DashboardController)

`app/Http/Controllers/DashboardController.php` — `GET /dashboard` (`permission:dashboard.view`) renders:

- **KPI cards:** total sales, total revenue (paid), total expenses, active products, customers, active suppliers, active employees, total stock value (sum of quantity × buy_price).
- **Order stats:** total orders, completed orders, pending orders (status `orderPlaced`), total due.
- **Recent sales:** last 5 by id.
- **Top products:** by quantity sold across sale_details (fallback to latest 5 products when no sales data).
- **Category revenue:** top 5 categories by sum of sale_detail subtotals.
- Chart data arrays (`monthlySales`, `monthlyExpenses`) are prepared for Chart.js rendering.

## 5. System Settings (ConfigController)

Routes: `GET /settings` (`permission:settings.view`), `PUT /settings` (`permission:settings.save`), `POST /maintenance/toggle` (`permission:settings.save`).

**Configuration keys** handled by the settings screen (`ConfigController` + `ConfigurationSeeder`):

| Key | Purpose |
|---|---|
| `company_name`, `company_email`, `company_phone`, `company_address`, `company_country`, `company_vat_number`, `company_website` | Company identity used on invoices and headers |
| `currency` / `currency_symbol` | Currency code and symbol shown in all money displays |
| `vat_rate` | VAT rate for POS/cart/accounting calculations |
| `tax_rate` | Additional tax rate |
| `date_format`, `timezone` | Display formatting |
| `fiscal_year_start` | Fiscal year start (MM-DD) |
| `invoice_prefix`, `order_prefix` | Document number prefixes |
| `low_stock_threshold` | Low-stock threshold for alerts/reports |
| `allow_registration` | Whether self-registration is enabled |
| `maintenance_mode` | Global maintenance flag (toggled separately) |

The POS API also exposes compatibility aliases `vat_percentage`, `tax_percentage`, and `project_name` via `ApiController::configuration`.

## 6. Activity Log (Audit Trail)

### 6.1 ActivityLogger Service

`app/Services/ActivityLogger.php` provides static helpers called on every mutation across the codebase:

- `login($email)`, `logout()`, `failedLogin($email, $reason)` — session lifecycle.
- `created($moduleName, $model)` — stores the new model state in `new_data`.
- `updated($moduleName, $model, $changes)` — stores `old_data` and `new_data` (only changed attributes).
- `deleted($moduleName, $model)` — stores snapshot in `old_data`, type `warning`.
- `error($name, $description)` — error-type entries.

Each entry captures the acting admin, IP address, and optional geolocation fields (country/state/city/address, resolved via external geo service in `ActivityService`).

### 6.2 Listing Surface

- `GET /activities` (`permission:activity.view`) — paginated viewer.
- AJAX endpoints (admin-authenticated, no separate permission middleware): `GET /api/activities`, `GET /api/activities/stats`, `DELETE /api/activities/clear`.

## 7. Notification System

`app/Services/NotificationHelper.php` creates in-app notifications on key events (orders, sales, stock events, etc.). Fields: title, link, notification text, `type` (info/success/warning/error), `module`, and optional `fk_admin_id` for targeted delivery.

**Admin surface** (`NotificationController`):

- `GET /notifications` — list page.
- `GET /api/notifications`, `GET /api/notifications/unread-count`, `GET /api/notifications/unread` — AJAX feeds for the sidebar badge.
- `POST /api/notifications/{id}/seen`, `POST /api/notifications/mark-all-seen` — read-state updates.
- `POST /api/notifications`, `DELETE /api/notifications/{id}` — create/delete.

Notification routes are protected by the admin auth group but do not currently add per-route `permission:` middleware.

## 8. CMS Content Management

`CmsController` (resource `cms`, excluding `show`) provides CRUD for storefront content. `contents` records support:

- Rich HTML content (`content` column) rendered on the storefront.
- `type` field restricted to `hero`, `page`, `banner`, `faq`, `settings`, or `slider`.
- Unique `slug` for URL addressing (auto-generated from `name` when blank) and `sort_order` for display ordering.
- `media` for images and `attribute` for extra metadata.

The storefront loads active `hero`, `slider`, `page`, and `faq` content blocks by `sort_order`.

## 9. Multi-Currency (Currencies)

- `CurrencyController` resource (excluding `show`) plus `POST /currencies/{currency}/set-base` (`permission:settings.save`).
- Each currency has a code (unique), symbol, `exchange_rate`, `is_base` flag (base = rate 1.000000), and `is_active`.

## 10. Permissions (This Module)

| Permission | Gates |
|---|---|
| `dashboard.view` | Dashboard + all `/reports/*` pages |
| `settings.view` / `settings.save` | Settings page, maintenance toggle, currency base |
| `activity.view` | Activity log viewer |
| `notifications.view` / `notifications.save` | Notification permission group (routes currently rely on admin auth) |
| `reports.view` / `reports.save` / `reports.edit` / `reports.delete` | Reports permission group |
| `ecommerce.view/save/edit/delete` | E-Commerce permission group |

## 11. Related Code Locations

- Controllers: `laravel/app/Http/Controllers/{DashboardController,ConfigController,ActivityController,NotificationController,CmsController,CurrencyController}.php`
- Services: `laravel/app/Services/{ActivityLogger,ActivityService,NotificationHelper}.php`
- Models: `laravel/app/Models/{Configuration,Activity,AdminNotification,Notification,Content,Currency}.php`
- Seeders: `laravel/database/seeders/ConfigurationSeeder.php`
