# Technical Specifications - Exception Analytics

## 1. Data model and indexed fields

Source: `telemetry_records` where `type = exception`, enriched via `extraction_exception` read model.

Extracted columns in `extraction_exception`:
- `record_id` → FK to `telemetry_records.id`
- `organization_id`, `project_id`, `environment_id`
- `exception_class` → fully qualified class name (e.g. `App\Exceptions\PaymentException`)
- `exception_message_hash` → SHA-256 of normalized message (strips variable parts like IDs, paths)
- `occurrence_group_key` → composite fingerprint: `sha256(class + file + line)`, stable across deployments
- `handled` → boolean flag from payload
- `user_id` → nullable, from payload `user` field
- `server_id` → server identifier string from payload
- `ts_utc` → timestamp of occurrence

The `grouping_key` column in `telemetry_records` stores `occurrence_group_key` for fast grouped queries.

## 2. Aggregation strategy

List endpoint returns rows **grouped by `occurrence_group_key`** (not raw occurrences):
- `last_seen` = MAX(ts_utc) for the group
- `count` = COUNT(*) of occurrences in active period/filters
- `users` = COUNT(DISTINCT user_id) of impacted users
- `handled` = any occurrence handled? (from aggregated handled flag)
- Exception class + message preview from most recent occurrence

Header metrics (summary cards):
- `total` = SUM of all occurrence counts across all groups
- `handled_total` = occurrences where `handled = true`
- `unhandled_total` = occurrences where `handled = false`
- `unique_exceptions` = COUNT of distinct `occurrence_group_key` values

Chart series: occurrence counts bucketed by time, split by `handled` flag.

## 3. Query and filtering

Filter parameters:
- `period` / `from` + `to`: time window (mandatory)
- `user`: filter by specific `user_id` (optional)
- `status`: `all` | `handled` | `unhandled` (maps to `handled` boolean filter)
- `search`: text search across `exception_class` + `exception_message_hash` display value

Search implementation: `ILIKE` on `exception_class` + first 200 chars of message preview stored in extraction table. Full-text index (`GIN tsvector`) on these columns for larger datasets.

Default sort: `last_seen DESC`. Sortable columns: `last_seen`, `count`, `users`.

## 4. API contracts

```
GET /analytics/exceptions
```

Query params:
| Param | Type | Description |
|-------|------|-------------|
| `period` | string | Preset: 1h, 24h, 7d, 14d, 30d |
| `from` | ISO8601 | Custom range start (when period=custom) |
| `to` | ISO8601 | Custom range end (when period=custom) |
| `user` | string | Filter by user_id |
| `status` | string | all \| handled \| unhandled |
| `search` | string | Text search on class + message |
| `page` | int | Page number (default: 1) |
| `sort` | string | last_seen \| count \| users |
| `direction` | string | asc \| desc |

Response shape: `{ summary, series, rows, pagination, filters_applied }`.

`rows` entry shape:
```json
{
  "group_key": "abc123",
  "last_seen": "2026-03-14T12:00:00Z",
  "exception_class": "App\\Exceptions\\PaymentException",
  "message_preview": "Payment declined for order #...",
  "handled": false,
  "count": 42,
  "users": 8
}
```

## 5. Index strategy

```sql
-- Primary list index
CREATE INDEX idx_ext_exception_scope_ts
  ON extraction_exception (organization_id, project_id, environment_id, ts_utc DESC);

-- Grouping index for aggregated list
CREATE INDEX idx_ext_exception_group
  ON extraction_exception (organization_id, project_id, environment_id, occurrence_group_key, ts_utc DESC);

-- Handled filter index
CREATE INDEX idx_ext_exception_handled
  ON extraction_exception (project_id, environment_id, handled, ts_utc DESC);

-- User filter index
CREATE INDEX idx_ext_exception_user
  ON extraction_exception (project_id, environment_id, user_id, ts_utc DESC);
```

## 6. Security and tenant isolation

- All queries include `organization_id` + `project_id` + `environment_id` scope guard.
- `user_id` filter validated against requesting user's organization context — no cross-org user leakage.
- Forbidden access returns `403` with zero-result body.
- Exception message content (which may contain PII from user input) is stored in `raw_payload` and only surfaced in message preview (truncated, no raw payload exposure in list).

## 7. Test strategy

Key feature tests:
- Exception list groups by `occurrence_group_key` — multiple occurrences of same exception appear as one row with correct `count`.
- Handled/unhandled filter returns only matching rows; counters in summary update accordingly.
- User filter narrows both list rows and `users` counter per group.
- Search by class name returns matching groups; search by partial message returns matching groups.
- Chart series has correct bucket count for each period preset.
- Empty state returned when no exceptions match active filters.
- Cross-organization access denied: exception data from org A not accessible from org B.
- `occurrence_group_key` stability: same exception class + file + line always produces same key.

## Related Resources

- **Functional Spec**: [exception.md](./exception.md)
- **Detail Page**: [exception-detail.md](./exception-detail.md) + [exception-detail-technical.md](./exception-detail-technical.md)
- **Parent Spec**: [analytics/specs.md](../specs.md)
- **Related**: [issues/specs.md](../../issues/specs.md) — exception-to-issue integration
