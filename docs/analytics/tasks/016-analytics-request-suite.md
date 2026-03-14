# Task T-016: Request Analytics Suite (List, Route Drilldown, Detail)
- Domain: `analytics`
- Status: `not started`
- Priority: `P0`
- Dependencies: `T-015`

## Description
Implement the request analytics surface with three levels: aggregated list (all routes), route-scoped drilldown, and individual request detail with events/headers/query/exception/timeline panels.

## How to implement
1. Implement `BuildRequestIndexData` action: aggregate from `extraction_requests` — group by route, count requests, avg/p95 duration, error rate, within period + org/project/env scope.
2. Implement `BuildRequestRouteData` action: same aggregation scoped to a single route; add bucketed chart (count by status class per bucket).
3. Implement `BuildRequestDetailData` action: fetch single request record + related records (queries, logs, exceptions, cache events) joined by `trace_id` / `execution_id`. Build chronological timeline.
4. Add `GET /analytics/{organization}/{project}/{environment}/requests` (index), `GET .../requests/route` (drilldown), `GET .../requests/{id}` (detail) routes.
5. Build Inertia pages: requests list with route table, route page with status-split chart, detail page with tab panels (overview, queries, logs, exceptions, timeline).
6. Preserve period/filter context in all navigation links (list → route → detail → back).
7. Write feature tests: index returns grouped routes, route drilldown filters correctly, detail returns related records, cross-org access blocked.

## Key files to create or modify
- `app/Actions/Analytics/Request/BuildRequestIndexData.php`
- `app/Actions/Analytics/Request/BuildRequestRouteData.php`
- `app/Actions/Analytics/Request/BuildRequestDetailData.php`
- `app/Http/Controllers/Analytics/RequestController.php`
- `resources/js/pages/analytics/requests/index.tsx`
- `resources/js/pages/analytics/requests/route.tsx`
- `resources/js/pages/analytics/requests/show.tsx`
- `tests/Feature/Analytics/RequestAnalyticsTest.php`

## Acceptance criteria
- [ ] Request list shows all routes with count, avg duration, p95 duration, error rate for the selected period
- [ ] Route drilldown shows the same metrics scoped to one route + a bucketed status chart
- [ ] Request detail shows the full timeline including all related queries, logs, and exceptions
- [ ] Navigating back from detail to route (or route to list) restores the previous filter/period state
- [ ] A request from a different org/project is not accessible
- [ ] Empty state is shown when no requests exist for the selected period/filters

## Related specs
- [Functional spec](../request/specs.md)
- [Technical spec](../request/request-technical.md)
