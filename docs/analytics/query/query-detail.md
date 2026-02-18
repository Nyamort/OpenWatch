# Functional Specifications - Query Detail Analytics

## 1. Purpose

This page analyzes one selected query signature in a selected project and environment.

## 2. Scope

Included:

- Query-focused drilldown from Query list table.
- Same two-card chart header as Query page, filtered to one selected query signature.
- Execution-level table for selected query signature.

Excluded:

- Cross-query comparison in this page.
- Query mutation actions from analytics UI.

## 3. Inherited Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-REQ-QUERY-001`: two-card chart header pattern reused from Query page.

## 4. Header and Charts

- `FR-AN-QDETAIL-001`: Breadcrumb starts with `Queries`.
- `FR-AN-QDETAIL-002`: Page title is the selected SQL query signature/text.
- `FR-AN-QDETAIL-003`: Left metric card shows total calls for selected query in active filters.
- `FR-AN-QDETAIL-004`: Left chart tooltip includes bucket timestamp (UTC), `calls`, and total.
- `FR-AN-QDETAIL-005`: Right metric card shows duration summary (`min-max` or single value when one call), with `avg` and `p95`.
- `FR-AN-QDETAIL-006`: Right chart tooltip includes bucket timestamp (UTC) with `avg` and `p95`.
- `FR-AN-QDETAIL-007`: Both charts are filtered by selected query signature + active period.

## 5. Info Section

- `FR-AN-QDETAIL-008`: An `Info` card shows at minimum: `total time`, `avg time`, `p95`, `calls`.
- `FR-AN-QDETAIL-009`: A SQL panel displays the formatted query text for readability.

## 6. Calls Section

- `FR-AN-QDETAIL-010`: Section title shows call count in active filters (example: `1 Call`).
- `FR-AN-QDETAIL-011`: Section right controls include duration segments: `View all`, `>= AVG`, `>= P95`.
- `FR-AN-QDETAIL-012`: Duration filter updates cards, charts, and table.

## 7. Calls Table

- `FR-AN-QDETAIL-013`: Table columns are: `date`, `source`, `location`, `connection`, `duration`.
- `FR-AN-QDETAIL-014`: `source` can include execution-context badge and preview (for example request method/path).
- `FR-AN-QDETAIL-015`: `location` displays file/line when available and supports placeholder values when unavailable.
- `FR-AN-QDETAIL-016`: `connection` can include connection name and connection type badge (for example `WRITE`).
- `FR-AN-QDETAIL-017`: Table rows are sorted by date descending by default.
- `FR-AN-QDETAIL-018`: Table supports pagination for longer call history.

## 8. Navigation and Edge Cases

- `FR-AN-QDETAIL-019`: Back navigation returns to Query list with preserved filters/context.
- `FR-AN-QDETAIL-020`: If selected query signature is missing or invalid, the page falls back to Query list with guidance.


## Technical Specifications

See dedicated technical specification: [query-detail-technical.md](./query-detail-technical.md)
