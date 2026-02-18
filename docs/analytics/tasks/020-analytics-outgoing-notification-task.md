# Task T-020: Outgoing Request, Notification and Scheduled-Task Analytics
- Domain: `analytics`
- Status: `not started`
- Priority: `P1`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement analytics for outgoing requests, notifications, and scheduled tasks with all required list/detail drill-through and threshold/charts semantics.

## How to execute
1. Outgoing requests: host/domain aggregation, 1/2/3xx/4/5xx counters, and domain detail.
2. Notifications: aggregated by type/status and detail pages for selected notification runs.
3. Scheduled tasks: two-card chart headers, task-level drill-down, and run detail timelines.
4. Add explicit empty and no-data states.

## Architecture implications
- **Context**: three analytics handlers with similar aggregation pattern.
- **Data**: host/task identity keys with grouped metrics.
- **UI**: common status badge styles and warning states.
- **Navigation**: shared back-link context restoration.

## Acceptance checkpoints
- Drilldown actions preserve filter/context state.
- Scheduled tasks detail includes run/action timeline and scheduler flags.

## Done criteria
- `docs/analytics/outgoing-request/*`, `docs/analytics/notification/*`, `docs/analytics/scheduled-task/*` completed.
