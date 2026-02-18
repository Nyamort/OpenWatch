# Functional Specifications - Outgoing Request Detail Analytics

## 1. Purpose

This page analyzes one selected host/domain for outgoing requests in a selected project and environment.

## 2. Scope

Included:

- Host-focused drilldown from Outgoing Requests list table.
- Same two-card chart header as Outgoing Requests page, filtered to one selected host.
- Request-level table for selected host/domain.

Excluded:

- Cross-host comparison in this page.
- Outgoing request mutation/replay actions from analytics UI.

## 3. Inherited Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-REQ-OUT-REQ-001`: two-card chart header pattern reused from Outgoing Requests page.

## 4. Header and Charts

- `FR-AN-OUTDETAIL-001`: Breadcrumb starts with `Outgoing Requests`.
- `FR-AN-OUTDETAIL-002`: Page title is selected host/domain label (example: `Unknown Host`).
- `FR-AN-OUTDETAIL-003`: Left card shows requests count and status counters (`1/2/3xx`, `4xx`, `5xx`) for selected host.
- `FR-AN-OUTDETAIL-004`: Left chart tooltip includes bucket timestamp (UTC), status-class counts, and total.
- `FR-AN-OUTDETAIL-005`: Right card shows duration summary (`min-max`, `avg`, `p95`) for selected host.
- `FR-AN-OUTDETAIL-006`: Right chart tooltip includes bucket timestamp (UTC), `avg`, and `p95`.
- `FR-AN-OUTDETAIL-007`: Both charts are filtered by selected host + active period.

## 5. Requests Section

- `FR-AN-OUTDETAIL-008`: Section title shows request count in active filters (example: `1 Request`).
- `FR-AN-OUTDETAIL-009`: Section right controls include two segmented filters:
  - duration filter: `View all`, `>= AVG`, `>= P95`
  - status filter: `View all`, `1/2/3xx`, `4xx`, `5xx`.
- `FR-AN-OUTDETAIL-010`: Status segments can show count badges for active filter context.
- `FR-AN-OUTDETAIL-011`: Duration/status filter changes update cards, charts, and request table.

## 6. Requests Table

- `FR-AN-OUTDETAIL-012`: Table columns are: `date`, `source`, `method`, `status`, `url`, `duration`.
- `FR-AN-OUTDETAIL-013`: `source` can include execution-context badge and preview (example request method/path).
- `FR-AN-OUTDETAIL-014`: `status` value is styled by status class (`2xx`, `4xx`, `5xx`).
- `FR-AN-OUTDETAIL-015`: Rows are sorted by date descending by default.
- `FR-AN-OUTDETAIL-016`: Table supports pagination for longer request history.

## 7. Navigation and Edge Cases

- `FR-AN-OUTDETAIL-017`: Back navigation returns to Outgoing Requests list with preserved filters/context.
- `FR-AN-OUTDETAIL-018`: If selected host/domain is missing or invalid, the page falls back to Outgoing Requests list with guidance.


## Technical Specifications

See dedicated technical specification: [outgoing-request-detail-technical.md](./outgoing-request-detail-technical.md)
