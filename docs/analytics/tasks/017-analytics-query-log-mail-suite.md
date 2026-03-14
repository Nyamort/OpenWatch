# Task T-017: Query, Log, and Mail Analytics
- Domain: `analytics`
- Status: `not started`
- Priority: `P0`
- Dependencies: `T-015`

## Description
Implement analytics pages for database queries (grouped by normalized SQL fingerprint), application logs (feed-style, newest-first, no grouping), and outgoing mail (grouped by class/mailer with recipient counters). Each includes list and detail levels.

## How to implement

### Query
1. Implement `BuildQueryIndexData`: normalize SQL (strip literals → sha256 fingerprint), group by fingerprint, compute count/avg/p95/max duration, slowest example. Display duration in adaptive units (µs/ms).
2. Implement `BuildQueryDetailData`: fetch all occurrences for a fingerprint with execution context and timeline.
3. Add search by SQL fragment (against the normalized form).

### Log
1. Implement `BuildLogIndexData`: no grouping — cursor-based feed of log entries ordered by `recorded_at DESC`. Support filters: level (RFC 5424 ordering), search in `message`.
2. Implement `BuildLogDetailData`: single log entry with full `context` and `extra` JSON rendered.
3. Cursor pagination (not offset) to handle high-volume log streams.

### Mail
1. Implement `BuildMailIndexData`: group by `(class, mailer)`, count sent/failed, avg duration. Exclude failed records from duration average.
2. Implement `BuildMailDetailData`: single mail record with recipients (to/cc/bcc), subject, attachments, status.

### Routes and pages
4. Add controllers and routes for each type.
5. Build Inertia pages with the shared table component from T-015; add type-specific columns and empty states.
6. Write feature tests for each action.

## Key files to create or modify
- `app/Actions/Analytics/Query/BuildQueryIndexData.php`
- `app/Actions/Analytics/Query/BuildQueryDetailData.php`
- `app/Actions/Analytics/Log/BuildLogIndexData.php`
- `app/Actions/Analytics/Log/BuildLogDetailData.php`
- `app/Actions/Analytics/Mail/BuildMailIndexData.php`
- `app/Actions/Analytics/Mail/BuildMailDetailData.php`
- `app/Http/Controllers/Analytics/QueryController.php`
- `app/Http/Controllers/Analytics/LogController.php`
- `app/Http/Controllers/Analytics/MailController.php`
- `resources/js/pages/analytics/queries/`
- `resources/js/pages/analytics/logs/`
- `resources/js/pages/analytics/mail/`
- `tests/Feature/Analytics/QueryAnalyticsTest.php`
- `tests/Feature/Analytics/LogAnalyticsTest.php`
- `tests/Feature/Analytics/MailAnalyticsTest.php`

## Acceptance criteria
- [ ] Queries are grouped by normalized SQL fingerprint (literals stripped)
- [ ] Log feed is ordered newest-first by default with cursor-based pagination
- [ ] Log level filter uses RFC 5424 ordering (emergency → debug)
- [ ] Failed mail records are excluded from average duration calculation
- [ ] Mail list groups by class+mailer combination
- [ ] Detail pages for all three types show full contextual data

## Related specs
- [Functional spec](../query/specs.md), [log/specs.md](../log/specs.md), [mail/specs.md](../mail/specs.md)
- [Technical specs](../query/query-technical.md)
