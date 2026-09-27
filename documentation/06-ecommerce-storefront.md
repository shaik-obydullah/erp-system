# Module 06 — E-Commerce & Storefront

**Module:** E-Commerce & Storefront (E-Commerce)
**Category:** Online Selling
**Purpose:** Powers the customer-facing storefront: browse categories, product detail with reviews, cart/checkout, order tracking, and a supplier-facing portal with shipment uploads and invoice generation.

> The storefront is the public face of the catalog. It reuses the same product/CMS data as the admin panel, adds customer-specific pricing, and integrates the supplier portal so vendors can fulfil orders and upload shipment documents directly.

## 1. Overview

The module is organized into three surfaces:

- **Storefront (customer)** — browse, product pages, cart, checkout, my orders, reviews.
- **Supplier portal** — login, shipments list, shipment detail/upload, invoice generation.
- **Banners & marketing** — banner rotations and promotional media.

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `customers` | Shopper accounts | name, email (unique), password, phone, address, balance, status |
| `carts` / `cart_attributes` | Session carts | See Sales & POS module |
| `orders` / `order_details` | Customer orders | See Sales & POS module |
| `reviews` | Order-line reviews | See Sales & POS module |
| `shipments` | Supplier uploads | fk_order_id, fk_supplier_id, file_path, tracking_no, status |
| `banners` | Storefront/POS banners | name, image, position, link, status |
| `contents` | CMS pages | See Administration & System module |

### 2.1 Shipment Status Lifecycle

| Status | Meaning |
|---|---|
| `pending` | Shipment created, awaiting document upload |
| `uploaded` | Document attached; awaiting processing |
| `processed` | Supplier processed the shipment |
| `cancelled` | Shipment voided |

## 3. Key Models

- **Customer** (`app/Models/Customer.php`) — `isActive()`, `isDue()` (balance < 0), `balances()`, `cashbookEntries()`, `orders()`.
- **Shipment** (`app/Models/Shipment.php`) — `order()`, `supplier()`; file stored under `public/uploads/shipments/`.
- **Banner** — position-keyed (top_banner_1/2/3) with link and status.
- **Content** — CMS pages rendered at storefront slugs.

## 4. Storefront (Customer) Routes

| Route | Method | Purpose |
|---|---|---|
| `/` (home) | GET | Hero/banners, featured categories, featured products |
| `/shop` | GET | Full catalog with category/price filters + search |
| `/product/{url_slug}` | GET | Product detail: gallery, specs, reviews, stock status |
| `/cart`, `/cart/add`, `/cart/update/{id}`, `/cart/remove/{id}` | GET/POST | Cart lifecycle |
| `/checkout` | POST | Create order from cart; storefront totals incl. VAT/tax |
| `/my-orders` | GET | Order history with status + tracking |
| `/reviews/store` | POST | Submit order-line review |
| `/login`, `/register`, `/forgot-password`, `/reset-password` | GET/POST | Customer auth (customer guard) |

### 4.1 Product Detail Page

- Image gallery from `parsed_images`.
- Specification table (`specification` JSON), description, attributes.
- Star rating + review count (`review_number`, `review_avg`).
- Price from first active stock; low-stock badge when under `stock_warning_level`.
- "Add to cart" with size/color attribute selection.

### 4.2 Cart Behavior

- Guest carts keyed by session id; merged into the customer account after login.
- Line extras: `extra_charge`, `discount`, `cost`/`subtotal`.
- Checkout applies storefront VAT/tax and creates a `pending`-payment order.

## 5. Supplier Portal Routes

| Route | Method | Purpose |
|---|---|---|
| `/supplier/login`, `/supplier/password/reset` | GET/POST | Supplier auth (supplier guard) |
| `/supplier/dashboard` | GET | KPIs: shipments count, uploaded, processed |
| `/supplier/shipments` | GET | Shipment list (filterable by status) |
| `/supplier/shipments/{id}` | GET | Shipment detail |
| `/supplier/shipments/{id}/upload` | POST | Upload shipment document (PDF/Image), sets `uploaded` |
| `/supplier/shipments/{id}/invoice` | GET | Generate/download invoice PDF |
| `/supplier/shipments/{id}/edit` | PUT | Update shipment record |

### 5.1 Invoice Generation

`GET /supplier/shipments/{id}/invoice` streams a PDF invoice from the shipment/order data (browser-printable template), stamped with the supplier's identity and shipment reference.

## 6. Banners

- `GET /banners` admin list; storefront renders active banners by `position`.
- Banners link out to products, CMS pages, or external URLs.

## 7. Permissions

| Group | Permissions |
|---|---|
| Banners | `banners.view/save/edit/delete` |
| Reviews | `reviews.view/save/edit/delete` |
| CMS Content | `contents.view/save/edit/delete` (via Administration module) |

## 8. Related Code Locations

- Controllers: `laravel/app/Http/Controllers/` (ECommerce front controllers, Customer auth, Supplier portal)
- Models: `laravel/app/Models/{Customer,Shipment,Banner,Content}.php`
- Views: `laravel/resources/views/front/`, `laravel/resources/views/supplier/`
- Routes: `laravel/routes/web.php` (storefront + supplier sections), `laravel/routes/customer.php`, `laravel/routes/supplier.php`
