#!/usr/bin/env bash
set -e

DB_DATABASE="${DB_DATABASE:-openwatch}"
DB_USERNAME="${DB_USERNAME:-openwatch}"
DB_PASSWORD="${DB_PASSWORD:-secret}"
CLICKHOUSE_DATABASE="${CLICKHOUSE_DATABASE:-openwatch}"

# ── MySQL ────────────────────────────────────────────────────
if [ ! -d "/var/lib/mysql/mysql" ]; then
    echo "==> Initializing MySQL data directory..."
    mysqld --initialize-insecure --user=mysql --datadir=/var/lib/mysql
fi

echo "==> Starting MySQL..."
service mysql start

echo "==> Waiting for MySQL..."
until mysqladmin ping -h 127.0.0.1 -u root --silent 2>/dev/null; do sleep 1; done

# First run: create DB and user
if [ ! -f /var/lib/mysql/.openwatch_initialized ]; then
    echo "==> Creating MySQL database and user..."
    mysql -u root -e "
        CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\`;
        CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'%' IDENTIFIED BY '${DB_PASSWORD}';
        GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'%';
        FLUSH PRIVILEGES;
    "
    touch /var/lib/mysql/.openwatch_initialized
fi

# ── Redis ────────────────────────────────────────────────────
echo "==> Starting Redis..."
service redis-server start

# ── ClickHouse ───────────────────────────────────────────────
echo "==> Starting ClickHouse..."
service clickhouse-server start

echo "==> Waiting for ClickHouse..."
until clickhouse-client --query="SELECT 1" 2>/dev/null; do sleep 2; done

if [ ! -f /var/lib/clickhouse/.openwatch_initialized ]; then
    echo "==> Creating ClickHouse database..."
    clickhouse-client --query="CREATE DATABASE IF NOT EXISTS \`${CLICKHOUSE_DATABASE}\`"
    touch /var/lib/clickhouse/.openwatch_initialized
fi

# ── Laravel bootstrap ────────────────────────────────────────
cd /var/www/html

echo "==> Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Linking storage..."
php artisan storage:link --force

echo "==> Running migrations..."
php artisan migrate --force

# ── Start app services via supervisord ───────────────────────
echo "==> Starting application..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
