# Technical Specifications - Dashboard

## 1. Architecture and data sources

The dashboard is a read-only aggregation page powered by queries across multiple analytics record types. It does **not** own dedicated storage — it queries `telemetry_records` (and derived read models) with pre-defined period windows.

Data source mapping by section:

| Section | Source types | Query type |
|---------|-------------|------------|
| Activity — request volume + duration | `request` | Bucketed time series + summary |
| Application — Exceptions card | `exception` | Period aggregate (total, handled/unhandled) |
| Application — Jobs card | `queued-job`, `job-attempt` | Period aggregate (status counters + duration) |
| Application — Thresholds CTA | `threshold_rules` | Count of active rules in scope |
| Users — Impacted users | `exception` + user projection | Top N users by exception count |
| Users — Most active users | `request` + user projection | Top N users by request count |
| Users — Activity chart | `request` | Bucketed time series (authenticated vs guest) |

## 2. Data model and aggregation

Dashboard reads from:

- `telemetry_records` (partitioned): filtered by `organization_id`, `project_id`, `environment_id`, `ts_utc` window.
- `dashboard_snapshots` (optional, precomputed): periodic aggregates for 7d/14d/30d periods.
  - Columns: `organization_id`, `project_id`, `environment_id`, `period_key`, `section`, `payload` (jsonb), `computed_at`.
  - Refreshed every 5 minutes for periods ≥ 7d via `RefreshDashboardSnapshotsJob`.
- `threshold_rules`: queried for count of active/triggered rules per scope.

Period window resolution:
- `1h`, `24h` → queried live from `telemetry_records`.
- `7d`, `14d`, `30d` → served from `dashboard_snapshots` if fresh (< 5 min old), else computed live and cached.

Bucketing for activity/user series charts follows the shared bucketing service:
- `1h` → 30s buckets, `24h` → 15m buckets, `7d` → 2h buckets.

## 3. API contracts

Dashboard data is loaded via Inertia page load with deferred props per section to allow progressive rendering.

```
GET /dashboard
```

Query params: `period` (1h | 24h | 7d | 14d | 30d, default: `24h`), `project`, `environment`.

Response shape:
```json
{
  "period": "24h",
  "activity": {
    "request_count": 1240,
    "status_summary": { "success": 1100, "client_error": 95, "server_error": 45 },
    "duration_summary": { "avg_ms": 142, "p95_ms": 890 },
    "series": [{ "bucket_start_utc": "2026-03-14T00:00:00Z", "success": 80, "client_error": 5, "server_error": 2 }]
  },
  "exceptions": {
    "total": 312,
    "handled": 210,
    "unhandled": 102
  },
  "jobs": {
    "total": 540,
    "failed": 12,
    "processed": 510,
    "released": 18,
    "avg_ms": 320,
    "p95_ms": 1400
  },
  "thresholds": {
    "active_count": 3,
    "triggered_count": 1
  },
  "users": {
    "impacted": [{ "user_id": "...", "name": "...", "exception_count": 5 }],
    "most_active": [{ "user_id": "...", "name": "...", "request_count": 120 }],
    "activity_series": [{ "bucket_start_utc": "...", "authenticated": 42, "guest": 18 }]
  }
}
```

Each section can also be fetched independently for deferred prop resolution.

## 4. Performance and caching

Cache strategy (Redis):

| Section | TTL | Strategy |
|---------|-----|----------|
| Activity (1h / 24h) | 30 seconds | Per-scope key |
| Activity (7d+) | 5 minutes | `dashboard_snapshots` |
| Exceptions summary | 30 seconds | Per-scope + period |
| Jobs summary | 30 seconds | Per-scope + period |
| Threshold count | 60 seconds | Per-org + project |
| Users sections | 60 seconds | Per-scope + period |

Cache key pattern: `dashboard:{org_id}:{project_id}:{env_id}:{period}:{section}`.

Cache invalidation: cleared when threshold configuration changes or project/environment is mutated.

SLA targets:
- Warm cache (all sections): p95 < 50ms.
- Cold (live query, recent period): p95 < 300ms total page load.
- Cold (live query, 7d+): p95 < 800ms (snapshot should be warm in normal operation).

## 5. Security and tenant isolation

- Dashboard routes require: authenticated user + verified organization context + `project:view` permission.
- All queries scoped by `organization_id + project_id + environment_id` — no cross-tenant leak by construction.
- Organization Viewer: full read access to all dashboard sections.
- Organization Developer/Admin/Owner: full read access + threshold `Add Threshold` CTA visible (requires `alert:create` permission).
- Users section (impacted/active users) respects PII policy: user identifiers shown only when present in records.
- Forbidden access returns `403` with empty body — never partial data.

## 6. Frontend integration

- Inertia page: `Dashboard/Index`.
- **Deferred props** (Inertia v2) used for heavy sections (users, jobs, exceptions) to allow progressive rendering.
- Period selector managed via URL state (`?period=24h`); changing period triggers Inertia reload preserving project/environment context.
- Each section renders an explicit skeleton while deferred props resolve.
- Navigation links from widgets forward `?period=` and active project/environment context.

Empty states per section:
- Activity: "No requests in selected period" + link to Requests analytics.
- Exceptions: "No exceptions recorded" + link to Exceptions page.
- Jobs: "No jobs recorded" + link to Jobs page.
- Thresholds: "No thresholds configured" + `Add Threshold` CTA button.
- Users: "No user activity" + link to Users analytics.

## 7. Test strategy

Key feature tests:

- Dashboard loads with default `24h` period and correct section data.
- Period change (`?period=7d`) updates all sections with correct time window.
- Activity section correctly computes request status split and duration metrics.
- Exceptions card shows correct handled/unhandled counts.
- Jobs card shows correct status counters (failed/processed/released).
- Thresholds CTA shown when 0 active rules; count badge shown when rules exist.
- Users sections show top N impacted/active users with correct counts.
- Cache: second call within TTL returns cached response without hitting DB.
- Cross-organization access denied (403) for all dashboard routes.
- Organization Viewer can view dashboard but does not see `Add Threshold` CTA.
- Empty state rendered correctly when no telemetry exists in period.
- `RefreshDashboardSnapshotsJob` updates `dashboard_snapshots` for all active project/env pairs.

## Related Resources

- **Functional Spec**: [specs.md](./specs.md)
- **Related Specs**: [analytics/specs.md](../analytics/specs.md), [alerts/specs.md](../alerts/specs.md), [issues/specs.md](../issues/specs.md)
- **Implementation Tasks**: [027 - Dashboard Experience](./tasks/027-dashboard-experience.md)
