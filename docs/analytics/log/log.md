# Functional Specifications - Log Analytics

## 1. Purpose

This page analyzes `log` records for a selected project and environment.

## 2. Inherited Base Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-020`: paginated and sortable records list.
- `FR-AN-021`: default sorting by `timestamp` descending.
- `FR-AN-023`: row-to-detail drill-through.

## 3. Page-Specific Requirements

- `FR-AN-REQ-LOG-001`: The page uses a chronological log list layout instead of chart cards.
- `FR-AN-REQ-LOG-002`: Top controls include `Search logs`, a `Level` filter (default `All`), a user selector (default `All Users`), and period presets.
- `FR-AN-REQ-LOG-003`: List entries are ordered newest first by timestamp.
- `FR-AN-REQ-LOG-004`: Each entry row includes at minimum: `timestamp` (UTC), execution source badge (example: `REQUEST`), optional request preview (example: `GET /`), log level badge, and message text.
- `FR-AN-REQ-LOG-005`: Level badges use severity styling (example: `ERROR` in error color, `DEBUG` in neutral color).
- `FR-AN-REQ-LOG-006`: The list supports filtering by free-text query, selected level, selected user, and period.
- `FR-AN-REQ-LOG-007`: Each row provides an action affordance to open [`log-detail`](./log-detail.md) context (full message/context/extra payload).
- `FR-AN-REQ-LOG-008`: The view includes a freshness marker (`Latest as of <timestamp UTC>`) indicating the newest loaded event.
- `FR-AN-REQ-LOG-009`: When no entries match active filters, an explicit empty-state message is shown.
- `FR-AN-REQ-LOG-010`: The log list is paginated with page-based navigation and keeps descending chronological order by default.


## Technical Specifications

See dedicated technical specification: [log-technical.md](./log-technical.md)
