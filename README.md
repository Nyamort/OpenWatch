# Nightwatch

Laravel 12 application monitoring platform. Collects telemetry from your PHP apps (requests, exceptions, queries, jobs, cache, etc.) and provides analytics, issue tracking, and threshold alerts.

## Stack

- **Backend**: Laravel 12, PHP 8.5, MySQL 8, Redis
- **Frontend**: React 19, TypeScript, Inertia v2, Tailwind CSS
- **Auth**: Laravel Fortify (2FA, email verification, session management)
- **Queue**: Redis / Laravel Horizon
- **Tests**: Pest 4 (168 tests)

## Quick start

```bash
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed          # creates demo users, org, projects, telemetry data

# Frontend
npm install && npm run build

# Dev server
composer run dev             # starts PHP + queue worker + Vite
```

**Demo credentials** (after seeding):

| Email | Password | Role |
|-------|----------|------|
| admin@example.com | password | Owner |
| dev@example.com | password | Developer |
| viewer@example.com | password | Viewer |

## Architecture

### Multi-tenant hierarchy

```
Organization → Project → Environment → Telemetry records
```

Every telemetry record is scoped to an `(organization_id, project_id, environment_id)` tuple. Cross-org isolation is enforced at the middleware and Eloquent scope levels.

### Ingestion flow

```
Agent SDK  →  POST /api/agent-auth  →  session token (Redis, 1h TTL)
           →  POST /api/ingest      →  ProcessTelemetryBatch job
                                    →  telemetry_records + extraction_{type} tables
```

Ingestion tokens are stored as sha256 hashes. The raw token is returned exactly once at creation.

### Telemetry record types

`request` · `query` · `cache-event` · `command` · `log` · `notification` · `mail` · `queued-job` · `job-attempt` · `scheduled-task` · `outgoing-request` · `exception` · `user`

Each type fans out into a typed extraction table for efficient analytical queries.

## Key endpoints

| Method | Path | Description |
|--------|------|-------------|
| `GET` | `/api/health` | Liveness check (DB + cache) |
| `POST` | `/api/agent-auth` | Exchange ingestion token for session token |
| `POST` | `/api/ingest` | Submit gzip-compressed telemetry batch |
| `GET` | `/dashboard` | Period-aware dashboard with deferred props |
| `GET` | `/organizations/{org}/analytics/{domain}` | Analytics per record type |
| `GET` | `/organizations/{org}/audit` | Immutable audit log (owner/admin) |

## Scheduled jobs

| Job | Schedule | Purpose |
|-----|----------|---------|
| `EvaluateAlertRules` | Every minute | Check thresholds, send trigger/recovery emails |
| `RefreshProjectHealth` | Every 5 minutes | Update environment health_status |
| `PurgeExpiredTelemetryRecords` | Daily | Hard-delete records past retention window |
| `AnonymizeStaleAuditEvents` | Daily | Anonymize PII in old audit records |

## Development

```bash
php artisan test --compact          # run test suite
vendor/bin/pint                     # code style
php artisan wayfinder:generate      # regenerate TypeScript route bindings
```
