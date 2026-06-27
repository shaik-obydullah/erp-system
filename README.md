# ERP System

Laravel 13 + PHP 8.5 Docker setup.

## Services

| Service | Container | Port |
|---------|-----------|------|
| Laravel | `erp_laravel` | — |
| Nginx   | `erp_nginx` | 8082 |
| MariaDB | `erp_mariadb` | 3307 |
| phpMyAdmin | `erp_phpmyadmin` | 8083 |

## Setup

```bash
./setup.sh
```

Or manually:

```bash
mkdir -p laravel
docker run --rm -v "$(pwd)/laravel:/app" composer:latest create-project laravel/laravel:^13.0 .
docker compose up -d --build
docker compose exec erp_laravel php artisan key:generate
docker compose exec erp_laravel php artisan migrate
```

## URLs

- **Laravel**: http://localhost:8082
- **phpMyAdmin**: http://localhost:8083 (Server: `erp_db`, User: `laravel`, Pass: `secret`)

## Stop

```bash
docker compose down
```
