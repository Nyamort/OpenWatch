# Functional Specifications - Outgoing Request Analytics

## 1. Purpose

This page analyzes `outgoing-request` records for a selected project and environment.

## 2. Inherited Base Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-020`: paginated and sortable records list.
- `FR-AN-021`: default sorting by `timestamp` descending.
- `FR-AN-023`: row-to-detail drill-through.

## 3. Page-Specific Requirements

- `FR-AN-REQ-OUT-REQ-001`: The page uses a two-card chart header.
- `FR-AN-REQ-OUT-REQ-002`: Left card headline metric is total outgoing requests count for active filters.
- `FR-AN-REQ-OUT-REQ-003`: Left card status counters include `1/2/3xx`, `4xx`, and `5xx`.
- `FR-AN-REQ-OUT-REQ-004`: Left chart visualizes request counts by status class and time bucket.
- `FR-AN-REQ-OUT-REQ-005`: Left chart tooltip shows bucket timestamp (UTC), class counts (`1/2/3xx`, `4xx`, `5xx`), and total.
- `FR-AN-REQ-OUT-REQ-006`: Right card headline metric shows duration range (`min-max`) for active filters.
- `FR-AN-REQ-OUT-REQ-007`: Right card summary shows `avg` and `p95` duration values.
- `FR-AN-REQ-OUT-REQ-008`: Right chart tooltip includes bucket timestamp (UTC) and both `avg` and `p95`.
- `FR-AN-REQ-OUT-REQ-009`: Section title below charts displays total unique host/domain count for active filters (example: `1 Domain`).
- `FR-AN-REQ-OUT-REQ-010`: A `Search domains` input filters rows by host/domain text.
- `FR-AN-REQ-OUT-REQ-011`: The table is aggregated by host/domain identity.
- `FR-AN-REQ-OUT-REQ-012`: Table columns are: `host`, `1/2/3xx`, `4xx`, `5xx`, `total`, `avg`, `p95`, `action`.
- `FR-AN-REQ-OUT-REQ-013`: Table supports sorting on `1/2/3xx`, `4xx`, `5xx`, `total`, `avg`, and `p95`.
- `FR-AN-REQ-OUT-REQ-014`: Non-zero `4xx` and `5xx` values use warning/error styling.
- `FR-AN-REQ-OUT-REQ-015`: Action column opens [`outgoing-request-detail`](./outgoing-request-detail.md) while preserving active filters.
- `FR-AN-REQ-OUT-REQ-016`: Empty-state behavior is explicit when no domains match active filters or search.


## Technical Specifications

See dedicated technical specification: [outgoing-request-technical.md](./outgoing-request-technical.md)
