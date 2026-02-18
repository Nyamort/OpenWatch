# Functional Specifications - Job Detail Analytics

## 1. Purpose

This page analyzes one selected job class/name in a selected project and environment.

## 2. Scope

Included:

- Job-focused analytics drilldown from the Jobs list page.
- Same two-chart header as Jobs page, filtered to one selected job.
- Attempt-level table for the selected job.

Excluded:

- Cross-job comparison in this view.
- Job mutation/retry actions from analytics UI.

## 3. Inherited Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-REQ-JOBS-009`: user selector + period controls in header.
- `FR-AN-REQ-JOBS-017`: consistent status colors.

## 4. Page Header and Cards

- `FR-AN-JDETAIL-001`: Breadcrumb starts with `Jobs`.
- `FR-AN-JDETAIL-002`: Page title is the full job class/name selected from Jobs list.
- `FR-AN-JDETAIL-003`: Left metric card shows attempts total for selected job and status counters (`failed`, `processed`, `released`).
- `FR-AN-JDETAIL-004`: Left chart tooltip includes bucket timestamp and counts (`processed`, `released`, `failed`, `total`).
- `FR-AN-JDETAIL-005`: Right metric card shows duration range (`min-max`), `avg`, and `p95` for selected job.
- `FR-AN-JDETAIL-006`: Right metric card includes `threshold` indicator and displays `N/A` when no threshold is configured.
- `FR-AN-JDETAIL-007`: Both charts are filtered by selected job + active user + active period.

## 5. Attempts Section

- `FR-AN-JDETAIL-008`: Section title shows attempts count in active filters (example: `2 Attempts`).
- `FR-AN-JDETAIL-009`: Section right controls include two segmented filters:
  - duration filter: `View all`, `>= AVG`, `>= P95`
  - status filter: `View all`, `Processed`, `Released`, `Failed`.
- `FR-AN-JDETAIL-010`: Status segments can show count badges for active filter context.
- `FR-AN-JDETAIL-011`: Duration/status filter changes update cards, charts, and attempts table.

## 6. Attempts Table

- `FR-AN-JDETAIL-012`: Attempts table columns are: `date`, `connection`, `queue`, `attempt`, `status`, `duration`, `action`.
- `FR-AN-JDETAIL-013`: `status` is rendered as a badge with status-class styling.
- `FR-AN-JDETAIL-014`: Table rows are sorted by date descending by default.
- `FR-AN-JDETAIL-015`: Table supports pagination for long attempt histories.
- `FR-AN-JDETAIL-016`: Action column opens [`attempt-detail`](./attempt-detail.md) while preserving current job filters/context.

## 7. Edge Cases

- `FR-AN-JDETAIL-017`: If selected job has no attempts in active filters, the page shows a contextual empty state and reset-filter action.
- `FR-AN-JDETAIL-018`: If selected job identifier is missing or invalid, the page falls back to Jobs list with guidance.


## Technical Specifications

See dedicated technical specification: [job-detail-technical.md](./job-detail-technical.md)
