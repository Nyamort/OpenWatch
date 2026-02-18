# Functional Specifications - Analytics

## 1. Purpose

This document defines the functional requirements for analytics pages used to visualize and inspect ingested records by type in a Laravel Nightwatch-like platform.

## 2. Scope

Included:

- Analytics pages for all supported record types.
- Route-scoped analytics pages for selected subsets (for example request route drilldowns).
- Shared layout behavior (type label, time-range controls, summary + list).
- Type-specific summaries and filtered list views.
- Contextual drill-down from one related record to another (for example across trace/group lineage).
- Permissions-aware filtering by organization/project/environment context.

Excluded:

- Ingestion transport and authentication internals.
- Indexing architecture and database optimization decisions.
- Alerting policy and incident orchestration (out of analytics UI scope unless explicitly linked).

## 3. Actors

- `Organization Owner`: full analytics access in organization scope.
- `Organization Admin`: full analytics access in organization scope.
- `Organization Developer`: analytics access according to role permissions.
- `Organization Viewer`: read-only access to analytics pages in scoped resources.

## 4. Analytics Entry and Context

- `FR-AN-001`: Analytics is reachable from project/environment navigation context.
- `FR-AN-002`: Analytics pages require an active organization context and valid project/environment authorization.
- `FR-AN-003`: Unauthorized users receive a clear access denied flow.
- `FR-AN-004`: A single analytics entry point is available per project that resolves environment-specific pages.

## 5. Shared Layout and Interaction

- `FR-AN-010`: Each analytics page displays the selected record type label in the upper-left area.
- `FR-AN-011`: Each analytics page shows period controls in the upper-right area with presets: `1h`, `24h`, `7d`, `14d`, `30d`, `custom`.
- `FR-AN-012`: On first load, analytics defaults to `24h`.
- `FR-AN-013`: `custom` requires valid start and end boundaries and validates the window before rendering.
- `FR-AN-014`: Changing period updates summary metrics and record lists immediately.
- `FR-AN-015`: Selected period is reflected in shareable URL query state.
- `FR-AN-016`: Empty states are explicit and show recommended actions when no records exist for the selected period.
- `FR-AN-017`: Each page includes:
  - total count for the selected period,
  - summary metrics for the selected period,
  - and a list of matching records.

## 6. Universal List Behavior

- `FR-AN-020`: All analytics lists are paginated and sortable.
- `FR-AN-021`: Default sort is descending by `timestamp`.
- `FR-AN-022`: For each row, at minimum the following fields are displayed when present in the source record:
  - `timestamp`,
  - record-specific key identifiers,
  - key performance or outcome metric,
  - and status/failure indicator.
- `FR-AN-023`: For record types that define a drilldown detail page, each row opens a detail view including raw payload and metadata used to inspect a single event.
- `FR-AN-024`: The record list supports free-text search across a configurable display subset.
- `FR-AN-025`: The list can be filtered by severity/failure and outcome states when the record type includes those concepts.
- `FR-AN-026`: Records can be exported for the current selection when export is enabled by product policy.

## 7. Record Type Pages

### 7.1 Shared Record Types

- `FR-AN-031`: The analytics module provides dedicated pages for the following record types (with one exception noted in `FR-AN-033`):
  - `request`
  - `query`
  - `cache-event`
  - `command`
  - `log`
  - `notification`
  - `mail`
  - `jobs`
  - `scheduled-task`
  - `outgoing-request`
  - `exception`
  - `user`
- `FR-AN-032`: Missing mandatory fields in a record are still representable in analytics as empty-value fields without breaking the list or page rendering.
- `FR-AN-033`: Only `jobs` merges two record types: `queued-job` and `job-attempt` are surfaced in a single analytics page named `jobs`.
 
### 7.2 Type Spec Files

- `request`: [`request`](./request/request.md)
- `query`: [`query`](./query/query.md)
- `cache-event`: [`cache-event`](./cache-event/cache-event.md)
- `command`: [`command`](./command/command.md)
- `log`: [`log`](./log/log.md)
- `notification`: [`notification`](./notification/notification.md)
- `mail`: [`mail`](./mail/mail.md)
- `jobs` (`queued-job` + `job-attempt`): [`jobs`](./jobs/jobs.md)
- `scheduled-task`: [`scheduled-task`](./scheduled-task/scheduled-task.md)
- `outgoing-request`: [`outgoing-request`](./outgoing-request/outgoing-request.md)
- `exception`: [`exception`](./exception/exception.md)
- `user`: [`user`](./user/user.md)

### 7.3 Additional Request Pages

- Route-scoped request page: [`request-route`](./request/request-route.md)
- Request detail page: [`request-detail`](./request/request-detail.md)

### 7.4 Additional Jobs Pages

- Job detail page: [`job-detail`](./jobs/job-detail.md)
- Attempt detail page: [`attempt-detail`](./jobs/attempt-detail.md)

### 7.5 Additional Mail Pages

- Mail detail page: [`mail-detail`](./mail/mail-detail.md)

### 7.6 Additional Query Pages

- Query detail page: [`query-detail`](./query/query-detail.md)

### 7.7 Additional Log Pages

- Log detail page: [`log-detail`](./log/log-detail.md)

### 7.8 Additional Command Pages

- Command detail page: [`command-detail`](./command/command-detail.md)
- Command run detail page: [`command-run-detail`](./command/command-run-detail.md)

### 7.9 Additional Outgoing Request Pages

- Outgoing request detail page: [`outgoing-request-detail`](./outgoing-request/outgoing-request-detail.md)

### 7.10 Additional Notification Pages

- Notification detail page: [`notification-detail`](./notification/notification-detail.md)

### 7.11 Additional Scheduled Task Pages

- Scheduled task detail page: [`scheduled-task-detail`](./scheduled-task/scheduled-task-detail.md)
- Scheduled task run detail page: [`scheduled-task-run-detail`](./scheduled-task/scheduled-task-run-detail.md)

### 7.12 Additional Exception Pages

- Exception detail page: [`exception-detail`](./exception/exception-detail.md)

### 7.13 Additional User Pages

- User detail page: [`user-detail`](./user/user-detail.md)

## 8. Cross-Type Correlation

- `FR-AN-110`: Analytics supports drill-through from a parent record to related records when correlation fields are present (`trace_id`, `_group`, `execution_id`).
- `FR-AN-111`: When correlation is available, users can pivot from request to query/log/exception/job pages in the same period context.
- `FR-AN-112`: Correlation drill-through preserves current period and project/environment context.

## 9. Audit and Governance

- `FR-AN-120`: Analytics access is read-logged (who viewed which type and period) for auditability.
- `FR-AN-121`: Export actions are logged when enabled.
- `FR-AN-122`: Analytics actions respect the same tenant isolation and permission rules as other project resources.

## 10. Minimum Viable Analytics

Required for MVP:

- Dedicated page for each required record type listed in `FR-AN-031`.
- Route-level request page (`./request/request-route.md`) and request detail page (`./request/request-detail.md`).
- Job detail page (`./jobs/job-detail.md`) for drilldown from Jobs list.
- Attempt detail page (`./jobs/attempt-detail.md`) for drilldown from Job Detail attempts table.
- Mail detail page (`./mail/mail-detail.md`) for drilldown from Mail list.
- Query detail page (`./query/query-detail.md`) for drilldown from Query list.
- Log detail page (`./log/log-detail.md`) for drilldown from Logs feed.
- Command detail page (`./command/command-detail.md`) for drilldown from Commands list.
- Command run detail page (`./command/command-run-detail.md`) for drilldown from Command Detail table.
- Outgoing request detail page (`./outgoing-request/outgoing-request-detail.md`) for drilldown from Outgoing Requests list.
- Notification detail page (`./notification/notification-detail.md`) for drilldown from Notifications list.
- Scheduled task detail page (`./scheduled-task/scheduled-task-detail.md`) for drilldown from Scheduled Tasks list.
- Scheduled task run detail page (`./scheduled-task/scheduled-task-run-detail.md`) for drilldown from Scheduled Task Detail table.
- Exception detail page (`./exception/exception-detail.md`) for drilldown from Exceptions list.
- User detail page (`./user/user-detail.md`) for drilldown from Users list.
- Shared layout with top-left type title and top-right period controls.
- Presets (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`) and default `24h`.
- Summary metrics + paginated sortable list for each page.
- Request, query, exception, and user pages with their specific summaries.

Can be deferred post-MVP:

- Advanced cross-type pivoting.
- Export capabilities.
- Advanced comparative views and custom dashboards.

## 11. Acceptance Checklist

- MVP-critical `FR-AN-*` requirements (section 10 scope) have at least one acceptance scenario, documented in specs and/or linked test cases.
- Analytics navigation is only available in authorized context.
- A type page loads with `24h` default when no period is provided.
- Custom period validation blocks invalid windows and displays guidance.
- Empty-state behavior is visible and actionable when no data exists.


## Technical Specifications

See dedicated technical specification: [specs-technical.md](./specs-technical.md)

## Related Resources

- **Technical Spec**: [specs-technical.md](./specs-technical.md)
- **Related Specs**: [projects/specs.md](../projects/specs.md), [issues/specs.md](../issues/specs.md), [alerts/specs.md](../alerts/specs.md), [dashboard/specs.md](../dashboard/specs.md)
- **Implementation Tasks**:
  - [015 - Analytics Shared Shell](./tasks/015-analytics-shared-shell.md)
  - [016 - Analytics Request Suite](./tasks/016-analytics-request-suite.md)
  - [017 - Analytics Query/Log/Mail Suite](./tasks/017-analytics-query-log-mail-suite.md)
  - [018 - Analytics Cache Event](./tasks/018-analytics-cache-event.md)
  - [019 - Analytics Command/Job Suite](./tasks/019-analytics-command-job-suite.md)
  - [020 - Analytics Outgoing/Notification/Task](./tasks/020-analytics-outgoing-notification-task.md)
  - [021 - Analytics Exception/User Suite](./tasks/021-analytics-exception-user-suite.md)
