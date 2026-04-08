# Development Guide

## Requirements

- PHP 8.5+
- Composer
- Node.js 24+ & npm
- MySQL 8+, Redis, ClickHouse 24

## Quick start

```bash
# 1. Clone
git clone https://github.com/Nyamort/OpenWatch.git
cd OpenWatch

# 2. Install dependencies
composer install
npm install

# 3. Configure environment
cp .env.example .env
php artisan key:generate

# 4. Run migrations
php artisan migrate

# 5. Start dev server (PHP + queue worker + Vite HMR)
composer run dev
```

Or with Laravel Sail:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run dev
```

## Demo credentials

Seed demo data first: `php artisan db:seed`

| Email | Password | Role |
|-------|----------|------|
| admin@example.com | password | Owner |
| dev@example.com | password | Developer |
| viewer@example.com | password | Viewer |

## Useful commands

```bash
php artisan test --compact          # run test suite
vendor/bin/pint                     # fix code style
php artisan wayfinder:generate      # regenerate TypeScript route bindings
npm run build                       # build frontend assets
```
