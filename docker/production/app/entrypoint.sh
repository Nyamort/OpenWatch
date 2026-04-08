#!/usr/bin/env bash
set -e

cd /var/www/html

echo "==> Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Linking storage..."
php artisan storage:link --force

echo "==> Running migrations..."
php artisan migrate --force
php artisan clickhouse:migrate

echo "==> Starting services..."
exec "$@"
