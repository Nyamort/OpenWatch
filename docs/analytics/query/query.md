# Functional Specifications - Query Analytics

## 1. Purpose

This page analyzes `query` records for a selected project and environment.

## 2. Inherited Base Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-020`: paginated and sortable records list.
- `FR-AN-021`: default sorting by `timestamp` descending.
- `FR-AN-023`: row-to-detail drill-through.

## 3. Page-Specific Requirements

- `FR-AN-REQ-QUERY-001`: The page uses a two-card chart header.
- `FR-AN-REQ-QUERY-002`: Left card headline metric is total query calls count for active filters.
- `FR-AN-REQ-QUERY-003`: Left chart visualizes query calls by time bucket.
- `FR-AN-REQ-QUERY-004`: Left chart tooltip shows bucket timestamp (UTC), `calls`, and bucket total.
- `FR-AN-REQ-QUERY-005`: Right card headline metric shows query duration range (`min-max`) for active filters.
- `FR-AN-REQ-QUERY-006`: Right card summary shows `avg` and `p95` duration values.
- `FR-AN-REQ-QUERY-007`: Right chart tooltip includes bucket timestamp (UTC) and both `avg` and `p95`.
- `FR-AN-REQ-QUERY-008`: Section title below charts displays total unique query signatures for active filters (example: `2 Queries`).
- `FR-AN-REQ-QUERY-009`: A `Search queries` input filters rows by query text/signature.
- `FR-AN-REQ-QUERY-010`: The table is aggregated by query signature.
- `FR-AN-REQ-QUERY-011`: Table columns are: `query`, `connection`, `calls`, `total`, `avg`, `p95`, `action`.
- `FR-AN-REQ-QUERY-012`: Table supports sorting on `calls`, `total`, `avg`, and `p95`.
- `FR-AN-REQ-QUERY-013`: Query text cell can display syntax-highlighted SQL preview.
- `FR-AN-REQ-QUERY-014`: Duration values support adaptive units (`us`, `ms`) depending on magnitude.
- `FR-AN-REQ-QUERY-015`: Action column opens [`query-detail`](./query-detail.md) while preserving active filters.
- `FR-AN-REQ-QUERY-016`: Empty-state behavior is explicit when no query records match active filters or search.


## Technical Specifications

See dedicated technical specification: [query-technical.md](./query-technical.md)
