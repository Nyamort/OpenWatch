# Functional Specifications - Scheduled Task Detail Analytics

## 1. Purpose

This page analyzes one selected scheduled task in a selected project and environment.

## 2. Scope

Included:

- Task-focused drilldown from Scheduled Tasks list table.
- Same two-card chart header as Scheduled Tasks page, filtered to one selected task.
- Run-level table for selected task.

Excluded:

- Cross-task comparison in this page.
- Task mutation actions from analytics UI.

## 3. Inherited Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-REQ-TASK-001`: two-card chart header pattern reused from Scheduled Tasks page.

## 4. Header and Charts

- `FR-AN-STDETAIL-001`: Breadcrumb starts with `Scheduled Tasks`.
- `FR-AN-STDETAIL-002`: Page title is selected task command/name.
- `FR-AN-STDETAIL-003`: A schedule badge is shown near the title (example: `EVERY SECOND`).
- `FR-AN-STDETAIL-004`: Left metric card shows runs count for selected task and status counters (`failed`, `processed`, `skipped`).
- `FR-AN-STDETAIL-005`: Left chart tooltip includes bucket timestamp (UTC), status counters, and total.
- `FR-AN-STDETAIL-006`: Right metric card shows duration summary (`min-max`, `avg`, `p95`) for selected task.
- `FR-AN-STDETAIL-007`: Right metric card includes `threshold` indicator and displays `N/A` when no threshold policy is configured.
- `FR-AN-STDETAIL-008`: Both charts are filtered by selected task + active period.

## 5. Runs Section

- `FR-AN-STDETAIL-009`: Section title shows run count in active filters (example: `14 Runs`).
- `FR-AN-STDETAIL-010`: Section right controls include two segmented filters:
  - duration filter: `View all`, `>= AVG`, `>= P95`
  - status filter: `View all`, `Skipped`, `Failed`.
- `FR-AN-STDETAIL-011`: Status segments can show count badges for active filter context.
- `FR-AN-STDETAIL-012`: Duration/status filter changes update cards, charts, and runs table.

## 6. Runs Table

- `FR-AN-STDETAIL-013`: Table columns are: `date`, `status`, `message`, `duration`, `action`.
- `FR-AN-STDETAIL-014`: `status` is rendered as a badge with status-class styling.
- `FR-AN-STDETAIL-015`: Table rows are sorted by date descending by default.
- `FR-AN-STDETAIL-016`: Table supports pagination for longer run history.
- `FR-AN-STDETAIL-017`: Action column opens [`scheduled-task-run-detail`](./scheduled-task-run-detail.md) while preserving current filters/context.

## 7. Navigation and Edge Cases

- `FR-AN-STDETAIL-018`: Back navigation returns to Scheduled Tasks list with preserved filters/context.
- `FR-AN-STDETAIL-019`: If selected task is missing or invalid, the page falls back to Scheduled Tasks list with guidance.


## Technical Specifications

See dedicated technical specification: [scheduled-task-detail-technical.md](./scheduled-task-detail-technical.md)
