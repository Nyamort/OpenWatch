# Functional Specifications - User Analytics

## 1. Purpose

This page analyzes user activity aggregates for a selected project and environment.

## 2. Inherited Base Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-020`: paginated and sortable records list.
- `FR-AN-021`: default sorting by `timestamp` descending.
- `FR-AN-023`: row-to-detail drill-through.
- `FR-AN-024`: free-text search is available for the list.

## 3. Page-Specific Requirements

- `FR-AN-REQ-USER-001`: The page uses a two-card chart header.
- `FR-AN-REQ-USER-002`: Left card headline metric is authenticated users count for active filters.
- `FR-AN-REQ-USER-003`: Left chart plots authenticated user activity over time and tooltip includes bucket timestamp (UTC) + authenticated count.
- `FR-AN-REQ-USER-004`: Right card headline metric is total requests for active filters.
- `FR-AN-REQ-USER-005`: Right card counters include request actor split: `authenticated` and `guest`.
- `FR-AN-REQ-USER-006`: Right chart tooltip includes bucket timestamp (UTC), `authenticated`, `guest`, and total.
- `FR-AN-REQ-USER-007`: Section title below charts displays total unique users in active filters (example: `1 User`).
- `FR-AN-REQ-USER-008`: A `Search users` input filters rows by user identity fields.
- `FR-AN-REQ-USER-009`: The table is aggregated by user identity.
- `FR-AN-REQ-USER-010`: Table columns are: `user`, `1/2/3xx`, `4xx`, `5xx`, `requests`, `queued jobs`, `exceptions`, `last seen`.
- `FR-AN-REQ-USER-011`: `user` column shows stable identifier and display username when available.
- `FR-AN-REQ-USER-012`: Table supports sorting by request/error/job counters and `last seen`.
- `FR-AN-REQ-USER-013`: Selecting a row opens [`user-detail`](./user-detail.md) while preserving current period/search/sort context.
- `FR-AN-REQ-USER-014`: Empty-state behavior is explicit when no users match active filters or search.


## Technical Specifications

See dedicated technical specification: [user-technical.md](./user-technical.md)
