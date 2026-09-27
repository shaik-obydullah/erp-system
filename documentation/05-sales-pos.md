# Module 05 — Sales & POS

**Module:** Sales & POS (Sales)
**Category:** Revenue
**Purpose:** Handles the complete order lifecycle — cart → order placement → payment → shipment → review — across two surfaces: the admin/POS panel and the React 19 POS terminal, with full order status and payment tracking.

> Orders are the revenue backbone. Every order references a customer, records its own total/tax/discount/due balance, and rolls into the cashbook when paid. Shipment and review phases are tracked on the order row itself.

## 1. Overview

The sales module provides:

- **Order management** — create, list, view, update, delete orders; status pipeline.
- **POS terminals** — the Vue-based admin POS screen and the React 19 POS app.
- **Cart & checkout** — guest/registered customer carts, VAT/tax application.
- **Shipments & returns** — tracking numbers and review workflow.
- **Customer reviews** — rating + comment per order line.

## 2. Database Tables

| Table | Purpose | Key Columns |
|---|---|---|
| `orders` | Order header | fk_customer_id, status (orderPlaced/shipping/delivered/returned), shipped_on, delivered_on, delivered_to_name, delivered_to_phone, address, fk_payment_method_id, total, discount, vat, subtotal, due, grand_total, paid |
| `order_details` | Order lines | fk_order_id, fk_product_id, fk_brand_id, fk_color_id, fk_size_id, quantity, unit_price, discount, subtotal, review_number, review_comment, review_status |
| `payments` | Payment ledger | fk_order_id, fk_payment_method_id, fk_admin_id, fk_customer_id, amount, invoice_no, note |
| `carts` | Session carts | fk_session_id, fk_product_id, quantity, cost, subtotal, extra_charge, discount |
| `cart_attributes` | Cart line attributes | fk_cart_id, fk_product_id, attribute_type, attribute_id, attribute_value |
| `reviews` | Customer reviews | fk_order_detail_id, rating, comment, status |
| `shipment_reviews` | Delivery review | fk_order_id, comment, rating |
| `banners` | POS/storefront banners | name, image, position (top_banner_1/2/3), link, status |
| `order_status` | Status pipeline log | fk_order_id, status |

### 2.1 Order Status Lifecycle

```
orderPlaced → shipping → delivered → (returned)
```

| Status | Set By | Notes |
|---|---|---|
| `orderPlaced` | Checkout / POS | Order created; payment may be pending (due > 0) |
| `shipping` | Admin | Marks in-transit; `shipped_on` stamped |
| `delivered` | Admin | `delivered_on` stamped; delivery recipient recorded |
| `returned` | Admin | Reverse flow; stock may be re-entered |

## 3. Key Models & Relationships

- **Order** (`app/Models/Order.php`) — `customer()`, `details()` (order_details), `payments()`, `paymentMethod()`, `shipmentReview()`, `statusLogs()`. Computed totals: `total`, `discount`, `vat`, `subtotal`, `due`, `grand_total`, `paid`.
- **OrderDetail** — `order()`, `product()`, `review()`.
- **Payment** — `order()`, `paymentMethod()`, `admin()`, `customer()`.
- **Cart** — session-scoped (`fk_session_id`) lines with `cost`, `subtotal`, `extra_charge`, `discount`.
- **Review / ShipmentReview** — order-line rating (1–5) + comment; shipment review on the order.

## 4. Admin Surface (Routes)

| Route Prefix | Controller | Permissions |
|---|---|---|
| `orders` | OrderController | orders.{view,save,edit,delete} |
| `pos` | PosController | pos.view |
| `reviews` | ReviewController | reviews.{view,save,edit,delete} |
| `shipment-reviews` | ShipmentReviewController | reviews.* |
| `banners` | BannerController | banners.{view,save,edit,delete} |
| `payment-methods` | PaymentMethodController | settings.* (managed under Settings) |

### 4.1 Order Features

- **Filtering** by status, date range, customer, payment method; search by customer/invoice.
- **Create/Edit** — pick customer, add products (search by name/SKU/barcode), set quantities, apply discount; VAT/discount applied automatically; due balance computed.
- **Payment capture** — inline payment form; overpayment handled as customer balance credit.
- **Status updates** with `order_status` timeline logging and audit entries.
- **Print invoice** — printable receipt template.

### 4.2 POS Terminal (Admin)

`GET /pos` (`permission:pos.view`) — the in-browser cashier screen: product grid with categories, search, cart panel, VAT/tax application, discount, customer selection, and payment method. On submit it creates the order, records the payment, updates inventory (OUT movement), writes cashbook entry, and sends an in-app notification.

## 5. API Surface (React POS & Storefront)

### 5.1 React POS (Sanctum, `routes/api.php`)

| Endpoint | Method | Purpose |
|---|---|---|
| `GET /sale` | GET | List orders (paginated, searchable) |
| `POST /sale` | POST | Create order (items, customer, payment) |
| `GET /sale/{id}` | GET | Order detail |
| `PUT /sale/{id}` | PUT | Update order |
| `DELETE /sale/{id}` | DELETE | Delete order |
| `POST /payment` | POST | Record payment on order |
| `POST /cart` / `PUT /cart/{id}` / `DELETE /cart/{id}` | POST/PUT/DELETE | Cart management from POS |
| `POST /save-review` | POST | Post a review |

### 5.2 Storefront

Public/customer routes handle guest & registered checkout (cart add/update/remove, checkout, my-orders), the review submission flow, and shipment feedback.

## 6. Tax & Discount Computation

- **VAT** — applied at rate `Configuration::get('vat_percentage')`.
- **Tax** — additional rate `Configuration::get('tax_percentage')`.
- **Discount** — flat amount or percent; stored on the order and per line.
- Order math: `subtotal` = Σ(line qty × unit_price − line discount); `grand_total` = subtotal − order discount + vat + tax; `due` = grand_total − paid.

## 7. Inventory & Finance Integration

- **On order creation (paid):** stock OUT movement recorded per line → `stocks` ledger.
- **On payment:** `payments` row + cashbook entry (income, cash/due bucket).
- **On return:** reverse movement; balance/refund applied to customer balance.

## 8. Permissions

| Group | Permissions |
|---|---|
| Orders | `orders.view/save/edit/delete` |
| POS | `pos.view` |
| Reviews | `reviews.view/save/edit/delete` |
| Banners | `banners.view/save/edit/delete` |
| Payment Methods | `settings.view/save` |

## 9. Related Code Locations

- Models: `laravel/app/Models/{Order,OrderDetail,Payment,Cart,CartAttribute,Review,ShipmentReview,Banner,OrderStatus}.php`
- Controllers: `laravel/app/Http/Controllers/{OrderController,PosController,ReviewController,ShipmentReviewController,BannerController,PaymentMethodController}.php`
- API: `laravel/app/Http/Controllers/Api/ApiController.php` (sale/payment/cart/review methods)
- POS UI: `laravel/resources/views/pos/`
