# Functional Specifications - Scheduled Task Analytics

## 1. Purpose

This page analyzes `scheduled-task` records for a selected project and environment.

## 2. Inherited Base Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-020`: paginated and sortable records list.
- `FR-AN-021`: default sorting by `timestamp` descending.
- `FR-AN-023`: row-to-detail drill-through.

## 3. Page-Specific Requirements

- `FR-AN-REQ-TASK-001`: The page uses a two-card chart header.
- `FR-AN-REQ-TASK-002`: Left card headline metric is total scheduled-task runs count for active filters.
- `FR-AN-REQ-TASK-003`: Left card status counters include `failed`, `processed`, and `skipped`.
- `FR-AN-REQ-TASK-004`: Left chart tooltip shows bucket timestamp (UTC), status counters (`processed`, `skipped`, `failed`), and total.
- `FR-AN-REQ-TASK-005`: Right card headline metric shows duration range (`min-max`) for active filters.
- `FR-AN-REQ-TASK-006`: Right card summary shows `avg` and `p95` duration values.
- `FR-AN-REQ-TASK-007`: Right chart tooltip includes bucket timestamp (UTC), `avg`, and `p95`.
- `FR-AN-REQ-TASK-008`: Section title below charts displays total unique tasks in active filters (example: `1 Task`).
- `FR-AN-REQ-TASK-009`: A `Search tasks` input filters rows by task command/name text.
- `FR-AN-REQ-TASK-010`: The table is aggregated by scheduled task identity.
- `FR-AN-REQ-TASK-011`: Table columns are: `task`, `schedule`, `next run`, `processed`, `skipped`, `failed`, `total`, `avg`, `p95`, `action`.
- `FR-AN-REQ-TASK-012`: Table supports sorting on `processed`, `skipped`, `failed`, `total`, `avg`, and `p95`.
- `FR-AN-REQ-TASK-013`: Non-zero `failed` values use error styling for visibility.
- `FR-AN-REQ-TASK-014`: Action column opens [`scheduled-task-detail`](./scheduled-task-detail.md) while preserving active filters.
- `FR-AN-REQ-TASK-015`: Empty-state behavior is explicit when no tasks match active filters or search.


## Technical Specifications

See dedicated technical specification: [scheduled-task-technical.md](./scheduled-task-technical.md)
