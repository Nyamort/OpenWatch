# ============================================================
# Stage 1 — Composer dependencies
# ============================================================
FROM composer:2 AS composer-deps

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev

# ============================================================
# Stage 2 — Node / Vite build
# ============================================================
FROM node:24-alpine AS node-build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
COPY --from=composer-deps /app/vendor ./vendor
RUN npm run build

# ============================================================
# Stage 3 — All-in-one image
#            PHP 8.5-FPM + Nginx + MySQL 8 + Redis + ClickHouse
# ============================================================
FROM ubuntu:24.04

LABEL maintainer="OpenWatch"

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

WORKDIR /var/www/html

# ── PHP 8.5 PPA ──────────────────────────────────────────────
RUN apt-get update && apt-get install -y gnupg curl ca-certificates \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6' \
        | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" \
        > /etc/apt/sources.list.d/ppa_ondrej_php.list

# ── ClickHouse repo ──────────────────────────────────────────
RUN curl -fsSL 'https://packages.clickhouse.com/rpm/lts/repodata/repomd.xml.key' \
        | gpg --dearmor -o /usr/share/keyrings/clickhouse-keyring.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/clickhouse-keyring.gpg] https://packages.clickhouse.com/deb stable main" \
        > /etc/apt/sources.list.d/clickhouse.list

# ── Install everything ────────────────────────────────────────
RUN apt-get update && apt-get install -y \
        nginx \
        supervisor \
        # PHP
        php8.5-fpm \
        php8.5-mysql \
        php8.5-redis \
        php8.5-mbstring \
        php8.5-xml \
        php8.5-zip \
        php8.5-bcmath \
        php8.5-curl \
        php8.5-gd \
        php8.5-intl \
        php8.5-pcntl \
        # MySQL
        mysql-server-8.0 \
        # Redis
        redis-server \
        # ClickHouse
        clickhouse-server \
        clickhouse-client \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# ── Copy application ─────────────────────────────────────────
COPY --from=composer-deps /app/vendor ./vendor
COPY --from=node-build /app/public/build ./public/build
COPY . .

# ── Copy configs ─────────────────────────────────────────────
COPY docker/production/app/nginx.conf                    /etc/nginx/sites-available/default
COPY docker/production/standalone/supervisord.conf       /etc/supervisor/conf.d/supervisord.conf
COPY docker/production/standalone/entrypoint.sh          /usr/local/bin/entrypoint.sh
COPY docker/production/standalone/clickhouse-paths.xml   /etc/clickhouse-server/config.d/paths.xml

RUN chmod +x /usr/local/bin/entrypoint.sh \
    # PHP-FPM socket
    && sed -i 's|listen = .*|listen = /run/php/php8.5-fpm.sock|' /etc/php/8.5/fpm/pool.d/www.conf \
    && sed -i 's|;listen.owner = www-data|listen.owner = www-data|' /etc/php/8.5/fpm/pool.d/www.conf \
    && sed -i 's|;listen.group = www-data|listen.group = www-data|' /etc/php/8.5/fpm/pool.d/www.conf \
    && mkdir -p /run/php \
    # MySQL — custom datadir + bind to localhost only
    && sed -i 's/^bind-address.*/bind-address = 127.0.0.1/' /etc/mysql/mysql.conf.d/mysqld.cnf \
    && echo "datadir = /var/lib/openwatch/mysql" >> /etc/mysql/mysql.conf.d/mysqld.cnf \
    # Redis — custom dir + bind to localhost only
    && sed -i 's/^bind .*/bind 127.0.0.1/' /etc/redis/redis.conf \
    && sed -i 's|^dir .*|dir /var/lib/openwatch/redis|' /etc/redis/redis.conf \
    # Storage permissions
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R 775 storage bootstrap/cache

# Single data volume
VOLUME ["/var/lib/openwatch", "/var/www/html/storage"]

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
