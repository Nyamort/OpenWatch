# Functional Specifications - Command Detail Analytics

## 1. Purpose

This page analyzes one selected command in a selected project and environment.

## 2. Scope

Included:

- Command-focused drilldown from Commands list table.
- Same two-card chart header as Commands page, filtered to one selected command.
- Execution-level table for selected command.

Excluded:

- Cross-command comparison in this page.
- Command mutation/rerun actions from analytics UI.

## 3. Inherited Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-REQ-CMD-001`: two-card chart header pattern reused from Commands page.

## 4. Header and Charts

- `FR-AN-CDETAIL-001`: Breadcrumb starts with `Commands`.
- `FR-AN-CDETAIL-002`: Page title is the selected command identifier (example: `app:test`).
- `FR-AN-CDETAIL-003`: Left metric card shows calls for selected command and status counters (`successful`, `unsuccessful`).
- `FR-AN-CDETAIL-004`: Left chart tooltip includes bucket timestamp (UTC), status counters, and total.
- `FR-AN-CDETAIL-005`: Right metric card shows duration summary and `avg`/`p95`.
- `FR-AN-CDETAIL-006`: Right metric card includes `threshold` indicator and displays `N/A` when no threshold policy is configured.
- `FR-AN-CDETAIL-007`: Both charts are filtered by selected command + active period.

## 5. Executions Section

- `FR-AN-CDETAIL-008`: Section title shows execution count in active filters (example: `1 Command`).
- `FR-AN-CDETAIL-009`: Section right controls include two segmented filters:
  - duration filter: `View all`, `>= AVG`, `>= P95`
  - status filter: `View all`, `Successful`, `Failed`.
- `FR-AN-CDETAIL-010`: Status segments can show count badges for active filter context.
- `FR-AN-CDETAIL-011`: Duration/status filter changes update cards, charts, and executions table.

## 6. Executions Table

- `FR-AN-CDETAIL-012`: Table columns are: `date`, `command`, `exit code`, `duration`, `action`.
- `FR-AN-CDETAIL-013`: Rows are sorted by date descending by default.
- `FR-AN-CDETAIL-014`: Table supports pagination for longer execution history.
- `FR-AN-CDETAIL-015`: Action column opens [`command-run-detail`](./command-run-detail.md) while preserving current filters/context.

## 7. Navigation and Edge Cases

- `FR-AN-CDETAIL-016`: Back navigation returns to Commands list with preserved filters/context.
- `FR-AN-CDETAIL-017`: If selected command is missing or invalid, the page falls back to Commands list with guidance.


## Technical Specifications

See dedicated technical specification: [command-detail-technical.md](./command-detail-technical.md)
