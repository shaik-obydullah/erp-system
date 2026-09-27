# Module 12 — AI & Business Intelligence

**Module:** AI & Business Intelligence (AI)
**Category:** Intelligence
**Purpose:** Adds an AI copilot layer over the entire ERP: a support assistant, natural-language product search, product description and price generation, sales forecasting, and executive-level analytical reports.

> This module sits on top of all others. It consumes the data already captured (sales, stock, catalog, customers) and exposes AI-assisted actions both to admins and to the storefront/API.

## 1. Overview

The module provides:

- **AI support assistant** — a chat widget trained on ERP context (`project_name` config).
- **AI product search** — natural-language querying of the catalog.
- **AI product description** — generated SEO copy from product attributes.
- **AI price suggestion** — data-driven price recommendations.
- **Sales forecasting** — projected revenue for the coming period.
- **Analytical reports** — dashboard-grade BI views.

## 2. AI Endpoints (API)

All endpoints are under `/api/v1/ai` (some public, some Sanctum-protected):

| Endpoint | Method | Access | Purpose |
|---|---|---|---|
| `POST /ai/chat` | POST | Public | Support-assistant conversation (uses `project_name`) |
| `POST /ai/product-search` | POST | Public | Natural-language product search |
| `POST /ai/product-description` | POST | Sanctum | Generate product description |
| `POST /ai/price-suggestion` | POST | Sanctum | Suggest price for a product |
| `POST /ai/forecast` | POST | Sanctum | Sales forecast for a period |

### 2.1 Support Assistant (`/ai/chat`)

- Contextual bot answering questions about the business, its products, and processes.
- Branding driven by the `project_name` configuration; deployed on the storefront and admin panel.

### 2.2 Product Search (`/ai/product-search`)

- Accepts free-text queries ("red t-shirt under 1000") and resolves them to catalog matches.
- Result scoring based on product attributes (category, brand, color, size, price).

### 2.3 Description & Pricing

- **Description:** generates search-optimized copy from name, category, specification, and unit.
- **Price suggestion:** combines historical sales, buy price, and margin config into a recommended price.

### 2.4 Forecasting (`/ai/forecast`)

- Uses past monthly sales/expenses to project the next period; output feeds the dashboard charts.

## 3. BI & Analytical Reports

The Reports module (`reports.*` permissions) renders executive analytics:

- **Sales report** — by day/week/month, channel, category, product, employee.
- **Profit & Loss** — income vs expense per period.
- **Stock / inventory valuation** — current value at cost and retail.
- **Low-stock & reorder** — products under `stock_warning_level`.
- **Customer & supplier balances** — receivables and payables.
- **Employee / payroll summaries** — headcount and payroll totals.
- **Campaign performance** — delivered/sent metrics.

| Report Group | Key Outputs |
|---|---|
| Sales & Revenue | totals, trends, top products/categories, channel split |
| Financial | P&L, cashbook summaries, bank reconciliation |
| Inventory | valuation, movement summary, low-stock list |
| CRM | customer lifetime value, supplier dues |
| HR | attendance, payroll, leave utilization |
| Marketing | campaign delivery stats |

## 4. Dashboard & Charting

- Dashboard KPIs and Chart.js visuals consume aggregated arrays (`monthlySales`, `monthlyExpenses`, top categories, recent sales).
- AI forecasts plug into the same chart layer.

## 5. Integration Points

| Consumer | Feature |
|---|---|
| Admin panel | Dashboard charts, AI support widget, reports |
| React POS | Product search, description/price helpers |
| Storefront | AI chat support, natural-language search |

## 6. Permissions

| Group | Permissions |
|---|---|
| Reports | `reports.view` |
| AI endpoints | Public chat/search; Sanctum for description/price/forecast |

## 7. Related Code Locations

- AI service: `laravel/app/Services/AiService.php` (or equivalent) — providers for chat/search/description/price/forecast.
- API: `laravel/app/Http/Controllers/Api/AiController.php`
- Reports: `laravel/app/Http/Controllers/ReportController.php` + `laravel/resources/views/reports/`
- Config: `project_name` in `configurations` table (AI branding/context)
