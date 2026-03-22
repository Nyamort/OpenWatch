# Task T-018: Cache-Event Analytics
- Domain: `analytics`
- Status: `completed`
- Priority: `P1`
- Dependencies: `T-015`

## Description
Implement the aggregated cache-event dashboard: group by `(store, key, type)`, compute hit rate, miss rate, and operation counts. List-only — no row-level detail drilldown. Hit rate color mapping is returned from the backend.

## How to implement
1. Implement `BuildCacheEventIndexData` action: aggregate from `extraction_cache_events` grouped by `(store, key, type)`. Compute:
   - `hit_rate_pct = COUNT(type='hit') * 100.0 / COUNT(*)`
   - Operation counts per type (hit, miss, write, forget, flush)
   - Return color mapping: `hit_rate >= 80` → `green`, `>= 50` → `yellow`, `< 50` → `red`
2. Add store filter (populated from distinct store values in the period).
3. Add key search (prefix match or contains).
4. Default sort: by total operations desc. Explicit secondary sort by key asc.
5. Add `GET /analytics/{org}/{project}/{env}/cache-events` route.
6. Build Inertia page with the shared table, hit-rate badge using backend color, store selector, no row click action.
7. Write feature tests: aggregation is correct, hit rate computation, color mapping values, store filter works.

## Key files to create or modify
- `app/Actions/Analytics/CacheEvent/BuildCacheEventIndexData.php`
- `app/Http/Controllers/Analytics/CacheEventController.php`
- `resources/js/pages/analytics/cache-events/index.tsx`
- `resources/js/components/analytics/hit-rate-badge.tsx`
- `tests/Feature/Analytics/CacheEventAnalyticsTest.php`

## Acceptance criteria
- [ ] Cache events are grouped by store, key, and type
- [ ] Hit rate percentage is computed correctly from hit vs total counts
- [ ] Color mapping (green/yellow/red) is returned from the backend, not computed on the frontend
- [ ] Store filter updates the list to show only events for the selected store
- [ ] No row-level detail action is available (list-only view)
- [ ] Default sort is by total operations descending

## Related specs
- [Functional spec](../cache-event/specs.md)
- [Technical spec](../cache-event/cache-event-technical.md)
