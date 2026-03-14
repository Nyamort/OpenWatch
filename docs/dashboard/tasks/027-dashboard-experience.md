# Task T-027: Dashboard Core Experience
- Domain: `dashboard`
- Status: `not started`
- Priority: `P1`
- Dependencies: `T-015`, `T-022`, `T-025`

## Description
Build the authenticated dashboard home: period-aware metric cards (requests, exceptions, jobs, users), active alert summary, recent issues section, and contextual navigation links into analytics pages. All sections load efficiently using Inertia v2 deferred props.

## How to implement
1. Implement `BuildDashboardData` action: compose summary data from multiple sources in parallel using `concurrent()` helper or separate deferred Inertia props:
   - **Requests**: total count, error rate, p95 duration for the period
   - **Exceptions**: total count, unhandled count, top exception class
   - **Jobs**: total queued, failed count, failure rate
   - **Users**: total unique authenticated users, guest ratio
2. Implement `BuildActiveAlertsSummary`: list triggered alerts for the current org/project scope, count by severity.
3. Implement `BuildRecentIssuesSummary`: last 5 open issues ordered by `last_seen_at` desc.
4. Cache dashboard summary data in Redis keyed by `dashboard:{org_id}:{project_id}:{env_id}:{period}` with short TTL (30s for `1h` period, up to 5min for `30d`).
5. Build Inertia page (`resources/js/pages/dashboard.tsx`): period selector in header, 4 metric cards, alerts banner, recent issues list, navigation links to analytics pages preserving period+context.
6. Use Inertia v2 deferred props (`defer`) for the three heavy sections (metric cards, alerts, recent issues) so the page shell renders immediately.
7. Add skeleton loading states for each deferred section.
8. Write feature tests: dashboard data is org/project scoped, cache is used on warm requests, deferred props resolve with correct data.

## Key files to create or modify
- `app/Actions/Dashboard/BuildDashboardData.php`
- `app/Actions/Dashboard/BuildActiveAlertsSummary.php`
- `app/Actions/Dashboard/BuildRecentIssuesSummary.php`
- `app/Http/Controllers/DashboardController.php`
- `resources/js/pages/dashboard.tsx` — extend existing shell
- `resources/js/components/dashboard/metric-card.tsx`
- `resources/js/components/dashboard/alerts-banner.tsx`
- `resources/js/components/dashboard/recent-issues.tsx`
- `resources/js/components/dashboard/section-skeleton.tsx`
- `tests/Feature/DashboardTest.php`

## Acceptance criteria
- [ ] Dashboard renders the page shell immediately; metric sections load via deferred props
- [ ] Each metric card shows the correct value for the selected period
- [ ] Period change updates all sections consistently without full page reload
- [ ] Active alerts are surfaced with count and link to the alerts page
- [ ] Recent issues show the last 5 open issues with a link to the full issues list
- [ ] Navigation links from dashboard to analytics pages preserve the current period and context
- [ ] A user with no projects or data sees appropriate empty states per section
- [ ] Cached dashboard data is served within SLA (< 50ms warm, < 300ms cold)

## Related specs
- [Functional spec](../specs.md) — `FR-DB-001` to `FR-DB-031`
- [Technical spec](../specs-technical.md)
