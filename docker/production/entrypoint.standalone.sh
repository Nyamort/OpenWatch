#!/usr/bin/env bash
set -e

# ── Persisted secrets ────────────────────────────────────────
# Generated once on first run and stored in the storage volume.
# Can be overridden by passing -e VAR=value to docker run.
SECRETS_FILE="/var/www/html/storage/app/.secrets"
mkdir -p /var/www/html/storage/app

if [ ! -f "$SECRETS_FILE" ]; then
    echo "==> Generating application secrets..."
    cat > "$SECRETS_FILE" <<SECRETS
APP_KEY=base64:$(openssl rand -base64 32)
DB_PASSWORD=$(openssl rand -hex 16)
SECRETS
    chmod 600 "$SECRETS_FILE"
fi

# Load secrets — only sets variables not already present in the environment
while IFS='=' read -r key value; do
    [[ "$key" =~ ^#.*$ ]] && continue
    [ -n "$key" ] && [ -z "${!key}" ] && export "$key=$value"
done < "$SECRETS_FILE"

# ── Defaults ─────────────────────────────────────────────────
export APP_ENV="${APP_ENV:-production}"
export APP_DEBUG="${APP_DEBUG:-false}"
export APP_URL="${APP_URL:-http://localhost}"
export DB_DATABASE="${DB_DATABASE:-openwatch}"
export DB_USERNAME="${DB_USERNAME:-openwatch}"
export CLICKHOUSE_DATABASE="${CLICKHOUSE_DATABASE:-openwatch}"
export CLICKHOUSE_USERNAME="${CLICKHOUSE_USERNAME:-default}"
export CLICKHOUSE_PASSWORD="${CLICKHOUSE_PASSWORD:-}"
export QUEUE_CONNECTION="${QUEUE_CONNECTION:-database}"
export CACHE_STORE="${CACHE_STORE:-redis}"
export SESSION_DRIVER="${SESSION_DRIVER:-redis}"
export MAIL_MAILER="${MAIL_MAILER:-log}"

# ── Write .env ───────────────────────────────────────────────
cat > /var/www/html/.env <<ENV
APP_NAME=OpenWatch
APP_ENV=${APP_ENV}
APP_KEY=${APP_KEY}
APP_DEBUG=${APP_DEBUG}
APP_URL=${APP_URL}

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=${DB_DATABASE}
DB_USERNAME=${DB_USERNAME}
DB_PASSWORD=${DB_PASSWORD}

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

CLICKHOUSE_HOST=127.0.0.1
CLICKHOUSE_HTTP_PORT=8123
CLICKHOUSE_DATABASE=${CLICKHOUSE_DATABASE}
CLICKHOUSE_USERNAME=${CLICKHOUSE_USERNAME}
CLICKHOUSE_PASSWORD=${CLICKHOUSE_PASSWORD}

QUEUE_CONNECTION=${QUEUE_CONNECTION}
CACHE_STORE=${CACHE_STORE}
SESSION_DRIVER=${SESSION_DRIVER}

MAIL_MAILER=${MAIL_MAILER}
MAIL_HOST=${MAIL_HOST:-}
MAIL_PORT=${MAIL_PORT:-587}
MAIL_USERNAME=${MAIL_USERNAME:-}
MAIL_PASSWORD=${MAIL_PASSWORD:-}
MAIL_FROM_ADDRESS=${MAIL_FROM_ADDRESS:-hello@example.com}
ENV

# ── Data directories ─────────────────────────────────────────
mkdir -p /var/lib/openwatch/{mysql,clickhouse,redis}
chown -R mysql:mysql /var/lib/openwatch/mysql
chown -R clickhouse:clickhouse /var/lib/openwatch/clickhouse
chown -R redis:redis /var/lib/openwatch/redis

# ── MySQL ────────────────────────────────────────────────────
if [ ! -d "/var/lib/openwatch/mysql/mysql" ]; then
    echo "==> Initializing MySQL data directory..."
    mysqld --initialize-insecure --user=mysql --datadir=/var/lib/openwatch/mysql
fi

echo "==> Starting MySQL..."
service mysql start

echo "==> Waiting for MySQL..."
until mysqladmin ping -h 127.0.0.1 -u root --silent 2>/dev/null; do sleep 1; done

if [ ! -f /var/lib/openwatch/mysql/.openwatch_initialized ]; then
    echo "==> Creating MySQL database and user..."
    mysql -u root -e "
        CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\`;
        CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'%' IDENTIFIED BY '${DB_PASSWORD}';
        GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'%';
        FLUSH PRIVILEGES;
    "
    touch /var/lib/openwatch/mysql/.openwatch_initialized
fi

# ── Redis ────────────────────────────────────────────────────
echo "==> Starting Redis..."
service redis-server start

# ── ClickHouse ───────────────────────────────────────────────
echo "==> Starting ClickHouse..."
service clickhouse-server start

echo "==> Waiting for ClickHouse..."
until clickhouse-client --query="SELECT 1" 2>/dev/null; do sleep 2; done

if [ ! -f /var/lib/openwatch/clickhouse/.openwatch_initialized ]; then
    echo "==> Creating ClickHouse database..."
    clickhouse-client --query="CREATE DATABASE IF NOT EXISTS \`${CLICKHOUSE_DATABASE}\`"
    touch /var/lib/openwatch/clickhouse/.openwatch_initialized
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
php artisan clickhouse:migrate

# ── Start app services via supervisord ───────────────────────
echo "==> Starting application..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
