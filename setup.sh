#!/bin/bash
set -e

# Create Laravel project using Composer Docker image (no global install)
mkdir -p laravel
docker run --rm \
    -v "$(pwd)/laravel:/app" \
    composer:latest create-project laravel/laravel:^13.0 .

# Start containers
docker compose up -d --build

# Set permissions
docker compose exec laravel chmod -R 777 storage bootstrap/cache

echo "Laravel 13 is running at http://localhost"
