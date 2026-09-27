# React POS — Point of Sale System

A modern, full-featured Point of Sale (POS) system built with React 19, Tailwind CSS, and a Laravel API backend. Designed for retail and small business inventory and sales management.

## Features

### POS Terminal
- **Category-Based Browsing** — Horizontal scrollable category tabs with product grid
- **Stock Search** — Debounced search across product name, batch, lot, SKU, barcode
- **Shopping Cart** — Add/remove items, quantity controls, cart persistence via localStorage
- **Checkout** — Customer assignment, discount, VAT, tax calculation, one-click checkout
- **Image Display** — Product images with emoji fallbacks

### CRUD Management
- **Customers** — Create, list, search, edit (modal), delete (confirmation)
- **Categories** — Create, list, search, edit (modal), delete (confirmation)
- **Products** — Create with image upload, list, search, edit (modal with preview), delete
- **Stocks** — Create with infinite-scroll product search, list with pagination
- **Sales** — List with summary cards, detail modal, PDF invoice download, delete

### Finance
- **Income Tracking** — Create, list with date range filtering, running totals
- **Expense Tracking** — Create, list with date range filtering, running totals

### Reporting
- **Overview** — Total customers, stock value, cart total, sales growth
- **Sales Charts** — Sales trend (line), income vs expenses (bar), profit analysis
- **Financial Charts** — Income by month (pie), expenses by month (pie)
- **Annual Performance** — Yearly revenue, expenses, profit, year-over-year change

### AI Features
- Product description generation
- Natural language product search
- Inventory insights and stockout risk analysis
- Sales forecasting
- Price optimization
- Customer support chatbot

### System
- **Authentication** — Token-based login via Laravel Sanctum
- **Protected Routes** — Auth guard redirects to login
- **Toast Notifications** — Success/error feedback via react-toastify
- **Responsive Design** — Mobile-friendly collapsible sidebar
- **PDF Generation** — Invoice download via jsPDF

## Tech Stack

| Layer | Technology |
|-------|-----------|
| UI | React 19, React Router 6, Tailwind CSS 3 |
| State | React Context + useState (no Redux) |
| HTTP | Axios with Bearer token interceptors |
| Charts | Chart.js 4 + react-chartjs-2 |
| PDF | jsPDF 3 + jspdf-autotable 5 |
| Notifications | react-toastify 11 |
| Icons | react-icons (FontAwesome) |
| Utilities | lodash (debounce) |
| Build | Vite 6 |

## Project Structure

```
src/
├── api/
│   └── axios.js              # Centralized API client + 35+ endpoint functions
├── components/
│   └── PrivateRoute.js       # Auth guard wrapper
├── contexts/
│   └── ConfigContext.js      # Global config (currency, VAT, tax, project name)
├── layout/
│   └── Layout.js             # Sidebar + topbar app shell
├── pages/
│   ├── LoginPage.js          # Email/password authentication
│   ├── Logout.js             # Token cleanup + redirect
│   ├── DashboardPage.js      # POS wrapper
│   ├── MainContent.js        # POS terminal (categories, cart, checkout)
│   ├── CustomerPage.js       # Customer list + edit modal
│   ├── AddCustomer.js        # Customer create form
│   ├── CategoryPage.js       # Category list + edit modal
│   ├── AddCategory.js        # Category create form
│   ├── ProductPage.js        # Product list + edit modal with image upload
│   ├── AddProduct.js         # Product create form
│   ├── StockPage.js          # Stock list (read-only)
│   ├── AddStock.js           # Stock create with infinite-scroll search
│   ├── SalePage.js           # Sales list + detail modal + PDF invoice
│   ├── IncomePage.js         # Income list with date filtering
│   ├── AddIncome.js          # Income create form
│   ├── ExpensePage.js        # Expense list with date filtering
│   ├── AddExpense.js         # Expense create form
│   └── ReportPage.js         # Charts dashboard (3 tabs)
├── App.jsx                   # Router, ToastContainer, ConfigProvider
├── main.jsx                  # Entry point
└── index.css                 # Tailwind directives
```

## API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/login` | Authenticate user |
| POST | `/logout` | Invalidate session |
| POST | `/configuration` | App config (public) |
| POST | `/dashboard` | POS categories |
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
| POST | `/ai/product-description` | Generate description |
| POST | `/ai/product-search` | AI search |
| POST | `/ai/inventory-insights` | Inventory analysis |
| POST | `/ai/sales-forecast` | Sales prediction |
| POST | `/ai/customer-support` | AI support |
| POST | `/ai/price-suggestion` | Price optimization |

## Getting Started

### Prerequisites
- Node.js 18+
- npm or yarn
- Laravel backend running

### Environment Setup

Create `.env`:
```bash
VITE_API_URL=http://localhost:8082/api/v1              # Local dev
VITE_API_URL_PRODUCTION=https://erp.obydullah.com/api/v1  # Production
```

### Development
```bash
npm install
npm run dev
```
Dev server runs at `http://localhost:3060`. API requests are proxied to Laravel via Vite.

### Build
```bash
npm run build
```
Output goes to `dist/`.

### Default Credentials
```
Email:    admin@erp.com
Password: password
```

## License

Private use.
