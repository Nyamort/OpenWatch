# Technical Specifications - Request Analytics

## 1. Data model and extracted fields

Source: `telemetry_records` where `type = request`, enriched via `extraction_request` read model.

Extracted columns in `extraction_request`:
- `record_id` → FK to `telemetry_records.id`
- `organization_id`, `project_id`, `environment_id`, `ts_utc`
- `route_path` → normalized route path (e.g. `/api/users/{id}`, not `/api/users/42`)
- `route_domain` → optional domain for domain-bound routing
- `http_method` → primary method (GET, POST, etc.)
- `route_methods` → all HTTP methods observed for the route (e.g. `GET|HEAD`)
- `status_code` → integer response code
- `status_class` → derived: `success` (1/2/3xx), `client_error` (4xx), `server_error` (5xx)
- `duration_ms` → total request duration in milliseconds
- `user_id` → nullable, from payload `user` field

Route-level read model: grouped rows pre-computed per `(route_path, route_methods)` for fast list queries.

## 2. Route aggregation query

The list endpoint returns **route-level grouped rows** (not individual request records):

```sql
SELECT
  route_path,
  route_methods,
  COUNT(*) FILTER (WHERE status_class = 'success') AS success_count,
  COUNT(*) FILTER (WHERE status_class = 'client_error') AS client_error_count,
  COUNT(*) FILTER (WHERE status_class = 'server_error') AS server_error_count,
  COUNT(*) AS total,
  AVG(duration_ms) AS avg_ms,
  PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY duration_ms) AS p95_ms,
  MAX(ts_utc) AS last_seen
FROM extraction_request
WHERE organization_id = ? AND project_id = ? AND environment_id = ?
  AND ts_utc BETWEEN ? AND ?
  AND (user_id = ? OR ? IS NULL)
GROUP BY route_path, route_methods
ORDER BY last_seen DESC
LIMIT 25 OFFSET ?
```

Route path cell: includes `route_domain` prefix when route identity depends on domain-bound routing (e.g. `api.example.com/users`).

## 3. Bucketed chart queries

Left chart (request volume by status class):
- `COUNT(*) GROUP BY bucket, status_class`
- Fixed color mapping: `success` → gray, `client_error` → orange, `server_error` → red.
- Header: total count + per-class counters for active filters.

Right chart (response time — avg and p95):
- `AVG(duration_ms)` and `PERCENTILE_CONT(0.95)` per bucket.
- Two series: `avg` (gray line), `p95` (orange line).
- Header: duration range (`min–max`) + `avg` + `p95` for active filters.

Bucket sizes per period: `1h→30s`, `24h→15m`, `7d→2h`, `14d→4h`, `30d→6h`, `custom→auto`.

## 4. API contracts

```
GET /analytics/requests
```

Query params:
| Param | Type | Description |
|-------|------|-------------|
| `period` | string | 1h \| 24h \| 7d \| 14d \| 30d |
| `from` / `to` | ISO8601 | Custom range |
| `user` | string | Filter by user_id |
| `search` | string | Filter route rows by path text |
| `page` | int | Page number (default: 1) |
| `sort` | string | last_seen \| total \| success \| client_error \| server_error \| avg \| p95 |
| `direction` | string | asc \| desc |

Response includes: `summary` (counters + duration stats), `series` (two arrays: status + duration), `rows` (route-grouped), `pagination`, `filters_applied`.

```
GET /analytics/request-route
```

Query params: `route_path`, `route_domain` (optional), `period`, `from`, `to`, `user`, `status` (success | client_error | server_error), `duration_segment`.
Returns: individual request records (not grouped), paginated. Used for route drilldown page.

## 5. Index strategy

```sql
-- Primary list + route grouping
CREATE INDEX idx_ext_request_route_scope
  ON extraction_request (organization_id, project_id, environment_id, route_path, ts_utc DESC);

-- Status class filter
CREATE INDEX idx_ext_request_status
  ON extraction_request (project_id, environment_id, status_class, ts_utc DESC);

-- User filter
CREATE INDEX idx_ext_request_user
  ON extraction_request (project_id, environment_id, user_id, ts_utc DESC);

-- Covering index for route list sort by total
CREATE INDEX idx_ext_request_route_covering
  ON extraction_request (project_id, environment_id, ts_utc DESC)
  INCLUDE (route_path, route_methods, status_class, duration_ms);
```

## 6. Security and tenant isolation

- All queries scoped by `organization_id + project_id + environment_id`.
- `user` filter validated: user_id comes from payload only, not session — no privilege escalation possible.
- Route path content never leaks cross-organization.
- 4xx/5xx visual emphasis in table is purely cosmetic — no additional permission check needed.

## 7. Test strategy

Key feature tests:
- Route list groups multiple requests to same route into one row with correct aggregated counts.
- Status class filter (`client_error`) returns only routes with non-zero `client_error_count`.
- User filter narrows both chart data and route list to requests from that user.
- Route search filters rows by partial path match (`/api/u` matches `/api/users/{id}`).
- Chart series has correct bucket count and status split for each period preset.
- Sort by `p95` column returns routes ordered by p95 duration correctly.
- Route drilldown (`GET /analytics/request-route`) returns individual records (not grouped).
- Cross-organization access denied.
- Empty state returned when no requests match active filters.

## Related Resources

- **Functional Spec**: [request.md](./request.md)
- **Detail Pages**: [request-detail-technical.md](./request-detail-technical.md), [request-route-technical.md](./request-route-technical.md)
- **Parent Spec**: [analytics/specs.md](../specs.md)
