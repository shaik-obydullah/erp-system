# React POS

A modern, full-featured Point of Sale (POS) system built with React, Tailwind CSS, and a Laravel API backend. Designed for retail and small business inventory and sales management.

## Features

- **Dashboard** — Sales overview with category cards, monthly/yearly charts, and real-time stats
- **POS Terminal** — Category-based product browsing, stock search, cart with discounts/VAT/tax, and one-click checkout
- **Customer Management** — CRUD operations with search and pagination
- **Category Management** — CRUD with parent-child relationships
- **Product Management** — CRUD with image upload, inline edit modal, AI-powered descriptions
- **Stock Management** — Batch/lot tracking, SKU, barcode, buy/sale pricing, quantity control
- **Sales** — Paginated list, detail modal, PDF invoice generation, delete
- **Incomes & Expenses** — Track financial transactions with date filtering
- **Reports** — Monthly sales, growth %, stock value, cart totals
- **AI Features** — Product descriptions, inventory insights, sales forecasts, price suggestions (via Ollama)
- **Auth** — Token-based login (Laravel Sanctum), protected routes
- **Responsive** — Mobile-friendly collapsible sidebar
- **Toast Notifications** — Non-intrusive success/error feedback via react-toastify

## Tech Stack

| Layer | Technology |
|-------|-----------|
| UI | React 19, Tailwind CSS 3, React Router 6 |
| HTTP | Axios with interceptors |
| Charts | Chart.js + react-chartjs-2 |
| PDF | jsPDF + jspdf-autotable |
| Notifications | react-toastify |
| Icons | react-icons |
| Build | Vite 6 |
| Backend | Laravel (Sanctum auth) |

## Project Structure

```
src/
├── api/
│   └── axios.js            # API client, interceptors, all endpoint functions
├── components/
│   └── PrivateRoute.js     # Auth guard wrapper
├── contexts/
│   └── ConfigContext.js     # Global config (currency, VAT, tax, project name)
├── layout/
│   └── Layout.js           # Sidebar + topbar shell
├── pages/
│   ├── LoginPage.js        # Email/password auth
│   ├── Logout.js           # Clear token, redirect
│   ├── DashboardPage.js    # POS terminal with categories, cart, checkout
│   ├── MainContent.js      # POS core: stock search, cart logic, checkout
│   ├── CustomerPage.js     # Customer list + search
│   ├── AddCustomer.js      # Customer form
│   ├── CategoryPage.js     # Category list + search
│   ├── AddCategory.js      # Category form
│   ├── ProductPage.js      # Product list + edit modal
│   ├── AddProduct.js       # Product form with image upload
│   ├── StockPage.js        # Stock list + search
│   ├── AddStock.js         # Stock form with product search
│   ├── SalePage.js         # Sales list, detail modal, PDF invoice
│   ├── IncomePage.js       # Income list
│   ├── AddIncome.js        # Income form
│   ├── ExpensePage.js      # Expense list
│   ├── AddExpense.js       # Expense form
│   └── ReportPage.js       # Dashboard stats and charts
├── App.jsx                 # Router, providers, toast config
├── main.jsx                # Entry point
└── index.css               # Tailwind base styles
```

## Getting Started

### Prerequisites

- Node.js 18+
- npm or yarn
- Laravel backend running (local or production)

### Environment Setup

Create `.env` in the project root:

```bash
VITE_API_URL=http://localhost:8082/api/v1    # Local dev (proxied via Vite)
VITE_API_URL_PRODUCTION=https://erp.obydullah.com/api/v1  # Production
```

### Development

```bash
npm install
npm run dev
```

The dev server runs on `http://localhost:3060`. API requests to `/api` are proxied to the Laravel backend via Vite's dev server proxy.

### Build

```bash
npm run build
```

Output goes to `dist/`.

### Production Deployment

The `VITE_API_URL` is baked into the build at compile time. For production builds, ensure `.env.production` exists with the correct API URL before running `npm run build`.

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/login` | Authenticate user |
| POST | `/logout` | Invalidate session |
| POST | `/configuration` | Fetch app config (public) |
| POST | `/dashboard` | POS dashboard categories |
| POST | `/category-product/{id}` | Stocks by category |
| POST | `/customer` | List customers |
| POST | `/save-customer` | Create customer |
| PUT | `/update-customer/{id}` | Update customer |
| DELETE | `/delete-customer/{id}` | Delete customer |
| POST | `/category` | List categories |
| POST | `/save-category` | Create category |
| PUT | `/update-category/{id}` | Update category |
| DELETE | `/delete-category/{id}` | Delete category |
| GET | `/product` | List products (paginated) |
| POST | `/save-product` | Create product |
| POST | `/update-product/{id}` | Update product (multipart) |
| DELETE | `/delete-product/{id}` | Delete product |
| GET | `/stock` | List stocks (paginated) |
| POST | `/save-stock` | Create stock |
| GET | `/sale` | List sales (paginated) |
| POST | `/select-sale/{id}` | Sale details |
| POST | `/save-sale` | Checkout / create sale |
| DELETE | `/delete-sale/{id}` | Delete sale |
| POST | `/income` | List incomes |
| POST | `/save-income` | Create income |
| POST | `/expense` | List expenses |
| POST | `/save-expense` | Create expense |
| POST | `/report` | Report data |
| GET | `/ai/status` | AI service status |
| POST | `/ai/product-description` | Generate product description |
| POST | `/ai/product-search` | AI-powered product search |
| POST | `/ai/inventory-insights` | Inventory analysis |
| POST | `/ai/sales-forecast` | Sales prediction |
| POST | `/ai/customer-support` | AI customer support |
| POST | `/ai/price-suggestion` | Price optimization |

## Default Credentials

```
Email:    admin@erp.com
Password: password
```

## License

Private use.
