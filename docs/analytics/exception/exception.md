# Functional Specifications - Exception Analytics

## 1. Purpose

This page analyzes aggregated `exception` records for a selected project and environment.

## 2. Inherited Base Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-020`: paginated and sortable records list.
- `FR-AN-021`: default sorting by `timestamp` descending.
- `FR-AN-023`: row-to-detail drill-through.
- `FR-AN-024`: free-text search is available for the list.
- `FR-AN-025`: list supports status/outcome filtering when applicable.

## 3. Page-Specific Requirements

- `FR-AN-REQ-EXC-001`: The page uses a single top chart card with occurrence distribution over time.
- `FR-AN-REQ-EXC-002`: The chart headline metric is total exception occurrences for active filters.
- `FR-AN-REQ-EXC-003`: Header counters include `handled` and `unhandled` totals.
- `FR-AN-REQ-EXC-004`: Bar chart uses handled-state coloring (`handled`, `unhandled`) for visual comparison.
- `FR-AN-REQ-EXC-005`: Chart tooltip shows bucket timestamp (UTC), `handled`, `unhandled`, and bucket total.
- `FR-AN-REQ-EXC-006`: A user scope filter is available in the top controls (example: `All Users`) and updates card + list.
- `FR-AN-REQ-EXC-007`: Section title below the chart displays unique exception groups count in active filters (example: `5 Exceptions`).
- `FR-AN-REQ-EXC-008`: A `Search exceptions` input filters exception rows by class and message text.
- `FR-AN-REQ-EXC-009`: Status segmented filters are available: `View all`, `Handled`, `Unhandled`.
- `FR-AN-REQ-EXC-010`: Status segments may show badges with counts for the active filter context.
- `FR-AN-REQ-EXC-011`: The table is grouped by exception signature (class/message fingerprint), not by raw occurrence row.
- `FR-AN-REQ-EXC-012`: Table columns are: `last seen`, `exception`, `count`, `users`, `action`.
- `FR-AN-REQ-EXC-013`: Each row shows handled-state badge, exception class, and message preview.
- `FR-AN-REQ-EXC-014`: `count` is the number of matching occurrences in active filters; `users` is distinct impacted users count.
- `FR-AN-REQ-EXC-015`: Action column opens [`exception-detail`](./exception-detail.md) while preserving current period/search/filter context.
- `FR-AN-REQ-EXC-016`: Empty-state behavior is explicit when no exceptions match active filters or search.


## Technical Specifications

See dedicated technical specification: [exception-technical.md](./exception-technical.md)
