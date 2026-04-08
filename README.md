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

### Docker Compose

Separate containers for the app, queue worker, scheduler, MySQL, Redis, and ClickHouse.

```bash
# 1. Download and run the setup script
curl -fsSL https://raw.githubusercontent.com/Nyamort/OpenWatch/main/docker/production/setup.sh -o setup.sh
bash setup.sh

# 2. Start
docker compose up -d
```

The setup script handles downloading `docker-compose.yml`, generating your `.env` from the template, and setting a secure `APP_KEY` and file permissions automatically.

On first boot, the `app` container caches config, runs MySQL and ClickHouse migrations, then starts. The worker and scheduler wait for the app to be healthy before starting.

The setup script asks two questions — your public URL and a MySQL password (or generates one for you). Everything else is handled automatically.

**Updating to a newer version:**

```bash
docker compose pull && docker compose up -d
```

**Viewing logs:**

```bash
docker compose logs -f app      # app + migrations
docker compose logs -f worker   # queue worker
```

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
