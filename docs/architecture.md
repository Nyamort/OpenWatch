# Architecture

## Multi-tenant hierarchy

```
Organization → Project → Environment → Telemetry records
```

Every telemetry record is scoped to an `(organization_id, project_id, environment_id)` tuple. Cross-org data isolation is enforced at the middleware and Eloquent scope levels.

## Ingestion flow

```
Agent SDK  →  POST /api/agent-auth  →  session token (Redis, 1 h TTL)
           →  POST /api/ingest      →  ProcessTelemetryBatch (queued)
                                    →  telemetry_records + extraction_{type} tables
```

The ingestion token is stored as a SHA-256 hash. The raw token is returned exactly once at creation.

## Telemetry types

`request` · `query` · `cache-event` · `command` · `log` · `notification` · `mail` · `queued-job` · `job-attempt` · `scheduled-task` · `outgoing-request` · `exception` · `user`

Each type fans out into a typed extraction table for efficient analytical queries.

## Scheduled jobs

| Job | Schedule | Purpose |
|-----|----------|---------|
| `EvaluateAlertRules` | Every minute | Check thresholds, send trigger/recovery emails |
| `RefreshProjectHealth` | Every 5 minutes | Update environment health status |
| `PurgeExpiredTelemetryRecords` | Daily | Hard-delete records past the retention window |
| `AnonymizeStaleAuditEvents` | Daily | Anonymize PII in old audit entries |
