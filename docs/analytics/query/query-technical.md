# Technical Specifications - Query Analytics

## 1. Data model and indexed fields

Source: `telemetry_records` where `type = query`, enriched via `extraction_query` read model.

Extracted columns in `extraction_query`:
- `record_id`, `organization_id`, `project_id`, `environment_id`, `ts_utc`
- `query_signature_hash` → normalized SQL hash (stable grouping key)
- `sql_preview` → first 300 characters of normalized SQL for display
- `connection` → database connection name (e.g. `mysql`, `pgsql`, `sqlite`)
- `connection_type` → `read` | `write`
- `duration_us` → duration in microseconds (higher precision than ms)
- `user_id` → nullable, from payload `user` field
- `trace_id` → for cross-type correlation with parent request/command
- `execution_source` → `request`, `command`, `job`, `schedule`

## 2. Query signature normalization

SQL normalization produces a stable `query_signature_hash` by:
1. Stripping all literal values: integers, strings, floats, date literals → replaced with `?` placeholder.
2. Normalizing whitespace: collapse multiple spaces/newlines to single space.
3. Uppercasing SQL keywords for consistency.
4. Computing `sha256(normalized_sql + connection)` as the final hash.

Examples:
- `SELECT * FROM users WHERE id = 42` → `SELECT * FROM users WHERE id = ?` → hash
- `SELECT * FROM users WHERE id = 99` → same hash (same query pattern)

SQL preview stored as the normalized form (with `?` placeholders) — no raw values in list view.

## 3. Aggregation strategy

The list endpoint returns **query-signature-level grouped rows**:

```sql
SELECT
  query_signature_hash,
  sql_preview,
  connection,
  COUNT(*) AS calls,
  SUM(duration_us) AS total_duration_us,
  AVG(duration_us) AS avg_us,
  PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY duration_us) AS p95_us,
  MIN(duration_us) AS min_us,
  MAX(duration_us) AS max_us,
  MAX(ts_utc) AS last_seen
FROM extraction_query
WHERE organization_id = ? AND project_id = ? AND environment_id = ?
  AND ts_utc BETWEEN ? AND ?
GROUP BY query_signature_hash, sql_preview, connection
ORDER BY last_seen DESC
```

Adaptive duration unit: if `avg_us < 1000`, display in microseconds (`µs`); otherwise display in milliseconds (`ms`). Unit metadata returned in `config.duration_unit` for frontend consistency.

## 4. API contracts

```
GET /analytics/queries
```

Query params: `period`, `from`, `to`, `connection`, `search`, `page`, `sort` (calls | total | avg | p95 | last_seen), `direction`.

Response includes `config.duration_unit` (`us` | `ms`) for adaptive display.

`rows` entry shape:
```json
{
  "signature_hash": "abc123",
  "sql_preview": "SELECT * FROM users WHERE id = ?",
  "connection": "pgsql",
  "calls": 842,
  "avg_us": 1240,
  "p95_us": 8500,
  "min_us": 120,
  "max_us": 45000,
  "last_seen": "2026-03-14T12:00:00Z"
}
```

```
GET /analytics/queries/{signature_hash}
```

Returns query detail: all individual execution rows for this signature, with `trace_id` links to parent request/command. Supports duration segment filter (fast | medium | slow threshold configurable).

## 5. Index strategy

```sql
-- Primary grouping index
CREATE INDEX idx_ext_query_signature_scope
  ON extraction_query (organization_id, project_id, environment_id, query_signature_hash, ts_utc DESC);

-- Connection filter
CREATE INDEX idx_ext_query_connection
  ON extraction_query (project_id, environment_id, connection, ts_utc DESC);

-- Duration sort (for slow query detection)
CREATE INDEX idx_ext_query_duration
  ON extraction_query (project_id, environment_id, duration_us DESC, ts_utc DESC);

-- Trace correlation
CREATE INDEX idx_ext_query_trace
  ON extraction_query (project_id, environment_id, trace_id, ts_utc DESC);
```

## 6. Security and tenant isolation

- All queries scoped by `organization_id + project_id + environment_id`.
- SQL normalization ensures no raw user data (e.g. user emails, passwords) appears in query previews.
- `trace_id` correlation always stays within organization scope.
- Connection name validated against known connections — no arbitrary string injection.

## 7. Test strategy

Key feature tests:
- Two queries with same SQL pattern and different literal values produce the same `query_signature_hash`.
- Query list groups them correctly into one row with accurate `calls` count.
- Connection filter narrows results to specified connection only.
- Sort by `p95_us` returns rows ordered by p95 duration correctly.
- Adaptive unit: response includes `duration_unit: "ms"` when avg > 1000µs.
- Query detail returns individual execution rows (not grouped), paginated.
- Trace link from query to parent request resolves within same organization.
- Cross-organization access denied.

## Related Resources

- **Functional Spec**: [query.md](./query.md)
- **Detail Page**: [query-detail-technical.md](./query-detail-technical.md)
- **Parent Spec**: [analytics/specs.md](../specs.md)
