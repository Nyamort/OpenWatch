# Technical Specifications - Mail Analytics

## 1. Data model and indexed fields

Source: `telemetry_records` where `type = mail`, enriched via `extraction_mail` read model.

Extracted columns in `extraction_mail`:
- `record_id`, `organization_id`, `project_id`, `environment_id`, `ts_utc`
- `mail_class` → fully qualified Mailable class (e.g. `App\Mail\WelcomeEmail`)
- `mailer` → mailer driver used (e.g. `smtp`, `ses`, `mailgun`)
- `subject_preview` → first 100 chars of email subject
- `recipient_count` → number of `to` recipients
- `duration_ms` → time to send in milliseconds
- `failed` → boolean flag (send failure)
- `user_id` → nullable, from payload `user` field
- `execution_source` → `request`, `command`, `job`, `schedule`

## 2. Aggregation strategy

The list endpoint returns **mail-class-level grouped rows**:

```sql
SELECT
  mail_class,
  mailer,
  COUNT(*) AS total,
  COUNT(*) FILTER (WHERE failed = true) AS failed_count,
  AVG(duration_ms) AS avg_ms,
  PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY duration_ms) AS p95_ms,
  MIN(duration_ms) AS min_ms,
  MAX(duration_ms) AS max_ms,
  MAX(ts_utc) AS last_seen
FROM extraction_mail
WHERE organization_id = ? AND project_id = ? AND environment_id = ?
  AND ts_utc BETWEEN ? AND ?
GROUP BY mail_class, mailer
ORDER BY last_seen DESC
```

Header cards:
- Left: total mail sends + failed count.
- Right: duration range (`min–max`) + `avg_ms` + `p95_ms`.

Chart series: total sends per bucket (volume chart) + avg/p95 duration per bucket.

## 3. Chart bucketing

Left chart: total mail sends per time bucket (including failed/success split when applicable).
Right chart: avg and p95 duration per bucket (failed sends excluded from duration metrics — they may have anomalous timing due to timeout).

Bucketing follows the shared `AnalyticsBucketService`: `1h→30s`, `24h→15m`, `7d→2h`, etc.

## 4. API contracts

```
GET /analytics/mail
```

Query params: `period`, `from`, `to`, `mailer`, `search`, `page`, `sort` (total | failed | avg | p95 | last_seen), `direction`.

`rows` entry shape:
```json
{
  "mail_class": "App\\Mail\\WelcomeEmail",
  "mailer": "ses",
  "total": 450,
  "failed_count": 12,
  "avg_ms": 340,
  "p95_ms": 1800,
  "min_ms": 80,
  "max_ms": 5200,
  "last_seen": "2026-03-14T12:00:00Z"
}
```

```
GET /analytics/mail/{mail_class}
```

Returns mail-class detail: individual send records for this class, paginated. Supports duration segment filter (fast | slow) and failed-only filter. Each row links to execution source (request/job/command) via `trace_id`.

## 5. Index strategy + security

```sql
-- Primary grouping index
CREATE INDEX idx_ext_mail_class_scope
  ON extraction_mail (organization_id, project_id, environment_id, mail_class, ts_utc DESC);

-- Mailer filter
CREATE INDEX idx_ext_mail_mailer
  ON extraction_mail (project_id, environment_id, mailer, ts_utc DESC);

-- Failed filter (partial index for performance)
CREATE INDEX idx_ext_mail_failed
  ON extraction_mail (project_id, environment_id, ts_utc DESC)
  WHERE failed = true;
```

Security:
- All queries scoped by `organization_id + project_id + environment_id`.
- Subject preview and recipient count shown; full recipient list (emails) only in raw payload — not exposed in list/detail views without explicit permission.
- Email content never stored or displayed.

## 6. Test strategy

Key feature tests:
- Mail list groups multiple sends of same `mail_class` into one row with correct total/failed counts.
- Mailer filter narrows results to specified mailer driver only.
- Sort by `failed_count` returns rows ordered by failure count correctly.
- Failed sends are excluded from duration metrics (avg/p95).
- Mail detail returns individual send records (not grouped), paginated.
- Duration segment filter (fast/slow) correctly partitions rows.
- Cross-organization access denied.

## Related Resources

- **Functional Spec**: [mail.md](./mail.md)
- **Detail Page**: [mail-detail-technical.md](./mail-detail-technical.md)
- **Parent Spec**: [analytics/specs.md](../specs.md)
