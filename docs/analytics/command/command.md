# Functional Specifications - Command Analytics

## 1. Purpose

This page analyzes `command` records for a selected project and environment.

## 2. Inherited Base Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-020`: paginated and sortable records list.
- `FR-AN-021`: default sorting by `timestamp` descending.
- `FR-AN-023`: row-to-detail drill-through.

## 3. Page-Specific Requirements

- `FR-AN-REQ-CMD-001`: The page uses a two-card chart header.
- `FR-AN-REQ-CMD-002`: Left card headline metric is total command calls count for active filters.
- `FR-AN-REQ-CMD-003`: Left card status counters include `successful` and `unsuccessful`.
- `FR-AN-REQ-CMD-004`: Left chart tooltip shows bucket timestamp (UTC), `successful`, `unsuccessful`, and `total`.
- `FR-AN-REQ-CMD-005`: Right card headline metric shows duration range (`min-max`) for active filters.
- `FR-AN-REQ-CMD-006`: Right card summary shows `avg` and `p95` duration values.
- `FR-AN-REQ-CMD-007`: Right chart tooltip includes bucket timestamp (UTC) and both `avg` and `p95`.
- `FR-AN-REQ-CMD-008`: Section title below charts shows total unique commands in active filters (example: `3 Commands`).
- `FR-AN-REQ-CMD-009`: A `Search commands` input filters rows by command text/name.
- `FR-AN-REQ-CMD-010`: The table is aggregated by command identity.
- `FR-AN-REQ-CMD-011`: Table columns are: `command`, `success`, `failed`, `total`, `avg`, `p95`, `action`.
- `FR-AN-REQ-CMD-012`: Table supports sorting on `success`, `failed`, `total`, `avg`, and `p95`.
- `FR-AN-REQ-CMD-013`: Non-zero failed counts use error styling for visibility.
- `FR-AN-REQ-CMD-014`: Action column opens [`command-detail`](./command-detail.md) while preserving active filters.
- `FR-AN-REQ-CMD-015`: Empty-state behavior is explicit when no commands match active filters or search.


## Technical Specifications

See dedicated technical specification: [command-technical.md](./command-technical.md)
