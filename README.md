# ERP System

Full-stack multi-vendor marketplace and POS system with Laravel API, React frontend, and AI-powered features.

## Live Demo

- **Admin Panel:** [erp.obydullah.com](https://erp.obydullah.com)
- **React POS:** [react-pos.obydullah.com](https://react-pos.obydullah.com)

## Architecture

```
react-pos.obydullah.com (React POS)
        │
        ▼
   Apache / Nginx
        │
        ▼
erp.obydullah.com (Laravel API + Storefront)
        │
        ├── MariaDB
        ├── Ollama (llama3.2)
        └── Storage (product images)
```

## Tech Stack

| Layer      | Technology                          |
| ---------- | ----------------------------------- |
| Backend    | Laravel 13, PHP 8.3, Sanctum Auth   |
| Frontend   | React 19, Vite 6, Tailwind CSS 3    |
| Storefront | Laravel Blade, Vue.js 3, Alpine.js  |
| Database   | MariaDB 10.6                        |
| AI         | Ollama (llama3.2) — self-hosted LLM |
| Infra      | Docker, Nginx, Apache               |
| PDF        | jsPDF + jspdf-autotable             |
| Charts     | Chart.js 4 + react-chartjs-2        |

## Key Modules

| Module              | Description                                                                           |
| ------------------- | ------------------------------------------------------------------------------------- |
| **POS Terminal**    | Category browsing, cart, checkout, VAT/tax/discount, PDF invoices                     |
| **Product Catalog** | Categories, brands, units, sizes, colors, image upload                                |
| **Inventory**       | Multi-warehouse stock with batch/lot tracking                                         |
| **Sales**           | Full lifecycle — sale creation, returns, invoice generation                           |
| **Finance**         | Income/expense tracking, double-entry transactions, cashbook, multi-currency          |
| **Procurement**     | Need → Purchase Order → Shipment → Returns pipeline                                   |
| **Manufacturing**   | Bill of Materials, production planning, cost tracking                                 |
| **HRM**             | Employees, payroll, task management                                                   |
| **Ecommerce**       | Multi-vendor storefront with cart, checkout, customer/supplier portals                |
| **RBAC**            | 4 roles, 72 permissions across 20 groups                                              |
| **AI**              | Product descriptions, inventory insights, sales forecasting, pricing, support chatbot |
| **Reporting**       | Sales trends, income vs expenses, profit analysis, annual performance charts          |

## Project Structure

```
erp-system/
├── laravel/          # Laravel 13 API + admin panel + storefront
├── react-app/        # React 19 POS frontend
├── production/       # Deployment configs, build scripts, Apache configs
├── nginx/            # Nginx reverse proxy config
├── php/              # PHP-FPM Dockerfile
├── docker-compose.yml
└── setup.sh
```

See [laravel/README.md](laravel/README.md) and [react-app/README.md](react-app/README.md) for detailed documentation.

## Quick Start

```bash
# Clone and setup
git clone <repo-url>
cd erp-system
./setup.sh
```

Or manually:

```bash
docker compose up -d --build
docker compose exec erp_laravel php artisan key:generate
docker compose exec erp_laravel php artisan migrate
docker compose exec erp_laravel php artisan db:seed
```

### Default Credentials

```
Email:    demp@obydullah.com
Password: 11111111
```

### Services

| Service         | Port  | URL                   |
| --------------- | ----- | --------------------- |
| Laravel (Nginx) | 8082  | http://localhost:8082 |
| React POS       | 3060  | http://localhost:3060 |
| phpMyAdmin      | 8083  | http://localhost:8083 |
| MariaDB         | 3307  | localhost:3307        |
| Ollama AI       | 11434 | localhost:11434       |

## Production Deployment

```bash
cd production
./deploy.sh
```

Generates `laravel-production.tar.gz` and `react-production.tar.gz` for Apache-based hosting.

## Database

55+ tables covering auth, catalog, inventory, sales, finance, procurement, manufacturing, HRM, and system logging.

See [laravel/README.md](laravel/README.md#database-schema) for full schema.

## License

Private use.
