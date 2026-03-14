# Task T-020: Outgoing Request, Notification, and Scheduled-Task Analytics
- Domain: `analytics`
- Status: `not started`
- Priority: `P1`
- Dependencies: `T-015`

## Description
Implement analytics for three related event types: outgoing HTTP requests (grouped by host/domain with status class counters), notifications (grouped by class+channel with failed rate), and scheduled tasks (task identity by command+schedule_expression, three-status model: processed/skipped/failed).

## How to implement

### Outgoing Requests
1. Implement `BuildOutgoingRequestIndexData`: extract `host_domain` from URL host, derive `status_class` (2xx/3xx/4xx/5xx/error). Group by `host_domain`, compute count by status class, avg/p95 duration.
2. Implement `BuildOutgoingRequestHostData`: drilldown for a single host — individual request list with URL, method, status, duration.

### Notifications
1. Implement `BuildNotificationIndexData`: group by `(class, channel)`. Compute sent/failed counts, failed rate. Channel filter from a whitelist (database, mail, broadcast, slack, vonage, nexmo, other). Channels outside whitelist → aggregated as "other".
2. Implement `BuildNotificationDetailData`: single notification record with recipient and metadata.

### Scheduled Tasks
1. Implement `BuildScheduledTaskIndexData`: task identity = `(name/command, cron)`. Three-status model: `processed` (ran and completed), `skipped` (without_overlapping guard fired), `failed` (exception during run). Two rows for the same command on different schedules.
2. Implement `BuildScheduledTaskRunData`: individual run detail with related logs, queries, exceptions, duration breakdown.

### Routes and pages
4. Add routes, controllers, and Inertia pages for all three types.
5. Write feature tests for each action.

## Key files to create or modify
- `app/Actions/Analytics/OutgoingRequest/BuildOutgoingRequestIndexData.php`
- `app/Actions/Analytics/OutgoingRequest/BuildOutgoingRequestHostData.php`
- `app/Actions/Analytics/Notification/BuildNotificationIndexData.php`
- `app/Actions/Analytics/Notification/BuildNotificationDetailData.php`
- `app/Actions/Analytics/ScheduledTask/BuildScheduledTaskIndexData.php`
- `app/Actions/Analytics/ScheduledTask/BuildScheduledTaskRunData.php`
- `app/Http/Controllers/Analytics/OutgoingRequestController.php`
- `app/Http/Controllers/Analytics/NotificationController.php`
- `app/Http/Controllers/Analytics/ScheduledTaskController.php`
- `resources/js/pages/analytics/outgoing-requests/`
- `resources/js/pages/analytics/notifications/`
- `resources/js/pages/analytics/scheduled-tasks/`
- `tests/Feature/Analytics/OutgoingRequestAnalyticsTest.php`
- `tests/Feature/Analytics/NotificationAnalyticsTest.php`
- `tests/Feature/Analytics/ScheduledTaskAnalyticsTest.php`

## Acceptance criteria
- [ ] Outgoing requests are grouped by extracted host domain
- [ ] Status classes (2xx/3xx/4xx/5xx/error) are correctly derived from HTTP status code
- [ ] Notifications outside the channel whitelist are grouped as "other"
- [ ] Scheduled task identity is by (command + cron expression) — same command on different schedules = two rows
- [ ] Skipped scheduled tasks (due to overlapping guard) show as `skipped` status
- [ ] Scheduled task run detail includes related logs, queries, and exceptions

## Related specs
- [Functional specs](../outgoing-request/specs.md), [notification/specs.md](../notification/specs.md), [scheduled-task/specs.md](../scheduled-task/specs.md)
- [Technical specs](../outgoing-request/outgoing-request-technical.md)
