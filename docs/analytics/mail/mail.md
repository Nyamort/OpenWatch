# Functional Specifications - Mail Analytics

## 1. Purpose

This page analyzes `mail` records for a selected project and environment.

## 2. Inherited Base Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-020`: paginated and sortable records list.
- `FR-AN-021`: default sorting by `timestamp` descending.
- `FR-AN-023`: row-to-detail drill-through.

## 3. Page-Specific Requirements

- `FR-AN-REQ-MAIL-001`: The page uses a two-card chart header.
- `FR-AN-REQ-MAIL-002`: Left card headline metric is total mails count for active filters.
- `FR-AN-REQ-MAIL-003`: Left chart visualizes mail calls by time bucket.
- `FR-AN-REQ-MAIL-004`: Left chart tooltip shows bucket timestamp (UTC), `calls`, and bucket total.
- `FR-AN-REQ-MAIL-005`: Right card headline metric shows duration range (`min-max`) for active filters.
- `FR-AN-REQ-MAIL-006`: Right card summary shows `avg` and `p95` duration values.
- `FR-AN-REQ-MAIL-007`: Right chart tooltip includes bucket timestamp (UTC) and both `avg` and `p95`.
- `FR-AN-REQ-MAIL-008`: Section title below charts displays total unique mail classes in active filters (example: `1 Mail`).
- `FR-AN-REQ-MAIL-009`: A `Search mail` input filters rows by mail class/name.
- `FR-AN-REQ-MAIL-010`: The table is aggregated by mail class/name.
- `FR-AN-REQ-MAIL-011`: Table columns are: `mail`, `count`, `avg`, `p95`, `action`.
- `FR-AN-REQ-MAIL-012`: Table supports sorting for `count`, `avg`, and `p95`.
- `FR-AN-REQ-MAIL-013`: Action column opens [`mail-detail`](./mail-detail.md) while preserving active filters.
- `FR-AN-REQ-MAIL-014`: Empty-state behavior is explicit when no mail records match active filters or search.


## Technical Specifications

See dedicated technical specification: [mail-technical.md](./mail-technical.md)
