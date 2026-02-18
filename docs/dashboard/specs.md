# Functional Specifications - Dashboard

## 1. Purpose

This document defines functional requirements for the authenticated dashboard home page.

## 2. Scope

Included:

- A global dashboard view in project/environment context.
- Period filtering and metric refresh behavior.
- Activity, Application, and Users dashboard sections.
- Navigation from dashboard cards to detailed analytics pages.

Excluded:

- Per-record analytics internals (covered in `docs/analytics/specs.md`).
- Issue workflow internals (covered in `docs/issues/specs.md`).
- Threshold rule configuration internals (covered in `docs/alerts/specs.md`).

## 3. Actors

- `Organization Owner`: full dashboard access.
- `Organization Admin`: full dashboard access.
- `Organization Developer`: dashboard access in authorized scopes.
- `Organization Viewer`: read-only dashboard access.

## 4. Functional Requirements

## 4.1 Global Layout and Period Filter

- `FR-DB-001`: The authenticated home page exposes a `Dashboard` view in active organization/project/environment context.
- `FR-DB-002`: A period selector is shown in the top-right area with presets: `1h`, `24h`, `7d`, `14d`, `30d`.
- `FR-DB-003`: Default selected period is `24h`.
- `FR-DB-004`: Changing period refreshes all dashboard sections consistently.
- `FR-DB-005`: Selected period is preserved when navigating from dashboard widgets to linked pages.

## 4.2 Activity Section

- `FR-DB-006`: Dashboard includes an `Activity` section with a link to `Requests` analytics.
- `FR-DB-007`: Activity section displays request volume summary with status split (`1/2/3xx`, `4xx`, `5xx`).
- `FR-DB-008`: Activity section displays request duration summary with `avg` and `p95`.
- `FR-DB-009`: Activity charts show tooltips per bucket with timestamp and values.
- `FR-DB-010`: `Requests` link navigates to request analytics with current scope and period.

## 4.3 Application Section

- `FR-DB-011`: Dashboard includes an `Application` section with cards for exceptions, thresholds, and jobs.
- `FR-DB-012`: Exceptions card shows at minimum:
  - total exceptions in selected period,
  - impact hint text,
  - handled vs unhandled split.
- `FR-DB-013`: Exceptions card `View` action navigates to exceptions analytics/issues context with current scope and period.
- `FR-DB-014`: Thresholds card shows setup CTA when no threshold is configured (for example `Setup thresholds` + `Add Threshold` button).
- `FR-DB-015`: `Add Threshold` action navigates to thresholds configuration flow in current scope.
- `FR-DB-016`: Jobs card shows jobs summary with status counters (`failed`, `processed`, `released`) and duration summary (`avg`, `p95`) when data exists.
- `FR-DB-017`: Jobs card supports explicit no-data state (for example zero counters and placeholder durations).
- `FR-DB-018`: `Jobs` section link navigates to jobs analytics with current scope and period.

## 4.4 Users Section

- `FR-DB-019`: Dashboard includes a `Users` section with cards for impacted users, most active users, and user activity charts.
- `FR-DB-020`: Impacted users card shows users impacted by exceptions in selected period with per-user impact counts.
- `FR-DB-021`: Impacted users card `View` action navigates to relevant user/exception analytics context.
- `FR-DB-022`: Most active users card shows top users by request activity in selected period.
- `FR-DB-023`: Most active users card `View` action navigates to user analytics with relevant pre-filters.
- `FR-DB-024`: Users activity card shows at minimum:
  - authenticated users count trend,
  - requests split between `authenticated` and `guest`.
- `FR-DB-025`: `Users` section link navigates to users analytics with current scope and period.

## 4.5 Navigation and Access Behavior

- `FR-DB-026`: All dashboard links preserve active organization/project/environment context.
- `FR-DB-027`: Dashboard widgets are read-only; mutation actions are limited to explicit setup CTAs (for example `Add Threshold`).
- `FR-DB-028`: Users without required permissions do not see restricted actions and receive consistent authorization behavior.

## 4.6 Empty and Loading States

- `FR-DB-029`: Each section has an explicit empty-state when there is no data in selected period.
- `FR-DB-030`: Empty-state copy suggests a relevant next action (for example configure thresholds, open analytics page).
- `FR-DB-031`: Loading state is visible while period changes are being applied.

## 5. MVP Scope

Required for MVP:

- Dashboard page with `Activity`, `Application`, and `Users` sections.
- Period presets (`1h`, `24h`, `7d`, `14d`, `30d`) with default `24h`.
- Requests activity summary and jobs/exception/user summary widgets.
- Threshold setup CTA card.
- Navigation links from widgets to analytics/threshold pages with context preservation.

Can be deferred post-MVP:

- Customizable dashboard layout and widget ordering.
- Saved dashboard views per user.
- Advanced comparative widgets (current period vs previous period).

## 6. Acceptance Checklist

- Each `FR-DB-*` requirement has at least one acceptance test case.
- Period change updates all dashboard widgets consistently.
- Requests, Jobs, and Users links navigate to correct scoped pages.
- Threshold setup CTA is shown when no threshold exists.
- Empty states are visible and actionable when period contains no data.


## Technical Specifications

See dedicated technical specification: [specs-technical.md](./specs-technical.md)

## Related Resources

- **Technical Spec**: [specs-technical.md](./specs-technical.md)
- **Related Specs**: [analytics/specs.md](../analytics/specs.md), [alerts/specs.md](../alerts/specs.md), [issues/specs.md](../issues/specs.md)
- **Implementation Tasks**: [027 - Dashboard Experience](./tasks/027-dashboard-experience.md)
