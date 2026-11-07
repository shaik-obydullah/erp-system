# Module 03 — Catalog Management

**Module:** Catalog Management (Catalog)
**Category:** Product Data
**Purpose:** Manages the product master data and its attribute taxonomy — brands, models, categories, subcategories, sizes, colors, and units — feeding both the admin panel and the storefront.

> The catalog is the single source of truth for product information. Products reference attribute tables (brand, category, unit, etc.) rather than storing denormalized text, keeping the data consistent across POS, storefront, and reports.

## 1. Overview

The catalog module is split into two layers:

- **Attribute taxonomy** — brands, models, categories, subcategories, sizes, colors, units. Each supports full CRUD plus CSV export/import.
- **Products** — the master record linking all attributes together, with rich metadata, images, SKU/barcode, reviews, and a status lifecycle.

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `products` | Master product records | name, url_slug, image (JSON array), sku, barcode, size, color, specification, attribute, review_number, review_avg, description, status (active/inactive/archive) |
| `brands` | Brands and models (self-referencing) | fk_brand_id (parent model), serial, name, url_slug, status |
| `categories` | Categories and subcategories (self-referencing) | fk_category_id (parent), serial, name, url_slug (unique), status |
| `units` | Units of measurement | name, status |
| `sizes` | Product sizes | name, status |
| `colors` | Product colors | name, status |

### 2.1 Products Foreign Keys

| Column | References | Meaning |
|---|---|---|
| `fk_brand_id` | brands.id | Brand |
| `fk_model_id` | brands.id | Model (also a brand row; legacy/optional) |
| `fk_category_id` | categories.id | Category |
| `fk_subcategory_id` | categories.id | Subcategory (also a category row) |
| `fk_item_id` | categories.id | Item-level classification (legacy/optional) |
| `fk_supplier_id` | suppliers.id | Preferred supplier |
| `fk_unit_id` | units.id | Unit of measure |

The product form currently writes brand, category, subcategory, supplier, and unit. `fk_model_id` and `fk_item_id` remain available in the schema for classification but are not edited by the current admin form.

## 3. Key Models & Relationships

- **Product** (`app/Models/Product.php`) — `brand()`, `category()`, `subcategory()`, `supplier()`, `unit()`, `stocks()` (1:N), `reviews()` (1:N). Auto-generates `url_slug` on create when empty; `scopeSellable()` filters products with available stock.
- **Product computed attributes:**
  - `parsed_images` / `first_image_url` — JSON image array handling; resolves storage or external URLs.
- **No finance data:** pricing, tax/VAT, stock quantities and ledger links belong to stock lines, not products. Product CRUD must not post journal entries or touch finance tables.
- **Category** — self-referencing: `parent()` and `children()`, plus `products()`.
- **Brand** — self-referencing for brand → model hierarchy; `fk_brand_id` points to the parent brand row.

## 4. Admin Surface (Routes)

All routes live inside the `auth:admin` + `active.admin` group in `routes/web.php`. Full resource controllers (CRUD excluding `show`) plus import/export:

| Route Prefix | Controller | Permission Group | Import / Export |
|---|---|---|---|
| `products` | ProductController | `products.*` | `GET products/export`, `POST products/import`, `GET products/barcodes`, `POST products/upload-media` |
| `brands` | BrandController | `brands.*` | `GET brands/export`, `POST brands/import` |
| `categories` | CategoryController | `categories.*` | `GET categories/export`, `POST categories/import` |
| `units` | UnitController | `units.*` | `GET units/export`, `POST units/import` |
| `sizes` | SizeController | `sizes.*` | `GET sizes/export`, `POST sizes/import` |
| `colors` | ColorController | `colors.*` | `GET colors/export`, `POST colors/import` |

The export/import endpoints and product extra endpoints carry explicit `permission:` middleware. The resource CRUD routes themselves currently rely on the `auth:admin` + `active.admin` group and menu visibility rather than per-route permission checks.

### 4.1 Product Features

- **Barcode sheet** — `GET /products/barcodes` renders printable barcodes from SKU/barcode fields.
- **Media upload** — `POST /products/upload-media` handles product images (stored under the public uploads disk); the `image` column stores a JSON array, with a fallback for single-string values.
- **CSV import/export** for products, brands, categories, units, sizes, colors.
- **Cascading dropdowns** — Category → Subcategory are AJAX-loaded in the admin product forms.

## 5. API Surface (React POS)

Sanctum-protected endpoints in `routes/api.php` handled by `ApiController`:

| Endpoint | Method | Purpose |
|---|---|---|
| `GET /product` | GET | Paginated product list with stocks; search by name/SKU/barcode |
| `POST /save-product` | POST | Create product (multipart, supports image) |
| `POST /update-product/{id}` | POST | Update product |
| `POST /category` | POST | List categories |
| `POST /save-category` | POST | Create category |
| `PUT /update-category/{id}` | PUT | Update category |

There are currently no API delete endpoints for products or categories. All create/update operations invoke `ActivityLogger::created/updated`.

## 6. Product Status Lifecycle

Products use a three-state status: `active` (sellable, visible in POS/storefront), `inactive` (hidden, kept in catalog), `archive` (retired). Stock records share the same `active`/`inactive`/`archive` enumeration. Soft deletes are used everywhere; all tables track `created_by`, `updated_by`, `deleted_by`.

## 7. AI Integration Points

- **AI product description** — `POST /api/v1/ai/product-description` generates SEO-friendly copy from name/category/specs (Sanctum-protected; see AI module doc).
- **AI product search** — `POST /api/v1/ai/product-search`, natural-language search over the product catalog (Sanctum-protected).
- **AI price suggestion** — `POST /api/v1/ai/price-suggestion`, price recommendations per product using sales history (Sanctum-protected).

## 8. Permissions

| Group | Permissions |
|---|---|
| Products | `products.view/save/edit/delete` |
| Categories | `categories.view/save/edit/delete` |
| Brands | `brands.view/save/edit/delete` |
| Sizes | `sizes.view/save/edit/delete` |
| Colors | `colors.view/save/edit/delete` |
| Units | `units.view/save/edit/delete` |

## 9. Related Code Locations

- Models: `laravel/app/Models/{Product,Category,Brand,Unit,Size,Color}.php`
- Controllers: `laravel/app/Http/Controllers/{ProductController,BrandController,CategoryController,UnitController,SizeController,ColorController}.php`
- API: `laravel/app/Http/Controllers/Api/ApiController.php` (product/category methods)
- Migrations: `laravel/database/migrations/0001_01_01_000013` through `0001_01_01_000019` (brands, categories, units, sizes, colors, suppliers, products)
