# Technical Specifications - Cache Event Analytics

## 1. Data model and indexed fields

Source: `telemetry_records` where `type = cache-event`, enriched via `extraction_cache_event` read model.

Extracted columns in `extraction_cache_event`:
- `record_id`, `organization_id`, `project_id`, `environment_id`, `ts_utc`
- `store` → cache store name (e.g. `redis`, `memcached`, `file`, `database`)
- `cache_key` → original cache key string
- `cache_key_hash` → sha256(cache_key) for grouping (keys can be very long)
- `cache_signature` → normalized key pattern (strips dynamic segments like IDs)
- `event_type` → `hit` | `miss` | `write` | `delete` | `forget`
- `failed` → boolean flag (operation failed)
- `duration_ms` → operation duration in milliseconds
- `ttl` → TTL in seconds (for write operations; null for reads)
- `execution_source` → `request`, `command`, `job`, `schedule`

## 2. Aggregates and metrics

List endpoint returns **cache-key-signature-level grouped rows**:

```sql
SELECT
  cache_signature,
  store,
  COUNT(*) FILTER (WHERE event_type = 'hit') AS hits,
  COUNT(*) FILTER (WHERE event_type = 'miss') AS misses,
  COUNT(*) FILTER (WHERE event_type = 'write') AS writes,
  COUNT(*) FILTER (WHERE event_type = 'delete') AS deletes,
  COUNT(*) FILTER (WHERE failed = true) AS failures,
  COUNT(*) AS total,
  ROUND(COUNT(*) FILTER (WHERE event_type = 'hit')::numeric /
    NULLIF(COUNT(*) FILTER (WHERE event_type IN ('hit','miss')), 0) * 100, 2) AS hit_rate_pct,
  MAX(ts_utc) AS last_seen
FROM extraction_cache_event
WHERE organization_id = ? AND project_id = ? AND environment_id = ?
  AND ts_utc BETWEEN ? AND ?
GROUP BY cache_signature, store
ORDER BY total DESC
```

Header cards:
- Left card: total calls + hit/miss/write/delete counts + hit percentage.
- Right card: failure count + failure-type breakdown.

Chart series: volume per event type per bucket (left) + failure rate per bucket (right).
Fixed color mapping returned in `config.colors`: `hit → green`, `miss → orange`, `write → blue`, `delete → gray`, `failure → red`.

## 3. API contracts

```
GET /analytics/cache-events
```

Query params:
| Param | Type | Description |
|-------|------|-------------|
| `period` | string | 1h \| 24h \| 7d \| 14d \| 30d |
| `from` / `to` | ISO8601 | Custom range |
| `store` | string | Filter by cache store name |
| `type_filter` | string | all \| hit \| miss \| write \| delete |
| `search` | string | Text filter on cache_signature |
| `page` | int | Page number |
| `sort` | string | total \| hits \| misses \| writes \| deletes \| failures \| hit_rate |
| `direction` | string | asc \| desc |

Response:
```json
{
  "summary": {
    "total": 48000,
    "hits": 32000,
    "misses": 8000,
    "writes": 6500,
    "deletes": 1000,
    "failures": 500,
    "hit_rate_pct": 80.0
  },
  "series": [{ "bucket_start_utc": "...", "hit": 120, "miss": 30, "write": 25, "failure": 2 }],
  "rows": [
    {
      "cache_signature": "user:*:profile",
      "store": "redis",
      "hits": 8000,
      "misses": 2000,
      "writes": 2100,
      "deletes": 100,
      "failures": 5,
      "total": 12200,
      "hit_rate_pct": 80.0,
      "last_seen": "2026-03-14T12:00:00Z"
    }
  ],
  "config": {
    "colors": { "hit": "#22c55e", "miss": "#f97316", "write": "#3b82f6", "delete": "#6b7280", "failure": "#ef4444" }
  },
  "pagination": { "current_page": 1, "total": 48 }
}
```

No drilldown to individual cache key in MVP — rows include only grouped signature data.

## 4. Index and query strategy

```sql
-- Primary grouping index
CREATE INDEX idx_ext_cache_signature_scope
  ON extraction_cache_event (organization_id, project_id, environment_id, cache_signature, ts_utc DESC);

-- Store filter index
CREATE INDEX idx_ext_cache_store
  ON extraction_cache_event (project_id, environment_id, store, ts_utc DESC);

-- Event type filter
CREATE INDEX idx_ext_cache_event_type
  ON extraction_cache_event (project_id, environment_id, event_type, ts_utc DESC);

-- Failure filter (partial index)
CREATE INDEX idx_ext_cache_failed
  ON extraction_cache_event (project_id, environment_id, ts_utc DESC)
  WHERE failed = true;
```

Precompute top keys in daily aggregates if volume exceeds 100k events/day per environment.

## 5. Security and isolation

- All queries scoped by `organization_id + project_id + environment_id`.
- Cache keys may contain sensitive identifiers — display `cache_signature` (normalized pattern) in list, not raw key. Raw key exposed in detail view only.
- Restricted cache keys (matching redaction patterns configured per organization) are hidden entirely from list and summary.
- Cross-organization access denied.

## 6. Frontend specifics

- Store selector is a dropdown populated from distinct `store` values in scope (dynamic, not hardcoded).
- Hit rate percentage shown as a gauge/badge per row — color-coded: > 80% green, 60–80% yellow, < 60% red.
- Color mapping for chart series is defined server-side (returned in `config.colors`) to ensure consistency across frontend components.
- No individual key drilldown in MVP — row action is a no-op or disabled.

## 7. Test strategy

Key feature tests:
- Hit rate calculation: `hits / (hits + misses) * 100`, handles zero-denominator case (no hits or misses).
- Store filter narrows results to specified store only.
- Event type filter (`type_filter = miss`) returns only miss events in grouping.
- Sort by `hit_rate` orders rows from lowest to highest (identifying problematic keys).
- Chart series has correct event type split per bucket.
- Restricted cache key signatures are excluded from response.
- Cross-organization access denied.

## Related Resources

- **Functional Spec**: [cache-event.md](./cache-event.md)
- **Parent Spec**: [analytics/specs.md](../specs.md)
