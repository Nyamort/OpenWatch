<p align="center">
  <img src="public/favicon.svg" alt="OpenWatch Logo" width="120" />
</p>

# OpenWatch

> Open-source application monitoring and telemetry platform — a self-hosted alternative to Laravel Nightwatch.

OpenWatch collects real-time telemetry from your PHP applications and provides analytics, issue tracking, and threshold-based alerting, all in a single self-hosted dashboard.

> **Status:** Active development — not yet production-ready.

[![GitHub](https://img.shields.io/badge/GitHub-Nyamort%2FOpenWatch-181717?logo=github)](https://github.com/Nyamort/OpenWatch)
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

---

## Deployment

Docker images are published to the GitHub Container Registry:

```
ghcr.io/nyamort/openwatch:latest           # standard image (PHP-FPM + Nginx)
ghcr.io/nyamort/openwatch:standalone       # all-in-one image
```

### Option 1 — Docker Compose (recommended)

Separate containers for the app, queue worker, scheduler, MySQL, Redis, and ClickHouse.

```bash
# 1. Download the compose file
curl -o docker-compose.prod.yml \
  https://raw.githubusercontent.com/Nyamort/OpenWatch/main/docker/production/docker-compose.prod.yml

# 2. Create your environment file
cp .env.example .env   # or create from scratch — see required variables below

# 3. Start
docker compose -f docker-compose.prod.yml up -d
```

On first boot, the `app` container runs database migrations automatically. The worker and scheduler start once the app is healthy.

**Required environment variables:**

| Variable | Description |
|----------|-------------|
| `APP_KEY` | Laravel app key (`php artisan key:generate --show`) |
| `APP_URL` | Public URL, e.g. `https://watch.example.com` |
| `DB_PASSWORD` | MySQL password |
| `DB_ROOT_PASSWORD` | MySQL root password |

### Option 2 — Standalone (single container)

Everything bundled in one container — MySQL, Redis, ClickHouse, PHP-FPM, Nginx, queue worker, and scheduler.

```bash
docker run -d \
  --name openwatch \
  -p 80:80 \
  -e APP_KEY="base64:your-key-here" \
  -e APP_URL="http://your-server-ip" \
  -e DB_PASSWORD="secret" \
  -v openwatch-mysql:/var/lib/mysql \
  -v openwatch-clickhouse:/var/lib/clickhouse \
  -v openwatch-storage:/var/www/html/storage \
  ghcr.io/nyamort/openwatch:standalone
```

Databases are initialized automatically on first run. All data is persisted in the named volumes.

---

---

## Contributing to OpenWatch

See [docs/development.md](docs/development.md) for setup instructions, local development workflow, and useful commands.
For a deeper look at the system design, see [docs/architecture.md](docs/architecture.md).

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.

## Security

If you discover a security vulnerability, please follow the process described in [SECURITY.md](SECURITY.md). Do **not** open a public issue.

## License

OpenWatch is open-source software licensed under the [MIT license](LICENSE).
