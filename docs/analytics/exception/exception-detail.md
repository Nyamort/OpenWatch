# Functional Specifications - Exception Detail Analytics

## 1. Purpose

This page displays one selected exception signature with its stack context and full occurrence history.

## 2. Scope

Included:

- Exception-focused drilldown from Exceptions list table.
- Summary context (`first seen`, `last seen`, occurrence volume, impacted entities).
- Stack trace visualization for the selected exception.
- Occurrence-by-occurrence table for inspection.

Excluded:

- Exception mutation or remediation actions from analytics UI.
- Automatic incident workflow management.

## 3. Inherited Requirements

- `FR-AN-023`: row actions support navigation to detail view.
- `FR-AN-024`: free-text filtering behavior remains consistent on occurrence tables where applicable.
- `FR-AN-REQ-EXC-015`: navigation preserves period/search/filter context from Exceptions page.

## 4. Header and Summary

- `FR-AN-EXDETAIL-001`: The page title is the selected exception message/summary.
- `FR-AN-EXDETAIL-002`: Top controls include user scope filter (example: `All Users`).
- `FR-AN-EXDETAIL-003`: An issue-action control may be available (example: `To Issue`) when integrations are enabled.
- `FR-AN-EXDETAIL-004`: The summary panel includes at minimum `last seen` and `first seen`.
- `FR-AN-EXDETAIL-005`: The summary panel includes total occurrences for active filters.
- `FR-AN-EXDETAIL-006`: The summary panel includes handled-state counters (`handled`, `unhandled`) with charted distribution.
- `FR-AN-EXDETAIL-007`: An impact section displays at least `events`, `users`, and `servers` counts for active filters.
- `FR-AN-EXDETAIL-008`: Distribution tooltip shows bucket timestamp (UTC), handled-state counts, and total.

## 5. Exception Section

- `FR-AN-EXDETAIL-009`: The page includes an `Exception` card with handled/unhandled status badge.
- `FR-AN-EXDETAIL-010`: The card shows class + message preview for the selected signature.
- `FR-AN-EXDETAIL-011`: Stack trace is rendered as frame rows with file path + line context when available.
- `FR-AN-EXDETAIL-012`: Stack trace supports expand/collapse behavior for large traces.
- `FR-AN-EXDETAIL-013`: Runtime metadata chips may include language/framework/runtime versions when available.

## 6. Occurrences Section

- `FR-AN-EXDETAIL-014`: A section title shows occurrence count in active filters (example: `14 occurrences`).
- `FR-AN-EXDETAIL-015`: Section controls include segmented user-state filters: `View all`, `Authenticated`, `Guest`.
- `FR-AN-EXDETAIL-016`: Filter changes update summary cards and occurrence table consistently.
- `FR-AN-EXDETAIL-017`: Occurrence table columns are: `date`, `source`, `message`, `user`.
- `FR-AN-EXDETAIL-018`: Rows are sorted by `date` descending by default.
- `FR-AN-EXDETAIL-019`: Table supports pagination for larger occurrence history.

## 7. Navigation and Edge Cases

- `FR-AN-EXDETAIL-020`: Back navigation returns to Exceptions list with preserved filters/context.
- `FR-AN-EXDETAIL-021`: If selected exception signature is missing or invalid, page falls back to Exceptions list with guidance.


## Technical Specifications

See dedicated technical specification: [exception-detail-technical.md](./exception-detail-technical.md)
