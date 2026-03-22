# Task T-015: Shared Analytics Shell and Context Engine
- Domain: `analytics`
- Status: `completed`
- Priority: `P0`
- Dependencies: `T-009`, `T-013`, `T-029`

## Description
Build the shared foundation all analytics pages depend on: period presets with custom range validation, org/project/environment context hydration, URL-state sync, shared table primitives (sort/paginate/search/filter), and the standard response shape `{ summary, series, rows, pagination, filters_applied, config }`.

## How to implement
1. Create `PeriodService`: parse period string (`1h`, `24h`, `7d`, `14d`, `30d`, or custom `start..end`). Compute bucket size: `1h→30s`, `24h→15m`, `7d→2h`, `14d→4h`, `30d→6h`, `custom≤300 points`. Validate custom ranges.
2. Create `AnalyticsContextResolver`: resolve and validate org/project/environment from route params, inject as typed DTO into controllers.
3. Define `AnalyticsResponseBuilder`: standard output shape with `summary`, `series`, `rows`, `pagination`, `filters_applied`, `config`.
4. Create base `AnalyticsController` with shared `resolveContext()` and `buildPeriod()` helpers.
5. Build shared Inertia layout for analytics pages: sidebar nav per record type, period selector component (presets + custom range), project/environment context switcher in the header.
6. Build shared table component with: column sort toggle, page size selector, search input, filter pills, empty state.
7. Write feature tests: period parsing for all presets, bucket size computation, invalid period rejected, context resolver rejects wrong-org project.

## Key files to create or modify
- `app/Services/Analytics/PeriodService.php`
- `app/Services/Analytics/AnalyticsContextResolver.php`
- `app/Services/Analytics/AnalyticsResponseBuilder.php`
- `app/Http/Controllers/Analytics/AnalyticsController.php` — base class
- `resources/js/layouts/analytics-layout.tsx`
- `resources/js/components/analytics/period-selector.tsx`
- `resources/js/components/analytics/data-table.tsx`
- `resources/js/components/analytics/empty-state.tsx`
- `tests/Feature/Analytics/PeriodServiceTest.php`
- `tests/Feature/Analytics/AnalyticsContextTest.php`

## Acceptance criteria
- [ ] All period presets (`1h`, `24h`, `7d`, `14d`, `30d`) parse correctly with correct bucket size
- [ ] Custom period range is validated and capped at 300 data points
- [ ] Invalid period string returns a `422` with a descriptive error
- [ ] Context resolver rejects a project that does not belong to the active org
- [ ] Period change in the UI updates all sections on the same page without full reload
- [ ] Shared table supports sort, pagination, and search via URL query params
- [ ] Empty state is shown when no records match the current filters

## Related specs
- [Functional spec](../specs.md) — `FR-AN-001`, `FR-AN-002`, `FR-AN-010` to `FR-AN-026`
- [Technical spec](../specs-technical.md)
