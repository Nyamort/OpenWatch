# Functional Specifications - Notification Detail Analytics

## 1. Purpose

This page analyzes one selected notification class/name in a selected project and environment.

## 2. Scope

Included:

- Notification-focused drilldown from Notification list table.
- Same two-card chart header as Notification page, filtered to one selected class.
- Delivery-level table for selected notification class.

Excluded:

- Cross-notification-class comparison in this page.
- Notification resend/mutation actions from analytics UI.

## 3. Inherited Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-REQ-NOTIF-001`: two-card chart header pattern reused from Notification page.

## 4. Header and Charts

- `FR-AN-NDETAIL-001`: Breadcrumb starts with `Notifications`.
- `FR-AN-NDETAIL-002`: Page title is the selected notification class/name.
- `FR-AN-NDETAIL-003`: Left metric card shows notifications count for selected class in active filters.
- `FR-AN-NDETAIL-004`: Left chart tooltip includes bucket timestamp (UTC), `calls`, and total.
- `FR-AN-NDETAIL-005`: Right metric card shows duration summary and `avg`/`p95`.
- `FR-AN-NDETAIL-006`: Right chart tooltip includes bucket timestamp (UTC), `avg`, and `p95`.
- `FR-AN-NDETAIL-007`: Both charts are filtered by selected notification class + active period.

## 5. Deliveries Section

- `FR-AN-NDETAIL-008`: Section title shows delivery count in active filters (example: `1 Notification`).
- `FR-AN-NDETAIL-009`: Section right controls include duration segments: `View all`, `>= AVG`, `>= P95`.
- `FR-AN-NDETAIL-010`: Duration filter updates cards, charts, and table.

## 6. Deliveries Table

- `FR-AN-NDETAIL-011`: Table columns are: `date`, `source`, `channel`, `duration`.
- `FR-AN-NDETAIL-012`: `source` can include execution-context badge and preview (for example request method/path).
- `FR-AN-NDETAIL-013`: Table rows are sorted by date descending by default.
- `FR-AN-NDETAIL-014`: Table supports pagination for longer delivery history.

## 7. Navigation and Edge Cases

- `FR-AN-NDETAIL-015`: Back navigation returns to Notification list with preserved filters/context.
- `FR-AN-NDETAIL-016`: If selected notification class is missing or invalid, the page falls back to Notification list with guidance.


## Technical Specifications

See dedicated technical specification: [notification-detail-technical.md](./notification-detail-technical.md)
