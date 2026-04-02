# OpenWatch

> Open-source application monitoring and telemetry platform — a self-hosted alternative to Laravel Nightwatch.

Open Watch collects real-time telemetry from your PHP applications and provides analytics, issue tracking, and threshold-based alerting, all in a single self-hosted dashboard.

> **Status:** Active development — not yet production-ready.

![PHP](https://img.shields.io/badge/PHP-8.5-blue?logo=php)
![Laravel](https://img.shields.io/badge/Laravel-12-red?logo=laravel)
![React](https://img.shields.io/badge/React-19-61DAFB?logo=react)
![License](https://img.shields.io/badge/license-MIT-green)

---

## Features

- **Multi-tenant** — Organizations, projects, and environments with role-based access (Owner, Admin, Developer, Viewer)
- **13 telemetry types** — requests, queries, cache events, commands, jobs, scheduled tasks, mail, notifications, outgoing requests, exceptions, logs, users
- **Analytics dashboards** — period-aware charts and sortable/searchable tables for every telemetry type
- **Alert rules** — configurable threshold alerts with email notifications for trigger and recovery
- **Audit log** — immutable, owner/admin-only record of all team and configuration changes
- **Two-factor authentication** — TOTP-based 2FA via Laravel Fortify
- **Ingestion API** — lightweight gzip-compressed batch endpoint; tokens stored as SHA-256 hashes
- **Data retention** — configurable per-environment TTL with automatic purge

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 12, PHP 8.5 |
| Frontend | React 19, TypeScript, Inertia.js v2, Tailwind CSS v4 |
| Database | MySQL 8 (SQLite for testing) |
| Cache / Queue | Redis, Laravel Horizon |
| Auth | Laravel Fortify (2FA, email verification) |
| Tests | Pest 4 |

## Requirements

- PHP 8.2+
- Composer
- Node.js 20+ & npm
- MySQL 8+ (or compatible)
- Redis

## Installation

```bash
# 1. Clone the repository
git clone https://github.com/your-org/openwatch.git
cd openwatch

# 2. Install PHP dependencies
composer install

# 3. Install JS dependencies
npm install

# 4. Configure the environment
cp .env.example .env
php artisan key:generate

# 5. Configure your database and Redis in .env, then run migrations
php artisan migrate

# 6. (Optional) Seed demo data
php artisan db:seed

# 7. Build frontend assets
npm run build
```

## Quick Start (Development)

```bash
composer run dev   # starts PHP dev server + queue worker + Vite HMR
```

**Demo credentials** (after seeding):

| Email | Password | Role |
|-------|----------|------|
| admin@example.com | password | Owner |
| dev@example.com | password | Developer |
| viewer@example.com | password | Viewer |

## Architecture

### Multi-tenant Hierarchy

```
Organization → Project → Environment → Telemetry records
```

Every telemetry record is scoped to an `(organization_id, project_id, environment_id)` tuple. Cross-org data isolation is enforced at the middleware and Eloquent scope levels.

### Ingestion Flow

```
Agent SDK  →  POST /api/agent-auth  →  session token (Redis, 1 h TTL)
           →  POST /api/ingest      →  ProcessTelemetryBatch (queued)
                                    →  telemetry_records + extraction_{type} tables
```

The ingestion token is stored as a SHA-256 hash. The raw token is returned exactly once at creation.

### Telemetry Types

`request` · `query` · `cache-event` · `command` · `log` · `notification` · `mail` · `queued-job` · `job-attempt` · `scheduled-task` · `outgoing-request` · `exception` · `user`

Each type fans out into a typed extraction table for efficient analytical queries.

## Key API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/health` | Liveness check (DB + cache) |
| `POST` | `/api/agent-auth` | Exchange ingestion token for a session token |
| `POST` | `/api/ingest` | Submit a gzip-compressed telemetry batch |
| `GET` | `/dashboard` | Period-aware dashboard with deferred props |
| `GET` | `/organizations/{org}/analytics/{domain}` | Analytics per telemetry type |
| `GET` | `/organizations/{org}/audit` | Immutable audit log (owner/admin only) |

## Scheduled Jobs

| Job | Schedule | Purpose |
|-----|----------|---------|
| `EvaluateAlertRules` | Every minute | Check thresholds, send trigger/recovery emails |
| `RefreshProjectHealth` | Every 5 minutes | Update environment health status |
| `PurgeExpiredTelemetryRecords` | Daily | Hard-delete records past the retention window |
| `AnonymizeStaleAuditEvents` | Daily | Anonymize PII in old audit entries |

## Development Commands

```bash
# Run the test suite
php artisan test --compact

# Fix code style
vendor/bin/pint

# Regenerate TypeScript route bindings (after adding/modifying routes)
php artisan wayfinder:generate

# Build frontend assets
npm run build
```

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.

## Security

If you discover a security vulnerability, please follow the process described in [SECURITY.md](SECURITY.md). Do **not** open a public issue.

## License

OpenWatch is open-source software licensed under the [MIT license](LICENSE).
