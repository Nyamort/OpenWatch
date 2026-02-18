# Functional Specifications - User Detail Analytics

## 1. Purpose

This page analyzes one selected user across requests and related telemetry in a selected project and environment.

## 2. Scope

Included:

- User-focused drilldown from Users list table.
- User identity and last-seen context.
- Request-centric performance and outcome analytics for selected user.
- Navigation pivots to user-filtered analytics pages (`Requests`, `Jobs`, `Exceptions`, `Logs`).

Excluded:

- User profile mutation actions.
- Organization membership/role administration.

## 3. Inherited Requirements

- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-REQ-USER-013`: navigation preserves context from Users list.

## 4. Header and Summary

- `FR-AN-UDETAIL-001`: Page header starts with `Users` context and selected-user scope.
- `FR-AN-UDETAIL-002`: Top-left summary card displays `id`, `username`, and `last seen`.
- `FR-AN-UDETAIL-003`: Summary card includes a `Filter by` control with segments: `Requests`, `Jobs`, `Exceptions`, `Logs`.
- `FR-AN-UDETAIL-004`: Clicking a `Filter by` segment redirects to the corresponding analytics page (`Requests`, `Jobs`, `Exceptions`, or `Logs`) with the selected user filter applied.
- `FR-AN-UDETAIL-004A`: Redirection from `Filter by` preserves current project, environment, period, and selected user context.
- `FR-AN-UDETAIL-005`: Top-right chart card headline metric is requests count for selected user in active filters.
- `FR-AN-UDETAIL-006`: Top-right chart includes status counters for `1/2/3xx`, `4xx`, and `5xx`.
- `FR-AN-UDETAIL-007`: Top-right chart tooltip includes bucket timestamp (UTC), status counters, and total.

## 5. Route Insight Cards

- `FR-AN-UDETAIL-008`: The page shows a `Top Routes` card ranked by request count for selected user.
- `FR-AN-UDETAIL-009`: The page shows a `Slowest Routes` card ranked by duration using `P95`.
- `FR-AN-UDETAIL-010`: Route cards are constrained by selected user + period.

## 6. Requests Activity Section

- `FR-AN-UDETAIL-011`: Section title shows matching request count (example: `1 Request`).
- `FR-AN-UDETAIL-012`: Section right controls include duration segments: `View all`, `>= AVG`, `>= P95`.
- `FR-AN-UDETAIL-013`: Section right controls include status segments: `View all`, `1/2/3xx`, `4xx`, `5xx`.
- `FR-AN-UDETAIL-014`: Segment changes update chart metrics and request table consistently.

## 7. Requests Activity Table

- `FR-AN-UDETAIL-015`: Table columns are: `date`, `method`, `url`, `status`, `duration`, `action`.
- `FR-AN-UDETAIL-016`: Rows are sorted by date descending by default.
- `FR-AN-UDETAIL-017`: Table supports pagination for larger histories.
- `FR-AN-UDETAIL-018`: Action opens request-level detail while preserving user + period context.

## 8. Navigation and Edge Cases

- `FR-AN-UDETAIL-019`: Back navigation returns to Users list with preserved filters/context.
- `FR-AN-UDETAIL-020`: If selected user is missing or invalid, page falls back to Users list with guidance.


## Technical Specifications

See dedicated technical specification: [user-detail-technical.md](./user-detail-technical.md)
