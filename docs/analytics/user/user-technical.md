# Technical Specifications - User Analytics

## 1. Data model and cross-type projection

User analytics aggregates activity data across multiple record types — it does not have its own dedicated record type. Source types:

| Source type | User data extracted |
|-------------|---------------------|
| `request` | user_id, request count, last seen |
| `queued-job` | user_id, job count |
| `exception` | user_id, exception count, last impacted |

Extracted columns in `extraction_user_activity` (materialized read model, refreshed periodically):
- `organization_id`, `project_id`, `environment_id`
- `user_id` → stable user identifier from payload
- `user_name` → display name from most recent user snapshot (optional)
- `request_count` → total requests in period
- `exception_count` → total exceptions impacted in period
- `job_count` → total job executions in period
- `first_seen` → earliest activity timestamp
- `last_seen` → most recent activity timestamp

**Important**: `user_id` is the identifier provided by the application via the Laravel Nightwatch agent. It is not linked to the platform's own `users` table — it is an application-side user reference.

## 2. Cross-type aggregation query

User activity is computed by joining across `extraction_request`, `extraction_exception`, and `extraction_job`:

```sql
-- Simplified illustration (actual uses UNION ALL + GROUP BY)
SELECT
  COALESCE(r.user_id, e.user_id, j.user_id) AS user_id,
  SUM(r.request_count) AS request_count,
  SUM(e.exception_count) AS exception_count,
  SUM(j.job_count) AS job_count,
  GREATEST(r.last_seen, e.last_seen, j.last_seen) AS last_seen
FROM (
  SELECT user_id, COUNT(*) request_count, MAX(ts_utc) last_seen
  FROM extraction_request WHERE project_id = ? AND ts_utc BETWEEN ? AND ?
  GROUP BY user_id
) r
FULL OUTER JOIN (...) e ON r.user_id = e.user_id
FULL OUTER JOIN (...) j ON ...
GROUP BY user_id
```

For performance, this aggregation is pre-computed in `extraction_user_activity` for common period windows (24h, 7d) and refreshed every 5 minutes.

## 3. User activity chart

Left chart: authenticated user count over time (users with non-null `user_id` in `extraction_request`).
- Computed as `COUNT(DISTINCT user_id) per bucket` from `extraction_request`.
- Empty `user_id` → guest; non-empty → authenticated.

Right card summary: total requests + split between `authenticated` and `guest` for active period.

```sql
SELECT
  COUNT(*) AS total_requests,
  COUNT(*) FILTER (WHERE user_id IS NOT NULL) AS authenticated,
  COUNT(*) FILTER (WHERE user_id IS NULL) AS guest
FROM extraction_request
WHERE project_id = ? AND environment_id = ? AND ts_utc BETWEEN ? AND ?
```

## 4. API contracts

```
GET /analytics/users
```

Query params: `period`, `from`, `to`, `search`, `page`, `sort` (request_count | exception_count | job_count | last_seen), `direction`.

Response:
```json
{
  "summary": {
    "total_requests": 12400,
    "authenticated": 8900,
    "guest": 3500,
    "unique_users": 142
  },
  "activity_series": [{ "bucket_start_utc": "...", "authenticated": 42, "guest": 18 }],
  "rows": [
    {
      "user_id": "user_123",
      "user_name": "John Doe",
      "request_count": 450,
      "exception_count": 3,
      "job_count": 12,
      "last_seen": "2026-03-14T12:00:00Z"
    }
  ],
  "pagination": { "current_page": 1, "total": 142 }
}
```

```
GET /analytics/users/{user_id}
```

Returns user detail: activity breakdown per type (requests, exceptions, jobs) with paginated rows and timeline. Links to individual request/exception detail pages.

## 5. Index strategy

```sql
-- User activity aggregation across request type
CREATE INDEX idx_ext_request_user_scope
  ON extraction_request (organization_id, project_id, environment_id, user_id, ts_utc DESC);

-- Exception impact per user
CREATE INDEX idx_ext_exception_user_scope
  ON extraction_exception (organization_id, project_id, environment_id, user_id, ts_utc DESC);

-- User activity materialized read model
CREATE INDEX idx_ext_user_activity_scope
  ON extraction_user_activity (organization_id, project_id, environment_id, last_seen DESC);
```

## 6. Security and tenant isolation

- All queries scoped by `organization_id + project_id + environment_id`.
- `user_id` and `user_name` are application-provided identifiers — they are displayed as-is; the platform does not enrich or link them to platform accounts.
- PII considerations: `user_id` and `user_name` may contain personal data. Display is gated by `analytics.view` permission; no additional PII redaction in MVP.
- Cross-organization user data inaccessible by construction.
- `user_id = NULL` (guest) grouped separately from authenticated users — no identity inference.

## 7. Test strategy

Key feature tests:
- User list aggregates request/exception/job counts correctly per `user_id`.
- Authenticated vs guest split in summary matches actual `user_id` null/non-null distribution.
- Activity chart shows correct `authenticated` count per bucket (DISTINCT user_id, not raw count).
- Search by user_id or user_name returns matching rows.
- Sort by `exception_count` orders rows correctly.
- User detail returns per-type activity breakdown.
- Guest users (null user_id) do not appear in the user list (only in summary stats).
- Cross-organization access denied.

## Related Resources

- **Functional Spec**: [user.md](./user.md)
- **Detail Page**: [user-detail-technical.md](./user-detail-technical.md)
- **Parent Spec**: [analytics/specs.md](../specs.md)
- **Related**: [issues/specs.md](../../issues/specs.md) — user impact tracking in issues
