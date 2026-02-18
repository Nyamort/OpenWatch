# Functional Specifications - Notification Analytics

## 1. Purpose

This page analyzes `notification` records for a selected project and environment.

## 2. Inherited Base Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-020`: paginated and sortable records list.
- `FR-AN-021`: default sorting by `timestamp` descending.
- `FR-AN-023`: row-to-detail drill-through.

## 3. Page-Specific Requirements

- `FR-AN-REQ-NOTIF-001`: The page uses a two-card chart header.
- `FR-AN-REQ-NOTIF-002`: Left card headline metric is total notifications count for active filters.
- `FR-AN-REQ-NOTIF-003`: Left chart visualizes notification calls by time bucket.
- `FR-AN-REQ-NOTIF-004`: Left chart tooltip shows bucket timestamp (UTC), `calls`, and bucket total.
- `FR-AN-REQ-NOTIF-005`: Right card headline metric shows duration summary for active filters.
- `FR-AN-REQ-NOTIF-006`: Right card summary shows `avg` and `p95` duration values.
- `FR-AN-REQ-NOTIF-007`: Right chart tooltip includes bucket timestamp (UTC) with `avg` and `p95`.
- `FR-AN-REQ-NOTIF-008`: Section title below charts displays total unique notification classes in active filters (example: `1 Notification`).
- `FR-AN-REQ-NOTIF-009`: A `Search notifications` input filters rows by notification class/name.
- `FR-AN-REQ-NOTIF-010`: The table is aggregated by notification class/name.
- `FR-AN-REQ-NOTIF-011`: Table columns are: `notification`, `count`, `avg`, `p95`, `action`.
- `FR-AN-REQ-NOTIF-012`: Table supports sorting for `count`, `avg`, and `p95`.
- `FR-AN-REQ-NOTIF-013`: Action column opens [`notification-detail`](./notification-detail.md) while preserving active filters.
- `FR-AN-REQ-NOTIF-014`: Empty-state behavior is explicit when no notification records match active filters or search.


## Technical Specifications

See dedicated technical specification: [notification-technical.md](./notification-technical.md)
