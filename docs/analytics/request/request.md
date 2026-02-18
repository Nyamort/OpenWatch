# Functional Specifications - Request Analytics

## 1. Purpose

This page analyzes `request` records for a selected project and environment.

## 2. Scope

Included:

- Request count and performance tracking.
- Request failure and status trend monitoring.
- Route-level visibility for performance investigation.
- Top route ranking with per-route method and response distribution.
- Route search and route-to-route drilldown.

## 3. Inherited Base Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-020`: paginated and sortable records list.
- `FR-AN-021`: default sorting by `timestamp` descending.
- `FR-AN-023`: row-to-detail drill-down.

## 4. Page-Specific Requirements

- `FR-AN-REQ-REQ-001`: The top-right control bar includes a user selector immediately to the left of the duration presets.
- `FR-AN-REQ-REQ-002`: User selection can be set to "All users" or a specific user identifier (`user` field from request records).
- `FR-AN-REQ-REQ-003`: Request records used in the page are filtered by selected user and selected period.
- `FR-AN-REQ-REQ-004`: The page shows a left bar chart of request count by response class for each time bucket: `1/2/3xx`, `4xx`, `5xx`.
- `FR-AN-REQ-REQ-005`: The bar chart stacks response classes with fixed color mapping: `1/2/3xx` in gray, `4xx` in orange, and `5xx` in red.
- `FR-AN-REQ-REQ-006`: Bar-chart tooltip shows bucket start time, counts for each class (`1/2/3xx`, `4xx`, `5xx`), and total request count.
- `FR-AN-REQ-REQ-007`: The page shows a right line chart with two series: `avg` response time (gray) and `p95` response time (orange).
- `FR-AN-REQ-REQ-008`: Line-chart tooltip shows both series values (`avg`, `p95`) for the hovered bucket.
- `FR-AN-REQ-REQ-009`: Chart bucketing is period-aware: `1h` uses 30-second buckets, `24h` uses 15-minute buckets, `7d` uses 2-hour buckets, and `14d` uses 4-hour buckets.
- `FR-AN-REQ-REQ-010`: `30d` uses fixed 6-hour buckets.
- `FR-AN-REQ-REQ-011`: `custom` bucket size is auto-derived to keep <=300 buckets and defaults to the nearest minute boundary aligned with selected range.
- `FR-AN-REQ-REQ-012`: A table is displayed below both charts with route-level aggregation.
- `FR-AN-REQ-REQ-013`: Route table is paginated at 25 rows per page.
- `FR-AN-REQ-REQ-014`: Route table columns are: method(s), path, `1/2/3xx` count, `4xx` count, `5xx` count, total requests, `avg` response time, `p95` response time, and action.
- `FR-AN-REQ-REQ-015`: Method display shows all HTTP methods observed for a route in a single cell (example: `GET|HEAD`).
- `FR-AN-REQ-REQ-016`: Action column exposes a route-specific drilldown link to a dedicated request-stat page filtered by the selected route.
- `FR-AN-REQ-REQ-017`: The route table is ordered by latest request timestamp descending by default.
- `FR-AN-REQ-REQ-018`: Empty-state for route table appears when no route has matching request records in the selected filters.
- `FR-AN-REQ-REQ-019`: Route drilldown navigation opens [`request-route`](./request-route.md) with context-preserved filters (`project`, `environment`, period, and user when selected).
- `FR-AN-REQ-REQ-020`: Period presets are displayed as compact chips in this order: `1H`, `24H`, `7D`, `14D`, `30D`, then custom range control.
- `FR-AN-REQ-REQ-021`: The currently selected period chip has a distinct active state.
- `FR-AN-REQ-REQ-022`: Left chart card header shows total request count and per-class counters (`1/2/3xx`, `4xx`, `5xx`) for the active filter context.
- `FR-AN-REQ-REQ-023`: Right chart card header shows response-time summary range (`min-max`) and summary counters (`avg`, `p95`) for the active filter context.
- `FR-AN-REQ-REQ-024`: Chart tooltips use bucket start timestamp in UTC display format and show metric values in milliseconds where relevant.
- `FR-AN-REQ-REQ-025`: A routes section title displays total route count for the active filters (example: `19 Routes`).
- `FR-AN-REQ-REQ-026`: A route search input is available at the top-right of the routes section and filters rows by route path/domain text.
- `FR-AN-REQ-REQ-027`: The route table supports column sorting for aggregate metric columns (`1/2/3xx`, `4xx`, `5xx`, `total`, `avg`, `p95`).
- `FR-AN-REQ-REQ-028`: Route rows visually emphasize non-zero `4xx` and `5xx` counts with warning/error styling.
- `FR-AN-REQ-REQ-029`: The action cell uses an icon-based control to open route-scoped analytics while preserving current filter context.
- `FR-AN-REQ-REQ-030`: Route path cell can include host/domain context when route identity depends on domain-bound routing.


## Technical Specifications

See dedicated technical specification: [request-technical.md](./request-technical.md)
