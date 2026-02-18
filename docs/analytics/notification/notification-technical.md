# Technical Specifications - Notification Analytics

## 1. Data model and indexed fields

Source: `telemetry_records` where `type = notification`, enriched via `extraction_notification` read model.

Extracted columns in `extraction_notification`:
- `record_id`, `organization_id`, `project_id`, `environment_id`, `ts_utc`
- `notification_class` → fully qualified Notification class (e.g. `App\Notifications\InvoicePaid`)
- `channel` → delivery channel: `mail`, `database`, `broadcast`, `slack`, `vonage`, etc.
- `notifiable_type` → the notifiable model type (e.g. `App\Models\User`)
- `duration_ms` → time to send in milliseconds
- `failed` → boolean flag (delivery failure)
- `user_id` → nullable, from payload `user` field
- `execution_source` → `request`, `command`, `job`, `schedule`

## 2. Aggregation strategy

The list endpoint returns **notification-class-level grouped rows**:

```sql
SELECT
  notification_class,
  channel,
  COUNT(*) AS total,
  COUNT(*) FILTER (WHERE failed = true) AS failed_count,
  AVG(duration_ms) AS avg_ms,
  PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY duration_ms) AS p95_ms,
  MIN(duration_ms) AS min_ms,
  MAX(duration_ms) AS max_ms,
  MAX(ts_utc) AS last_seen
FROM extraction_notification
WHERE organization_id = ? AND project_id = ? AND environment_id = ?
  AND ts_utc BETWEEN ? AND ?
GROUP BY notification_class, channel
ORDER BY last_seen DESC
```

When the same notification class is sent via multiple channels, each `(class, channel)` combination appears as a separate row — enabling per-channel performance comparison.

Header cards:
- Left: total notification sends + failed count per channel.
- Right: duration range + avg + p95.

## 3. Channel filtering

Channel filter allows narrowing to a specific delivery channel:
- `all` → no channel filter
- `mail` | `database` | `broadcast` | `slack` | `vonage` | `other` → exact channel match

Channel values are validated against an allowed set — no arbitrary string injection. "Other" covers all unlisted channels via NOT IN clause.

Chart series: volume per bucket (total sends) + avg/p95 duration per bucket. Duration chart excludes failed deliveries.

## 4. API contracts

```
GET /analytics/notifications
```

Query params: `period`, `from`, `to`, `channel`, `search`, `page`, `sort` (total | failed | avg | p95 | last_seen), `direction`.

`rows` entry shape:
```json
{
  "notification_class": "App\\Notifications\\InvoicePaid",
  "channel": "mail",
  "total": 320,
  "failed_count": 5,
  "avg_ms": 210,
  "p95_ms": 950,
  "min_ms": 45,
  "max_ms": 3200,
  "last_seen": "2026-03-14T12:00:00Z"
}
```

```
GET /analytics/notifications/{notification_class}
```

Returns notification-class detail: individual delivery records paginated. Supports `channel` filter and duration segment filter. Each row shows: `ts_utc`, `channel`, `notifiable_type`, `duration_ms`, `failed`.

## 5. Index strategy + security

```sql
-- Primary grouping index
CREATE INDEX idx_ext_notification_class_scope
  ON extraction_notification (organization_id, project_id, environment_id, notification_class, ts_utc DESC);

-- Channel filter index
CREATE INDEX idx_ext_notification_channel
  ON extraction_notification (project_id, environment_id, channel, ts_utc DESC);

-- Failed filter (partial index)
CREATE INDEX idx_ext_notification_failed
  ON extraction_notification (project_id, environment_id, ts_utc DESC)
  WHERE failed = true;
```

Security:
- All queries scoped by `organization_id + project_id + environment_id`.
- Notifiable type (model class name) displayed; notifiable ID not exposed in list — only in detail with appropriate permission.
- Cross-organization access denied.

## 6. Test strategy

Key feature tests:
- Notification list groups by `(notification_class, channel)` — same class sent via mail and slack appears as two separate rows.
- Channel filter returns only matching channel rows.
- Failed-only filter correctly narrows results.
- Sort by `avg_ms` returns rows ordered by average duration.
- Detail endpoint returns individual delivery records (not grouped), paginated.
- Cross-organization access denied.
- Duration segment filter in detail partitions correctly.

## Related Resources

- **Functional Spec**: [notification.md](./notification.md)
- **Detail Page**: [notification-detail-technical.md](./notification-detail-technical.md)
- **Parent Spec**: [analytics/specs.md](../specs.md)
