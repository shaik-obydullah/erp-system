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
| `products` | Master product records | name, url_slug (unique), image, sku, barcode, size, color, specification, attribute, review_number, review_avg, description, status (active/inactive/archive) |
| `brands` | Brands and models (self-referencing) | fk_brand_id (parent model), serial, name, url_slug, status |
| `categories` | Categories and subcategories (self-referencing) | fk_category_id (parent), serial, name, url_slug (unique), status |
| `units` | Units of measurement | name, status |
| `sizes` | Product sizes | name, status |
| `colors` | Product colors | name, status |

### 2.1 Products Foreign Keys

| Column | References | Meaning |
|---|---|---|
| `fk_brand_id` | brands.id | Brand |
| `fk_model_id` | brands.id | Model (also a brand row) |
| `fk_category_id` | categories.id | Category |
| `fk_subcategory_id` | categories.id | Subcategory (also a category row) |
| `fk_item_id` | categories.id | Item-level classification |
| `fk_supplier_id` | suppliers.id | Preferred supplier |
| `fk_unit_id` | units.id | Unit of measure |

## 3. Key Models & Relationships

- **Product** (`app/Models/Product.php`) — `brand()`, `category()`, `subcategory()`, `supplier()`, `unit()`, `stocks()` (1:N), `reviews()` (1:N). Auto-generates `url_slug` on create when empty.
- **Product computed attributes:**
  - `sale_price` / `buy_price` — from the first active stock record.
  - `stock_quantity` — sum of quantity across active stocks.
  - `parsed_images` / `first_image_url` — JSON image array handling; resolves storage or external URLs.
- **Category** — self-referencing: `parent()` and `children()`, plus `products()`.
- **Brand** — self-referencing for brand → model hierarchy; `fk_brand_id` points to the parent brand row.

## 4. Admin Surface (Routes)

All routes live inside the `auth:admin` group in `routes/web.php`. Full resource controllers (CRUD excluding `show`) plus import/export:

| Route Prefix | Controller | Permissions | Import / Export |
|---|---|---|---|
| `products` | ProductController | products.{view,save,edit,delete} | `GET products/export`, `POST products/import`, `GET products/barcodes`, `POST products/upload-media` |
| `brands` | BrandController | brands.* | `GET brands/export`, `POST brands/import` |
| `categories` | CategoryController | categories.* | `GET categories/export`, `POST categories/import` |
| `units` | UnitController | units.* | `GET units/export`, `POST units/import` |
| `sizes` | SizeController | sizes.* | `GET sizes/export`, `POST sizes/import` |
| `colors` | ColorController | colors.* | `GET colors/export`, `POST colors/import` |

### 4.1 Product Features

- **Barcode sheet** — `GET /products/barcodes` renders printable barcodes from SKU/barcode fields.
- **Media upload** — `POST /products/upload-media` handles product images (stored under `public/uploads/products/`); image column stores a JSON array, with a fallback for single-string values.
- **CSV import/export** for products, brands, categories, units, sizes, colors.
- **Cascading dropdowns** — Brand → Model and Category → Subcategory → Item are AJAX-loaded in the admin forms.

## 5. API Surface (React POS)

Sanctum-protected endpoints in `routes/api.php` handled by `ApiController`:

| Endpoint | Method | Purpose |
|---|---|---|
| `GET /product` | GET | Paginated product list with stocks; search by name/SKU/barcode |
| `POST /save-product` | POST | Create product (multipart, supports image) |
| `POST /update-product/{id}` | POST | Update product |
| `DELETE /delete-product/{id}` | DELETE | Soft-delete product (records deleted_by) |
| `POST /category` / `save-category` / `update-category/{id}` / `delete-category/{id}` | POST/POST/PUT/DELETE | Category CRUD from POS terminal |

All CRUD operations in the API invoke `ActivityLogger::created/updated/deleted`.

## 6. Product Status Lifecycle

Products use a three-state status: `active` (sellable, visible in POS/storefront), `inactive` (hidden, kept in catalog), `archive` (retired). Stock records share the same `active`/`inactive`/`archive` enumeration. Soft deletes are used everywhere; all tables track `created_by`, `updated_by`, `deleted_by`.

## 7. AI Integration Points

- **AI product description** — `POST /api/v1/ai/product-description` generates SEO-friendly copy from name/category/specs (see AI module doc).
- **AI product search** — natural-language search over the product catalog.
- **AI price suggestion** — price recommendations per product using sales history.

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
- Migrations: `laravel/database/migrations/` (0001_01_01_0000{13..19})
