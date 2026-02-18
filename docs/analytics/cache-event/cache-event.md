# Functional Specifications - Cache Analytics

## 1. Purpose

This page analyzes `cache-event` records for a selected project and environment.

## 2. Inherited Base Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-020`: paginated and sortable records list.
- `FR-AN-021`: default sorting by `timestamp` descending.

## 3. Page-Specific Requirements

- `FR-AN-REQ-CACHE-001`: The page uses a two-card chart header.
- `FR-AN-REQ-CACHE-002`: Left card headline metric is total cache events count for active filters.
- `FR-AN-REQ-CACHE-003`: Left card status counters include `deletes`, `hits`, `misses`, and `writes`.
- `FR-AN-REQ-CACHE-004`: Left chart visualizes cache events by time bucket with per-type color legend.
- `FR-AN-REQ-CACHE-004A`: Left chart uses fixed type colors: `deletes` (red), `hits` (gray), `misses` (orange), `writes` (blue).
- `FR-AN-REQ-CACHE-005`: Right card headline metric is total failures count for active filters.
- `FR-AN-REQ-CACHE-006`: Right card includes failure-type counters at minimum for `delete` and `write`.
- `FR-AN-REQ-CACHE-007`: Right chart visualizes failure events by time bucket.
- `FR-AN-REQ-CACHE-008`: Left chart tooltip shows bucket timestamp (UTC), per-type values (`hits`, `misses`, `writes`, `deletes`), and `total`.
- `FR-AN-REQ-CACHE-008A`: Right chart tooltip shows bucket timestamp (UTC), per-type values (`write`, `delete`), and `total`.
- `FR-AN-REQ-CACHE-009`: Section header below charts displays total unique cache keys count for active filters (example: `14 keys`).
- `FR-AN-REQ-CACHE-010`: The table is keyed by cache key identity and aggregates metrics per key.
- `FR-AN-REQ-CACHE-011`: Table columns are: `key`, `hit %`, `hits`, `misses`, `writes`, `deletes`, `failures`, `total`.
- `FR-AN-REQ-CACHE-012`: The table supports sorting on aggregate columns (`hit %`, `hits`, `misses`, `writes`, `deletes`, `failures`, `total`).
- `FR-AN-REQ-CACHE-013`: Default table ordering is by latest cache-event timestamp descending.
- `FR-AN-REQ-CACHE-014`: Key rows can display a cache-key icon marker in the first column.
- `FR-AN-REQ-CACHE-015`: Empty-state behavior is explicit when no keys match active filters.
- `FR-AN-REQ-CACHE-016`: Cache keys list does not provide a row drilldown detail page; no per-row action is required in this MVP scope.


## Technical Specifications

See dedicated technical specification: [cache-event-technical.md](./cache-event-technical.md)
