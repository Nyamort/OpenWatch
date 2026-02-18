# Functional Specifications - Route-Scoped Request Analytics

## 1. Purpose

This page analyzes `request` records for a single route in a selected project and environment.

## 2. Scope

Included:

- The same two-chart layout as the main request analytics page.
- Per-route filters and per-route request table.
- Route-scoped drill-down to individual request detail pages.

Excluded:

- Organization-level analytics.
- Non-request telemetry types.

## 3. Inherited Layout and Access

- `FR-AN-REQ-REQ-001`: top-right control bar includes user selector.
- `FR-AN-REQ-REQ-002`: user filter applies on selected scope.
- `FR-AN-REQ-REQ-003`: request records are filtered by selected user, period, and selected route.
- `FR-AN-REQ-REQ-010`: `30d` uses 6-hour buckets.
- `FR-AN-REQ-REQ-011`: `custom` bucket size is auto-derived to keep <=300 buckets.
- `FR-AN-REQ-REQ-019`: route drilldown preserves `project`, `environment`, period, and user context.
- `FR-AN-REQ-REQ-013`: route table is paginated by 25 rows per page (reused for route page listing).

## 4. Page Header and Filters

- `FR-AN-RREQ-001`: The page shows a breadcrumb with `Requests` as the first segment.
- `FR-AN-RREQ-002`: The page headline shows `<METHOD_BADGES> <ROUTE_PATH>`, where method badges are displayed before route name.
- `FR-AN-RREQ-003`: The top-right global controls include user selector and period presets (`1H`, `24H`, `7D`, `14D`, `30D`, custom range control).
- `FR-AN-RREQ-004`: Below charts, the list section header shows `<N> Requests` for the active route and filters.
- `FR-AN-RREQ-005`: The list section header right side includes two segmented filter groups:
  - duration filter: `View all`, `>= AVG`, `>= P95`
  - status filter: `View all`, `1/2/3xx`, `4xx`, `5xx`.
- `FR-AN-RREQ-006`: The duration filter (`>= AVG`, `>= P95`) is applied against per-request duration and updates charts + table.
- `FR-AN-RREQ-007`: The status filter is applied against request status classes and updates charts + table.
- `FR-AN-RREQ-008`: Status filter segments can display count badges for each class in current filter context.

## 5. Charts (Same as Request Root Page)

- `FR-AN-RREQ-009`: The page shows the same two-chart layout as the request page:
  - left bar chart by response class (`1/2/3xx`, `4xx`, `5xx`) with fixed colors.
  - right line chart with two series (`avg`, `p95`) and matching tooltips.
- `FR-AN-RREQ-010`: The right chart legend supports `threshold` indicator and displays `N/A` when no threshold policy is configured.
- `FR-AN-RREQ-011`: Both charts show only records matching the selected route and the active route-scoped filters.
- `FR-AN-RREQ-012`: Bucket sizes remain period-aware as in the main request page.

## 6. Request List

- `FR-AN-RREQ-013`: The table below the charts shows one row per request record (route-matched and active filters).
- `FR-AN-RREQ-014`: Table columns are: `date`, `method`, `details`, `status`, `duration`, `action`.
- `FR-AN-RREQ-015`: `details` includes route path and relevant summary context (`trace_id`, `_group`, `request_preview` if available).
- `FR-AN-RREQ-016`: `action` opens [`request-detail`](./request-detail.md) for the selected request.
- `FR-AN-RREQ-017`: `action` preserves current route context and list filters when possible.
- `FR-AN-RREQ-018`: Table supports sorting and pagination.

## 7. Route Behavior and Edge Cases

- `FR-AN-RREQ-019`: If a route has no matching request data, a contextual empty state explains filters and allows resetting them to defaults.
- `FR-AN-RREQ-020`: If a route identifier is missing in a request context, the page falls back to the global request page.


## Technical Specifications

See dedicated technical specification: [request-route-technical.md](./request-route-technical.md)
