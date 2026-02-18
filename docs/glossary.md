# Technical Glossary

This glossary defines all shared technical terms used across the documentation.

---

## Tenant Hierarchy

### Organization
The primary tenant unit. All data, projects, members, and alert rules belong to exactly one organization. Cross-organization isolation is total and guaranteed by construction (foreign keys + global Eloquent scopes).

### Project
A monitored asset attached to an organization. A project groups multiple environments and exposes a computed `health_status` (healthy / degraded / unhealthy / unknown).

### Environment
A deployment instance of a project (e.g. `production`, `staging`, `development`). Each environment has its own ingestion tokens and isolated telemetry data.

---

## Ingestion & Agents

### Agent
An external process (daemon, CLI) that collects telemetry data from a Laravel application and sends it to the ingestion endpoint.

### NIGHTWATCH_TOKEN
A long-lived token bound to an environment, generated from the project interface. Used by agents to authenticate via `POST /api/agent-auth`. Stored as `sha256(token)` — shown once in plaintext at creation.

### AgentSessionToken
A short-lived token (TTL ~1h) returned by `POST /api/agent-auth`. Used by agents for ingestion requests (`POST {ingest_url}`). Includes `expires_in` and `refresh_in` for automatic renewal.

### Ingest URL
The endpoint URL returned by `agent-auth`, to which agents send gzip-compressed record batches.

### Grace Window
The duration during which an old token remains valid after rotation. If `grace_window = 0s`, the old token is immediately revoked. If `> 0s`, it is marked `deprecated` and remains accepted until expiry. Prevents ingestion interruptions during token rotations.

---

## Telemetry Data

### telemetry_records
The central append-only table (partitioned by month) that stores all ingested events.
Key columns: `organization_id`, `project_id`, `environment_id`, `type`, `ts_utc`, `payload_version`, `grouping_key`, `trace_id`, `execution_id`, `raw_payload` (jsonb).

### Record Types

| Type | Description |
|------|-------------|
| `request` | Incoming HTTP request |
| `query` | SQL query executed |
| `cache-event` | Cache operation (hit / miss / write / delete) |
| `command` | Artisan command executed |
| `log` | Application log entry |
| `notification` | Laravel notification sent |
| `mail` | Email sent via Laravel Mailer |
| `queued-job` | Job pushed to a queue |
| `job-attempt` | Job execution attempt |
| `scheduled-task` | Scheduled task run |
| `outgoing-request` | Outgoing HTTP request |
| `exception` | Exception thrown |
| `user` | User snapshot (id, name, username) |

### grouping_key (`_group`)
A hash/fingerprint that groups records belonging to the same logical group (e.g. same exception signature, same route, same job). Used for deduplication and correlation.

### trace_id
A UUID that spans all records from the same execution context (e.g. an HTTP request and all its associated queries, logs, exceptions). Enables cross-type correlation.

### execution_id
Identifier of the parent execution (request, command, job) that produced a child record. Complements `trace_id` for hierarchical correlations.

### Exception Signature / Fingerprint
A normalized hash computed from exception class + file + line (excluding variable values). Two exceptions of the same type at the same location share the same signature and are grouped in the analytics list.

---

## Analytics

### Bucketing Service
A centralized service that computes time bucket sizes based on the selected period:
- `1h` → 30-second buckets
- `24h` → 15-minute buckets
- `7d` → 2-hour buckets
- `14d` → 4-hour buckets
- `30d` → 6-hour buckets
- `custom` → auto-derived to keep ≤ 300 buckets

### p95 (95th percentile)
The value below which 95% of measurements fall. Indicates "near worst-case" performance excluding extreme outliers. Used for request/query/job durations.

### avg (average)
Arithmetic mean of a metric over the selected period.

### CQRS-like Split
Read/write separation used in the architecture:
- **Writes (commands)**: auth, org, projects, issues, alerts → mutation paths via Actions/Services.
- **Reads (queries)**: analytics pages → optimized query services and dedicated read models.

### dashboard_snapshots
Pre-computed aggregates table for 7d/14d/30d periods, refreshed every 5 minutes. Reduces live query load for the Dashboard page.

---

## Issues & Alerts

### Issue
An entity representing a detected problem in an application (e.g. a recurring exception). An issue has a lifecycle (`open`, `resolved`, `ignored`), an optional assignee, a priority, and occurrences linked to telemetry records.

### Occurrence
An individual telemetry record linked to an issue via `source_fingerprint` or `trace_id`.

### Threshold Rule
A configurable alert rule that evaluates a metric (e.g. exception count > 100 over 1h) and triggers state transitions.

### Alert State
The state of a threshold rule: `ok` (condition not met) or `triggered` (condition met). Transitions trigger email notifications to configured recipients.

---

## Authentication & Security

### RBAC (Role-Based Access Control)
Access control based on organization-defined roles and permission mappings.

### Default Roles

| Role | Description |
|------|-------------|
| `Organization Owner` | Full control, ownership transfer |
| `Organization Admin` | Member management, projects, alerts |
| `Organization Developer` | Technical configuration, investigation |
| `Organization Viewer` | Read-only access |

### Step-up Confirmation
Password (or 2FA) verification required before sensitive actions (enable/disable 2FA, token rotation, ownership transfer).

### Bounded Context
A DDD (Domain-Driven Design) logical boundary that isolates a business domain with its own models, services, and rules. Enables future evolution toward microservices without full rewrites.

---

## Performance & Operations

### SLA Targets

| Endpoint | p95 Target |
|----------|-----------|
| Analytics list (recent data) | < 200ms |
| Analytics summary / charts | < 500ms |
| Dashboard page (warm cache) | < 50ms |
| Dashboard page (cold) | < 300ms |
| Ingestion endpoint | < 100ms (async processing) |
| Issue list | < 150ms |

### MVP (Minimum Viable Product)
Features required for the first production release. Specs explicitly distinguish MVP from post-MVP in each module.

### Post-MVP
Features deferred after the initial launch, documented for future planning.
