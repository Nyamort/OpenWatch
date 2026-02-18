# Technical Specifications - Log Analytics

## 1. Data model and indexed fields

Source: `telemetry_records` where `type = log`, enriched via `extraction_log` read model.

Extracted columns in `extraction_log`:
- `record_id`, `organization_id`, `project_id`, `environment_id`, `ts_utc`
- `log_level` → enum: `debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`
- `message_preview` → first 500 characters of log message (for search and list display)
- `channel` → Laravel log channel name (e.g. `stack`, `daily`, `slack`)
- `user_id` → nullable, from payload `user` field
- `trace_id` → for cross-type correlation
- `execution_source` → `request`, `command`, `job`, `schedule`, `console`

**No grouping**: log analytics displays raw events in reverse chronological order (a feed), not aggregated rows. This differs from exception/request analytics.

## 2. Level filter implementation

Log levels follow RFC 5424 severity order (emergency being most severe):
`emergency > alert > critical > error > warning > notice > info > debug`

Level filter modes:
- `All` → no level filter
- Specific level (e.g. `error`) → exact match only
- Level selector shows count per level for active period (precomputed in summary query).

Summary query computes per-level counts:
```sql
SELECT log_level, COUNT(*) as count
FROM extraction_log
WHERE organization_id = ? AND project_id = ? AND environment_id = ?
  AND ts_utc BETWEEN ? AND ?
GROUP BY log_level
```

## 3. Query and filtering

Parameters:
- `period` / `from` + `to`: time window (mandatory)
- `level`: specific log level filter (optional)
- `search`: text search on `message_preview` using `ILIKE` or full-text index
- `page` + cursor: cursor-based pagination for real-time feel (avoids count drift)

Default order: `ts_utc DESC`. Logs are never re-sorted by user — temporal order is the primary view.

Latest event marker: response includes `latest_event_at` timestamp to enable frontend polling for new entries without full reload.

Cursor-based pagination preferred over offset for log feed (avoids missing or duplicating entries as new logs arrive during browsing).

## 4. API contracts

```
GET /analytics/logs
```

Query params:
| Param | Type | Description |
|-------|------|-------------|
| `period` | string | 1h \| 24h \| 7d \| 14d \| 30d |
| `from` / `to` | ISO8601 | Custom range |
| `level` | string | Log level filter |
| `search` | string | Text search on message |
| `page` | int | Page number |
| `cursor` | string | Opaque cursor for cursor-based pagination |

Response:
```json
{
  "summary": {
    "total": 1240,
    "by_level": { "error": 42, "warning": 150, "info": 800, "debug": 248 }
  },
  "latest_event_at": "2026-03-14T12:00:00Z",
  "rows": [
    {
      "id": "uuid",
      "ts_utc": "2026-03-14T12:00:00Z",
      "log_level": "error",
      "channel": "stack",
      "message_preview": "Failed to connect to payment gateway...",
      "user_id": "user_123",
      "trace_id": "uuid"
    }
  ],
  "pagination": { "next_cursor": "...", "has_more": true }
}
```

Row action opens `GET /analytics/logs/{id}` for full log detail (raw payload, full context, execution source).

## 5. Index strategy

```sql
-- Primary feed index (most common query)
CREATE INDEX idx_ext_log_scope_ts
  ON extraction_log (organization_id, project_id, environment_id, ts_utc DESC);

-- Level filter index
CREATE INDEX idx_ext_log_level
  ON extraction_log (project_id, environment_id, log_level, ts_utc DESC);

-- Text search (GIN for full-text)
CREATE INDEX idx_ext_log_message_fts
  ON extraction_log USING GIN (to_tsvector('simple', message_preview));

-- Trace correlation
CREATE INDEX idx_ext_log_trace
  ON extraction_log (project_id, environment_id, trace_id, ts_utc DESC);
```

## 6. Security and tenant isolation

- All queries scoped by `organization_id + project_id + environment_id`.
- Log message content may contain PII — `message_preview` is truncated at 500 chars; full content only in detail view.
- Trace correlation (`trace_id` links to requests/exceptions) is scoped to same organization.
- Export (when enabled): logs actor, period, level filter, timestamp.

## 7. Test strategy

Key feature tests:
- Log feed returns events in `ts_utc DESC` order.
- Level filter returns only matching log level rows; summary `by_level` counts are accurate.
- Search returns only matching message preview rows.
- Cursor pagination: navigating pages does not duplicate or skip entries as new logs arrive.
- `latest_event_at` reflects the most recent entry in scope.
- Trace ID link from log to related request/exception works within same org.
- Cross-organization access denied.

## Related Resources

- **Functional Spec**: [log.md](./log.md)
- **Detail Page**: [log-detail-technical.md](./log-detail-technical.md)
- **Parent Spec**: [analytics/specs.md](../specs.md)
