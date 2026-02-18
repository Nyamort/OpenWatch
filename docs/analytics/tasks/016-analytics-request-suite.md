# Task T-016: Request Analytics (List, Route Drilldown, Detail)
- Domain: `analytics`
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement request analytics surface, including route-scoped view and request detail with events/headers/timeline/exception panels.

## How to implement
1. Add base request aggregated endpoint + route drilldown endpoint.
2. Build detail endpoint returning request object + relations + telemetry timeline.
3. Implement charts (count/status buckets) and period-aware filtering.
4. Preserve context when moving between list, route page, and detail.

## Architecture implications
- **Context**: analytics read models for request family.
- **Data**: request summary queries with heavy indexes on `timestamp`, status, project/environment, route.
- **Cross-context**: correlation IDs for link to logs/queries/jobs/notifications.
- **UI**: reusable detail tabs/sections for headers/events/timeline.

## Acceptance checkpoints
- Route-level filters and navigation are stable across period changes.
- Detail back-navigation returns list with preserved state.

## Done criteria
- `FR-AN-REQ-REQ-*` requirements in `docs/analytics/request/*` and shared requirements `FR-AN-023`.
