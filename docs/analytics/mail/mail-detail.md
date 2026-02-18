# Functional Specifications - Mail Detail Analytics

## 1. Purpose

This page analyzes one selected mail class/name in a selected project and environment.

## 2. Scope

Included:

- Mail-focused drilldown from Mail list table.
- Same two-chart header as Mail page, filtered to one selected mail class.
- Message-level table for selected mail class.

Excluded:

- Cross-mail-class comparison in this page.
- Mail resend/mutation actions from analytics UI.

## 3. Inherited Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-REQ-MAIL-001`: two-card chart header pattern reused from Mail page.

## 4. Header and Charts

- `FR-AN-MDETAIL-001`: Breadcrumb starts with `Mail`.
- `FR-AN-MDETAIL-002`: Page title is the selected mail class/name.
- `FR-AN-MDETAIL-003`: Left metric card shows total mails count for selected class in active filters.
- `FR-AN-MDETAIL-004`: Left chart tooltip includes bucket timestamp (UTC), `calls`, and total.
- `FR-AN-MDETAIL-005`: Right metric card shows duration range (`min-max`) and summary metrics (`avg`, `p95`).
- `FR-AN-MDETAIL-006`: Right chart tooltip includes bucket timestamp (UTC) with `avg` and `p95`.
- `FR-AN-MDETAIL-007`: Both charts are filtered by selected mail class + active period.

## 5. Messages Section

- `FR-AN-MDETAIL-008`: Section title shows message count in active filters (example: `2 mails`).
- `FR-AN-MDETAIL-009`: Section right controls include duration segments: `View all`, `>= AVG`, `>= P95`.
- `FR-AN-MDETAIL-010`: Duration filter updates cards, charts, and table.

## 6. Messages Table

- `FR-AN-MDETAIL-011`: Table columns are: `date`, `source`, `mailer`, `subject`, `recipients`, `duration`.
- `FR-AN-MDETAIL-012`: `source` can include execution-context badge and preview (for example request method/path snippet).
- `FR-AN-MDETAIL-013`: `recipients` column displays recipient counters at minimum for `to` and `cc/bcc` where available.
- `FR-AN-MDETAIL-014`: Table rows are sorted by date descending by default.
- `FR-AN-MDETAIL-015`: Table supports pagination for longer message history.
- `FR-AN-MDETAIL-016`: Mail detail table has no row-level drilldown action in MVP scope.

## 7. Navigation and Edge Cases

- `FR-AN-MDETAIL-017`: Back navigation returns to Mail list with preserved filters/context.
- `FR-AN-MDETAIL-018`: If selected mail class is missing or invalid, the page falls back to Mail list with guidance.


## Technical Specifications

See dedicated technical specification: [mail-detail-technical.md](./mail-detail-technical.md)
