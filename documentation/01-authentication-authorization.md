# Module 01 — Authentication & Authorization

**Module:** Authentication & Authorization (Auth)
**Category:** System · Security
**Purpose:** Secures every layer of the ERP — the admin panel, the storefront, the supplier portal, and the REST API — using multiple independent guards and a granular Role-Based Access Control (RBAC) engine.

> This module is the security backbone of the system. Every other module is gated behind it. It implements session-based authentication for humans (admins, customers, suppliers) and token-based authentication (Laravel Sanctum) for the React POS terminal.

## 1. Overview

`config/auth.php` defines five guards, each mapped to a distinct persona (plus the framework default `web` guard):

- **Admin guard** — session-based, used by the admin panel (Blade + Vue + Alpine). Middleware: `RoleMiddleware`, `PermissionMiddleware`, `ActiveAdminMiddleware`, `MaintenanceModeMiddleware`.
- **Customer guard** — session-based, used by the storefront and customer portal. Middleware: `ActiveCustomerMiddleware`.
- **Supplier guard** — session-based, used by the supplier portal. Middleware: `ActiveSupplierMiddleware`.
- **Sanctum guard** — bearer tokens for the REST API. `POST /api/v1/login` returns a token; all subsequent calls send `Authorization: Bearer <token>`. Sanctum's provider is the `admins` table.
- **Web guard** — Laravel's framework-default session guard backed by the `users` table; not used by the admin/storefront/portal flows.

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `admins` | Back-office users | first_name, last_name, sex, email (unique), password, image, mobile, address, status (active/inactive) |
| `customers` | Storefront shoppers | name, email (unique), password, phone, address, balance (decimal), status |
| `suppliers` | Vendors with portal access | name, email (unique), password, mobile, address, balance (decimal), status |
| `roles` | Named permission groups | name, description |
| `permissions` | Granular capability flags | name (e.g. `products.view`), group, fk_role_id (nullable) |
| `role_permissions` | Many-to-many role → permission link | fk_role_id, fk_permission_id |
| `role_relations` | Admin → role assignment | fk_admin_id, fk_role_id |
| `admin_password_reset_tokens` | Password reset flow | email (PK), token |
| `customer_password_reset_tokens` | Customer password reset | email (PK), token |
| `supplier_password_reset_tokens` | Supplier password reset | email (PK), token |
| `admin_sessions` | Admin session storage | id, admin_id, ip_address, user_agent, payload |
| `customer_sessions` | Customer session storage | id, customer_id, ip_address, user_agent, payload |
| `supplier_sessions` | Supplier session storage | id, supplier_id, ip_address, user_agent, payload |
| `personal_access_tokens` | Sanctum API tokens | tokenable_type/id, token, name, abilities, expires_at |

## 3. Key Models & Relationships

- **Admin** (`app/Models/Admin.php`) — Authenticatable; `roles()` belongs-to-many via `role_relations`; `hasRole()`, `hasAnyRole()`, `hasPermission()`, `hasAnyPermission()`, `isActive()` helpers.
- **Customer** (`app/Models/Customer.php`) — Authenticatable, guard `'customer'`; `isActive()`, `isDue()` (balance < 0), `balances()`, `cashbookEntries()`. Password hashed automatically.
- **Supplier** (`app/Models/Supplier.php`) — Authenticatable, guard `'supplier'`; `isActive()`.
- **Role** — `permissions()` belongs-to-many via `role_permissions`; `admins()` via `role_relations`.
- **Permission** — grouped by a `group` column for checkbox-grid rendering in the UI.

## 4. RBAC Design

**Permission granularity.** The `PermissionSeeder` seeds **127 permissions across 34 groups**, following the pattern `<module>.<action>` where action ∈ {view, save, edit, delete}. The seeded groups are: Accounting (3), Activity Log (1), Admin Management (4), Brands, Campaigns, Cashbook, Categories, Colors, Customers, Dashboard (1), E-Commerce, Employees, Expenses, Income, Notifications (2), Orders, Payroll, POS, Products, Reports, Reviews, Roles & Permissions, Sales, Settings, Shipments, Shipping Methods, Shipping Zones, Sizes, Stocks, Suppliers, Tasks, Transactions, Units, Warehouses.

**Enforcement.** The `permission` middleware accepts a variadic list of permission names and aborts with 403 unless the admin has **any** of them (`hasAnyPermission`). There is no hard-coded super-admin bypass in the middleware — the `super-admin` role simply holds all 127 permissions. Most admin routes in `routes/web.php` are individually wrapped in `permission:<name>` middleware; several resource CRUD groups (brands, categories, products, employees, payrolls, tasks, campaigns, warehouses, shipments, shipping zones, needs, purchase orders, bill-of-materials, production planning, productions) currently rely on the `auth:admin` + `active.admin` group and menu visibility rather than per-route permission checks. New routes should add explicit `permission:` middleware.

**Seeded roles:**

| Role | Permissions | Access Profile |
|---|---|---|
| `super-admin` | 127 | Every permission (all groups) |
| `admin` | 126 | Every permission except `roles.delete` |
| `manager` | 30 | Dashboard, products, categories, stocks, customers, sales, orders, employees, tasks, reports, incomes, expenses, cashbook (view), accounting (view) |
| `cashier` | 9 | Dashboard, POS, products (view), customers (view/save), sales (view/save), stocks (view) |

A fifth role, `Demo` (31 permissions), exists in the current live database as a test/demo role but is not created by `PermissionSeeder`.

## 5. Authentication Flows

### 5.1 Session Authentication (Admin / Customer / Supplier)

Standard Laravel multi-guard session flow with dedicated auth scaffolding under `routes/auth.php`, `routes/customer.php` and `routes/supplier.php`:

- Register (customers and admins), login, logout, forgot-password, reset-password, email verification, password confirmation.
- Guard-specific controllers under `app/Http/Controllers/Customer/Auth/` and `app/Http/Controllers/Supplier/Auth/`.
- Session lifetime enforced by `ActiveAdminMiddleware` / `ActiveCustomerMiddleware` / `ActiveSupplierMiddleware` — inactive users are rejected even if authenticated.

### 5.2 Token Authentication (REST API / React POS)

Defined in `routes/api.php` (base path `/api/v1`):

- `POST /login` (public) — validates email/password against the `admins` table, issues a Sanctum token, writes an `ActivityLogger::login` audit entry, and returns `{ token, admin }`.
- `POST /logout` (protected) — deletes the current access token and writes a logout audit entry.
- All other routes live behind `auth:sanctum` middleware.

The React POS stores the token and attaches it via an Axios interceptor (`src/api/axios.js`).

## 6. Route Map

| Route File | Guard | Content |
|---|---|---|
| `routes/web.php` | admin (session) | Admin panel + permission-gated CRUD for all modules; storefront, customer portal, supplier portal, and admin AJAX endpoints |
| `routes/api.php` | public + auth:sanctum | REST API consumed by React POS and the mobile offline sync client |
| `routes/customer.php` | customer (session) | Customer register/login/password + dashboard/profile |
| `routes/supplier.php` | supplier (session) | Supplier login/password + dashboard |
| `routes/auth.php` | admin (session) | Framework auth scaffolding (login, register, password reset, verify, confirm) |

## 7. Admin Management Surface (Routes)

- `GET/POST /roles`, `GET /roles/create`, `GET /roles/{role}/edit`, `PUT /roles/{role}`, `DELETE /roles/{role}` — RoleController (`roles.*`).
- `GET/POST /admins`, `GET /admins/create`, `GET /admins/{admin}/edit`, `PUT /admins/{admin}`, `DELETE /admins/{admin}` — AdminController (`admins.*`).
- `GET /permissions` — PermissionController (permission grid, `roles.view`).

## 8. Permissions & Middleware (Quick Reference)

| Middleware | Applies To | Behavior |
|---|---|---|
| `auth:admin` | Admin routes | Rejects unauthenticated sessions |
| `active.admin` | Admin routes | Rejects inactive admin accounts |
| `permission:<names>` | Per-route | 403 unless admin has any listed permission |
| `auth:customer` + `active.customer` | Storefront/portal | Customer session + active check |
| `auth:supplier` + `active.supplier` | Supplier portal | Supplier session + active check |
| `auth:sanctum` | API routes | Bearer token validation |
| `maintenance` | Web group | Blocks non-admin access while maintenance mode is on |

## 9. Security Conventions & Gotchas

- **Do not mix guards.** Admin panel routes use the `admin` guard; API routes use `auth:sanctum`; storefront uses `customer`; supplier portal uses `supplier`. Never conflate them.
- **New admin routes must be permission-gated** or they become reachable by any authenticated admin — add them inside a `permission:` middleware wrapper.
- **Passwords** are stored via Laravel's `hashed` cast; `createCustomer` default password is the email address (change on first login).
- **Failed logins are audited** through `ActivityLogger::failedLogin`.
- Remember-token and email-verified-at columns exist on all three human guards.

## 10. Related Code Locations

- Models: `laravel/app/Models/{Admin,Customer,Supplier,Role,Permission}.php`
- Middleware: `laravel/app/Http/Middleware/` (RoleMiddleware, PermissionMiddleware, Active*Middleware, MaintenanceModeMiddleware)
- Auth controllers: `laravel/app/Http/Controllers/{AdminController,RoleController,PermissionController}` and `Customer/Auth/`, `Supplier/Auth/`
- Seeder: `laravel/database/seeders/PermissionSeeder.php`
- API login: `laravel/app/Http/Controllers/Api/ApiController.php` (`login`, `logout`)
- Routes: `laravel/routes/{web,api,customer,supplier,auth}.php`
