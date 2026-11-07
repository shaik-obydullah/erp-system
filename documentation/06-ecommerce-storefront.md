# Module 06 — E-Commerce & Storefront

**Module:** E-Commerce & Storefront (E-Commerce)
**Category:** Online Selling
**Purpose:** Powers the customer-facing storefront (browse, product detail, cart, checkout, shipping options), the customer portal, and the supplier-facing portal for purchase orders and catalog visibility.

> The storefront is the public face of the catalog. It reuses the same product, stock, and CMS data as the admin panel, applies shipping-zone rules at checkout, and creates a normal `Sale` record plus the corresponding accounting entries. The supplier portal gives vendors read access to their products and purchase orders.

## 1. Overview

The module is organized into three surfaces:

- **Storefront (public/customer)** — home, product list, product detail with published reviews, vendor directory, cart, checkout, and shipping options.
- **Customer portal** — authenticated order history, order detail, and profile.
- **Supplier portal** — authenticated dashboard, purchase orders, product list, and profile.

There are no banner-management routes, shipment-upload routes, or invoice-generation routes in the current storefront/portal code.

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `customers` | Shopper accounts | name, email (unique), password, phone, address, balance, status, vat_number, country, vat_treatment |
| `cart` / `cart_details` | Admin POS cart only | See Sales & POS module |
| `sales` / `sale_details` | Storefront and POS sales | See Sales & POS module |
| `reviews` | Product reviews | fk_product_id, fk_user_id, rating, review, status |
| `shipping_zones` | Country-based shipping zones | name, countries (JSON), status, sort_order |
| `shipping_methods` | Methods within a zone | fk_shipping_zone_id, name, type, cost, min_order_amount, status, sort_order |
| `shipments` / `shipment_costs` / `shipment_returns` | Procurement-side shipment records | See Procurement module |
| `contents` | CMS pages and content blocks | See Administration & System module |
| `suppliers` | Vendor accounts | name, email (unique), password, mobile, address, balance, status |
| `purchase_orders` | Supplier-visible POs | See Procurement module |

The storefront does **not** use the `cart` / `cart_details` tables; its cart is built client-side and submitted with the checkout payload. Those tables belong to the admin POS.

## 3. Key Models

- **Customer** (`app/Models/Customer.php`) — `isActive()`, `isDue()` (balance < 0), `balances()`, `cashbookEntries()`.
- **Review** (`app/Models/Review.php`) — `product()`, `customer()` (via `fk_user_id`).
- **ShippingZone** — `methods()`, `activeMethods()`, `matchesCountry()`; `countries` is a JSON array (`['*']` for rest-of-world).
- **ShippingMethod** — `zone()`, `isAvailable($subtotal)`, `calculateCost($subtotal)`; types include flat/free/local pickup style handling in the storefront label logic.
- **Content** — CMS blocks loaded on the home page (`hero`, `slider`, `page`, `faq`).

## 4. Storefront (Public) Routes

| Route | Method | Purpose |
|---|---|---|
| `/` | GET | Redirects to `/home` |
| `/home` | GET | Hero/slider/page/FAQ content, featured categories, featured products, new arrivals |
| `/products-list` | GET | Full catalog with search/category/brand filters |
| `/product/{slug}` | GET | Product detail: gallery, specs, published reviews, stock status |
| `/vendors` | GET | Active supplier directory |
| `/vendors/register` | POST | Vendor self-registration (creates an active `Supplier`) |
| `/vendor/{slug}` | GET | Products belonging to one vendor |
| `/cart` | GET | Cart page (client-side cart state) |
| `/checkout` | GET | Checkout form |
| `/checkout` | POST | `placeOrder` — creates the sale, stock deduction, and accounting entries |
| `/shipping/options` | GET | AJAX: matching zone + available methods/costs for a country and subtotal |
| `/newsletter/subscribe` | POST | Stores an email in `newsletter_subscribers` (throttled 10/min) |

### 4.1 Product Detail Page

- Image gallery from `parsed_images`.
- Specification, description, brand/category/supplier metadata.
- Star rating + review count (`review_number`, `review_avg`) and the latest 10 published reviews.
- Price/VAT/availability read from the first available stock line (falling back to the first stock line).
- "Add to cart" runs client-side; checkout re-validates stock server-side.

### 4.2 Stock Availability

The storefront lists every active product from the product catalog. Stock decides whether a product can actually be bought:

- Products without an available stock line, or whose stock has run out, remain visible and are marked **Out of Stock**; their "Add to Cart" button is disabled.
- Product detail pages remain accessible for out-of-stock products and show the stock status.
- Checkout re-validates each line against available stock and rejects the order when quantity exceeds it.
- Category, brand, and home-page product counts count all active products.

### 4.3 Checkout Flow (`StorefrontController::placeOrder`)

1. Validates customer details, country, items, and optional `shipping_method_id`.
2. Loads each product's first available stock line and rejects insufficient quantities.
3. Computes VAT per line with `VatCalculator`, adds shipping cost as a non-stock line when a method is selected.
4. Finds or creates the customer by email.
5. Creates a `Sale` with `type = Ecommerce`, `status = completed`, `paid_amount = 0`, `sale_due = grand_total`.
6. Creates `SaleDetail` rows and decrements each stock line.
7. Writes a `saleIncome` transaction, an `Income`, a receivable cashbook row, and the double-entry journal via `AccountingPoster::sale()`.

The response is JSON containing the order number, sale id, VAT, shipping, and total.

## 5. Customer Authentication & Portal

| Route | Method | Purpose |
|---|---|---|
| `/customer/register` | GET/POST | Customer registration |
| `/customer/login` | GET/POST | Customer login |
| `/customer/forgot-password` | GET/POST | Password reset request |
| `/customer/reset-password/{token}` | GET/POST | Password reset |
| `/customer/dashboard` | GET | Customer dashboard (auth) |
| `/customer/profile` | GET/PUT | Profile edit/update (auth) |
| `/customer/password` | PUT | Password update (auth) |
| `/customer/logout` | POST | Logout (auth) |
| `/portal` | GET | Portal dashboard: order count, total spent, pending count |
| `/portal/orders` | GET | Order history (sales for this customer) |
| `/portal/orders/{id}` | GET | Order detail |
| `/portal/profile` | GET/PUT | Profile view/update |

Customer auth routes use the `guest:customer` / `auth:customer` guards; the `/portal` group uses `auth:customer` (the `/customer/*` dashboard routes additionally apply `active.customer`).

## 6. Supplier Authentication & Portal

| Route | Method | Purpose |
|---|---|---|
| `/supplier/login` | GET/POST | Supplier login |
| `/supplier/forgot-password` | GET/POST | Password reset request |
| `/supplier/reset-password/{token}` | GET/POST | Password reset |
| `/supplier/dashboard` | GET | Supplier dashboard (auth + active) |
| `/supplier/logout` | POST | Logout |
| `/supplier-portal` | GET | Portal dashboard: product count, PO count, pending POs, balance, recent POs |
| `/supplier-portal/purchase-orders` | GET | PO list (filter: all/due/paid) |
| `/supplier-portal/purchase-orders/{id}` | GET | PO detail |
| `/supplier-portal/products` | GET | Supplier's active products (searchable) |
| `/supplier-portal/profile` | GET/PUT | Profile view/update (name, email, mobile, address, optional password) |

The supplier portal is read-only for procurement data; it does not upload shipment documents or generate invoices.

## 7. Shipping Calculations

- The storefront calls `GET /shipping/options?country=XX&subtotal=...`.
- Only active zones and active methods are considered; the first zone whose `countries` matches (or contains `*`) is used.
- Methods are filtered by `min_order_amount` and priced via `calculateCost()`.
- If no zone matches, the endpoint returns `methods: []` and a "No shipping options" message.
- The selected method id is passed to checkout and its cost is added to the sale's `shipping_cost`.

## 8. Reviews

- Product pages display up to 10 `published` reviews with the customer relation.
- The `reviews` table and `Review` model exist, but there is currently no storefront review-submission route or admin review controller. Reviews are read-only in the current UI.

## 9. Permissions

| Group | Permissions |
|---|---|
| E-Commerce | `ecommerce.view/save/edit/delete` |
| Reviews | `reviews.view/save/edit/delete` (no review controller/routes yet) |

Storefront, customer, and supplier portal routes are protected by their guards (`customer`, `supplier`) and the active-user middleware; they are not RBAC-gated.

## 10. Related Code Locations

- Controllers: `laravel/app/Http/Controllers/{StorefrontController,CustomerPortalController,SupplierPortalController}.php`; customer/supplier auth controllers under `Customer/Auth/` and `Supplier/Auth/`
- Models: `laravel/app/Models/{Customer,Supplier,Review,ShippingZone,ShippingMethod,Content}.php`
- Services: `laravel/app/Services/VatCalculator.php`; `laravel/app/Accounting/AccountingPoster.php`
- Views: `laravel/resources/views/storefront/`, `laravel/resources/views/customer-portal/`, `laravel/resources/views/supplier-portal/`
- Routes: `laravel/routes/web.php` (storefront, portals), `laravel/routes/customer.php`, `laravel/routes/supplier.php`
