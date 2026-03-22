# Task T-021: Exception and User Analytics
- Domain: `analytics`
- Status: `completed`
- Priority: `P1`
- Dependencies: `T-015`

## Description
Implement exception analytics (grouped by occurrence fingerprint/class+message+file+line, handled/unhandled split, occurrence history) and user analytics (cross-type pivot: users seen across requests, exceptions, and job attempts — authenticated vs guest split).

## How to implement

### Exceptions
1. Implement `BuildExceptionIndexData`: group by `occurrence_group_key` (sha256 of class+message+file+line after normalization). Compute total occurrences, first_seen, last_seen, handled/unhandled count. Sort by last_seen desc.
2. Implement `BuildExceptionDetailData`: resolve the exception group — return representative record (stack trace, PHP/Laravel version), occurrence list (paginated, filterable by environment/period), and related telemetry links (request, job) via `trace_id`.
3. Add search by class name or message fragment.

### Users
1. Implement `BuildUserIndexData`: cross-type projection — FULL OUTER JOIN across `extraction_requests`, `extraction_exceptions`, `extraction_job_attempts` on `user` field. Compute: request count, exception count, job count per user. Split authenticated (non-empty user) vs guest.
2. Implement `BuildUserDetailData`: single user — show their requests, exceptions, and jobs with links to the respective analytics pages preserving period/filter context.
3. User pivot: clicking a user in any analytics page (exceptions, jobs) navigates to user detail with context preserved.

### Routes and pages
4. Add routes, controllers, and Inertia pages.
5. Privacy: apply field redaction policy for user-related sensitive fields per org configuration.
6. Write feature tests: exception grouping, occurrence count, user cross-type aggregation, guest vs authenticated split.

## Key files to create or modify
- `app/Actions/Analytics/Exception/BuildExceptionIndexData.php`
- `app/Actions/Analytics/Exception/BuildExceptionDetailData.php`
- `app/Actions/Analytics/User/BuildUserIndexData.php`
- `app/Actions/Analytics/User/BuildUserDetailData.php`
- `app/Http/Controllers/Analytics/ExceptionController.php`
- `app/Http/Controllers/Analytics/UserAnalyticsController.php`
- `resources/js/pages/analytics/exceptions/`
- `resources/js/pages/analytics/users/`
- `tests/Feature/Analytics/ExceptionAnalyticsTest.php`
- `tests/Feature/Analytics/UserAnalyticsTest.php`

## Acceptance criteria
- [ ] Exceptions are grouped by fingerprint (class+message+file+line normalized)
- [ ] Handled and unhandled exceptions are counted separately
- [ ] Exception detail shows the full stack trace with frame metadata
- [ ] User list correctly splits authenticated (non-empty user field) from guest
- [ ] User detail cross-references requests, exceptions, and job attempts for that user
- [ ] Navigating from exception/job to user detail preserves period/filter context

## Related specs
- [Functional spec](../exception/specs.md), [user/specs.md](../user/specs.md)
- [Technical specs](../exception/exception-technical.md)
