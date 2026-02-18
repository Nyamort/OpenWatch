# Technical Specifications - Jobs Analytics

## 1. Data model

Jobs analytics merges two record types from `telemetry_records`:
- `type = queued-job`: a job pushed to queue (captures: job_id, name, connection, queue, duration).
- `type = job-attempt`: an individual attempt to execute a job (captures: job_id, attempt_id, attempt number, status, duration, and rich context like queries/exceptions).

Extracted columns in `extraction_job`:
- `record_id`, `organization_id`, `project_id`, `environment_id`, `ts_utc`
- `record_type` → `queued-job` | `job-attempt`
- `job_id` → unique job identifier (shared between queued-job and its attempts)
- `job_name` → job class name (e.g. `App\Jobs\SendWelcomeEmail`)
- `connection` → queue connection (database, redis, sqs, etc.)
- `queue` → queue name
- `status` → `processed` | `failed` | `released` (job-attempt only)
- `duration_ms` → execution duration
- `attempt` → attempt number (job-attempt only, nullable for queued-job)
- `attempt_id` → unique attempt identifier (job-attempt only)

## 2. Aggregation strategy

The list endpoint returns **job-class-level grouped rows**:

```sql
SELECT
  job_name,
  COUNT(*) FILTER (WHERE record_type = 'queued-job') AS queued,
  COUNT(*) FILTER (WHERE status = 'processed') AS processed,
  COUNT(*) FILTER (WHERE status = 'released') AS released,
  COUNT(*) FILTER (WHERE status = 'failed') AS failed,
  COUNT(*) AS total,
  AVG(duration_ms) AS avg_ms,
  PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY duration_ms) AS p95_ms,
  MAX(ts_utc) AS last_seen
FROM extraction_job
WHERE organization_id = ? AND project_id = ? AND environment_id = ?
  AND ts_utc BETWEEN ? AND ?
  AND (record_type = ? OR ? IS NULL)
GROUP BY job_name
ORDER BY last_seen DESC
```

Header cards:
- Left card: total attempts + `failed/processed/released` counters.
- Right card: duration range (min–max) + `avg_ms` + `p95_ms`.

Chart series: status class counts per time bucket (left chart) + avg/p95 duration per bucket (right chart).

## 3. Query and filtering

Filter parameters:
- `record_type`: `all` | `queued-job` | `job-attempt`
- `connection`: filter by queue connection name
- `queue`: filter by queue name
- `search`: text match on `job_name`
- `period` / `from` + `to`: time window

Connection and queue filter values are validated against an allowed whitelist (derived from existing distinct values in scope) to prevent arbitrary query injection and ensure predictable query plans.

Default sort: `last_seen DESC`. Sortable columns: `queued`, `processed`, `released`, `failed`, `total`, `avg_ms`, `p95_ms`.

## 4. API contracts

```
GET /analytics/jobs
```

Query params: `period`, `from`, `to`, `record_type`, `connection`, `queue`, `search`, `page`, `sort`, `direction`.

Response: `{ summary, series: { status_series, duration_series }, rows, pagination, filters_applied }`.

`rows` entry shape:
```json
{
  "job_name": "App\\Jobs\\SendWelcomeEmail",
  "queued": 100,
  "processed": 95,
  "released": 3,
  "failed": 2,
  "total": 100,
  "avg_ms": 320,
  "p95_ms": 1200,
  "last_seen": "2026-03-14T12:00:00Z"
}
```

```
GET /analytics/jobs/{job_name}
```

Returns job-specific detail: full list of `job-attempt` records for that job class, with connection/queue/attempt filters, paginated.

## 5. Index strategy

```sql
-- Primary list + job grouping
CREATE INDEX idx_ext_job_name_scope
  ON extraction_job (organization_id, project_id, environment_id, job_name, ts_utc DESC);

-- Status filter
CREATE INDEX idx_ext_job_status
  ON extraction_job (project_id, environment_id, status, ts_utc DESC);

-- Attempt-specific: attempt_id lookups
CREATE INDEX idx_ext_job_attempt
  ON extraction_job (project_id, environment_id, job_id, attempt_id, attempt);

-- Connection/queue filter
CREATE INDEX idx_ext_job_connection
  ON extraction_job (project_id, environment_id, connection, queue, ts_utc DESC);
```

## 6. Security and tenant isolation

- All queries scoped by `organization_id + project_id + environment_id`.
- `connection` and `queue` filter values validated against actual values in scope — no arbitrary injection.
- Job content (exception previews, context) exposed only in detail view; list shows class names only.
- Cross-organization job data inaccessible by construction.

## 7. Test strategy

Key feature tests:
- Job list groups multiple records of same `job_name` into one row with correct `processed/released/failed` counts.
- `record_type = queued-job` filter returns only queued-job records; `job-attempt` returns only attempt records.
- `connection` filter narrows results to specified connection only.
- Search by partial job class name returns matching rows.
- Chart series has correct status split per bucket.
- Sort by `failed` column orders rows by failure count correctly.
- Job detail endpoint returns individual attempt records (not grouped), paginated.
- Cross-organization access denied.
- Empty state returned when no jobs match active filters.

## Related Resources

- **Functional Spec**: [jobs.md](./jobs.md)
- **Detail Pages**: [job-detail-technical.md](./job-detail-technical.md), [attempt-detail-technical.md](./attempt-detail-technical.md)
- **Parent Spec**: [analytics/specs.md](../specs.md)
