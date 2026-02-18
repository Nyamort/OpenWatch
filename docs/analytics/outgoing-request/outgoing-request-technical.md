# Technical Specifications - Outgoing Request Analytics

## 1. Data model and indexed fields

Source: `telemetry_records` where `type = outgoing-request`, enriched via `extraction_outgoing_request` read model.

Extracted columns in `extraction_outgoing_request`:
- `record_id`, `organization_id`, `project_id`, `environment_id`, `ts_utc`
- `host_domain` → extracted domain/host from URL (e.g. `api.stripe.com`, `graph.facebook.com`)
- `url_path` → URL path without query string (for route-level grouping within a host)
- `http_method` → HTTP method (GET, POST, PUT, DELETE, etc.)
- `status_code` → integer response code
- `status_class` → `success` (1/2/3xx), `client_error` (4xx), `server_error` (5xx)
- `duration_ms` → request duration in milliseconds
- `request_size` → outgoing request body size in bytes
- `response_size` → response body size in bytes
- `user_id` → nullable, from payload `user` field
- `trace_id` → for cross-type correlation with parent request/command

## 2. Host-level aggregation

The list endpoint returns **host-domain-level grouped rows**:

```sql
SELECT
  host_domain,
  COUNT(*) FILTER (WHERE status_class = 'success') AS success_count,
  COUNT(*) FILTER (WHERE status_class = 'client_error') AS client_error_count,
  COUNT(*) FILTER (WHERE status_class = 'server_error') AS server_error_count,
  COUNT(*) AS total,
  AVG(duration_ms) AS avg_ms,
  PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY duration_ms) AS p95_ms,
  MAX(ts_utc) AS last_seen
FROM extraction_outgoing_request
WHERE organization_id = ? AND project_id = ? AND environment_id = ?
  AND ts_utc BETWEEN ? AND ?
GROUP BY host_domain
ORDER BY last_seen DESC
```

Header cards:
- Left: total outgoing calls + status class counters.
- Right: duration range + avg + p95.

Chart series: status class counts per bucket (left) + avg/p95 duration per bucket (right).
Visual emphasis: `client_error` (4xx) styled in warning orange, `server_error` (5xx) in error red.

## 3. Query and filtering

Filter parameters:
- `period` / `from` + `to`: time window
- `search`: text filter on `host_domain` (ILIKE)
- `status`: `all` | `success` | `client_error` | `server_error`
- `user`: filter by user_id (optional)

Default sort: `last_seen DESC`. Sortable: `total`, `success_count`, `client_error_count`, `server_error_count`, `avg_ms`, `p95_ms`.

## 4. API contracts

```
GET /analytics/outgoing-requests
```

Query params: `period`, `from`, `to`, `search`, `status`, `user`, `page`, `sort`, `direction`.

`rows` entry shape:
```json
{
  "host_domain": "api.stripe.com",
  "success_count": 1200,
  "client_error_count": 15,
  "server_error_count": 3,
  "total": 1218,
  "avg_ms": 180,
  "p95_ms": 620,
  "last_seen": "2026-03-14T12:00:00Z"
}
```

```
GET /analytics/outgoing-requests/{host_domain}
```

Returns host detail: individual outgoing request records for this host, paginated. Shows `url_path`, `http_method`, `status_code`, `duration_ms`. Supports `status` and `method` filters. Each row links to parent execution via `trace_id`.

## 5. Index strategy

```sql
-- Primary host grouping index
CREATE INDEX idx_ext_outgoing_host_scope
  ON extraction_outgoing_request (organization_id, project_id, environment_id, host_domain, ts_utc DESC);

-- Status class filter
CREATE INDEX idx_ext_outgoing_status
  ON extraction_outgoing_request (project_id, environment_id, status_class, ts_utc DESC);

-- Trace correlation
CREATE INDEX idx_ext_outgoing_trace
  ON extraction_outgoing_request (project_id, environment_id, trace_id, ts_utc DESC);

-- User filter
CREATE INDEX idx_ext_outgoing_user
  ON extraction_outgoing_request (project_id, environment_id, user_id, ts_utc DESC);
```

## 6. Security and tenant isolation

- All queries scoped by `organization_id + project_id + environment_id`.
- Host domain and URL path displayed; full URL (including query params which may contain tokens/secrets) only accessible in raw detail view with explicit permission.
- `trace_id` correlation stays within organization scope.
- Cross-organization access denied.

## 7. Test strategy

Key feature tests:
- Outgoing request list groups by `host_domain` with correct status class counters.
- `status = server_error` filter returns only hosts with `server_error_count > 0` matching rows.
- Host search by partial domain (`stripe`) returns matching hosts.
- Sort by `server_error_count` orders rows correctly.
- Detail endpoint returns individual records for that host, paginated.
- Trace ID link from outgoing request to parent request resolves within same org.
- Cross-organization access denied.
- 4xx/5xx rows have correct visual class metadata in response.

## Related Resources

- **Functional Spec**: [outgoing-request.md](./outgoing-request.md)
- **Detail Page**: [outgoing-request-detail-technical.md](./outgoing-request-detail-technical.md)
- **Parent Spec**: [analytics/specs.md](../specs.md)
