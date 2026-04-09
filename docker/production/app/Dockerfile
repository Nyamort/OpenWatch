# ============================================================
# Stage 1 — Composer dependencies (no dev)
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
RUN mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && composer dump-autoload --optimize --no-dev \
    && cp .env.example .env \
    && php artisan key:generate --ansi \
    && php artisan wayfinder:generate --with-form

# ============================================================
# Stage 2 — Node / Vite build
# ============================================================
FROM node:24-alpine AS node-build

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
COPY --from=composer-deps /app/vendor ./vendor
COPY --from=composer-deps /app/resources/js/actions ./resources/js/actions
COPY --from=composer-deps /app/resources/js/routes ./resources/js/routes
COPY --from=composer-deps /app/resources/js/wayfinder ./resources/js/wayfinder
RUN WAYFINDER_COMMAND=true npm run build

# ============================================================
# Stage 3 — Production image (PHP 8.5-FPM + Nginx)
# ============================================================
FROM ubuntu:24.04

LABEL maintainer="OpenWatch"

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC

WORKDIR /var/www/html

# PHP 8.5 via ondrej PPA + Nginx
RUN apt-get update && apt-get install -y gnupg curl ca-certificates \
    && curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0xb8dc7e53946656efbce4c1dd71daeaab4ad4cab6' \
        | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg > /dev/null \
    && echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu noble main" \
        > /etc/apt/sources.list.d/ppa_ondrej_php.list \
    && apt-get update \
    && apt-get install -y \
        nginx \
        supervisor \
        default-mysql-client \
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
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Copy application
COPY --from=composer-deps /app/vendor ./vendor
COPY --from=node-build /app/public/build ./public/build
COPY . .

# Copy configs
COPY docker/production/app/nginx.conf        /etc/nginx/sites-available/default
COPY docker/production/app/supervisord.conf  /etc/supervisor/conf.d/supervisord.conf
COPY docker/production/app/entrypoint.sh     /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh \
    && mkdir -p bootstrap/cache storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage bootstrap/cache public \
    && chmod -R 775 storage bootstrap/cache \
    # Remove FPM default pool's listen on TCP, use socket
    && sed -i 's|listen = .*|listen = /run/php/php8.5-fpm.sock|' /etc/php/8.5/fpm/pool.d/www.conf \
    && sed -i 's|;listen.owner = www-data|listen.owner = www-data|' /etc/php/8.5/fpm/pool.d/www.conf \
    && sed -i 's|;listen.group = www-data|listen.group = www-data|' /etc/php/8.5/fpm/pool.d/www.conf \
    && mkdir -p /run/php

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
