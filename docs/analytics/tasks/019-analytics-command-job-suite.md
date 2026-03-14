# Task T-019: Command and Jobs Analytics Suite
- Domain: `analytics`
- Status: `not started`
- Priority: `P0`
- Dependencies: `T-015`

## Description
Implement command analytics (list by command name with success/failed/pending counters, run detail) and jobs analytics (merged `queued-job` + `job-attempt` view grouped by name, with attempt-level detail). Status for commands: `success` (exit_code=0), `failed` (exit_code>0), `pending` (exit_code IS NULL).

## How to implement

### Commands
1. Implement `BuildCommandIndexData`: group `extraction_commands` by `(class, name)`, compute count by status (success/failed/pending), avg duration. Exclude pending from duration avg.
2. Implement `BuildCommandDetailData`: single command run with related logs, queries, exceptions. `command_string` (raw argv) only shown in detail, never in list.
3. Add run-level list page for a given command showing individual executions.

### Jobs
1. Implement `BuildJobsIndexData`: union `extraction_queued_jobs` + `extraction_job_attempts` — group by `name` (job class), compute status counters (queued/processing/completed/failed/retrying).
2. Implement `BuildJobDetailData`: single queued-job with all its attempt records ordered by attempt number.
3. Implement `BuildAttemptDetailData`: single job attempt with related logs, queries, exceptions.

### Routes and pages
4. Add routes and controllers for commands and jobs.
5. Build Inertia pages: command list → command runs → run detail; jobs list → job detail → attempt detail.
6. Write feature tests for each action.

## Key files to create or modify
- `app/Actions/Analytics/Command/BuildCommandIndexData.php`
- `app/Actions/Analytics/Command/BuildCommandDetailData.php`
- `app/Actions/Analytics/Jobs/BuildJobsIndexData.php`
- `app/Actions/Analytics/Jobs/BuildJobDetailData.php`
- `app/Actions/Analytics/Jobs/BuildAttemptDetailData.php`
- `app/Http/Controllers/Analytics/CommandController.php`
- `app/Http/Controllers/Analytics/JobsController.php`
- `resources/js/pages/analytics/commands/`
- `resources/js/pages/analytics/jobs/`
- `tests/Feature/Analytics/CommandAnalyticsTest.php`
- `tests/Feature/Analytics/JobsAnalyticsTest.php`

## Acceptance criteria
- [ ] Commands are grouped by name with success/failed/pending counters
- [ ] Pending status is shown for commands where `exit_code IS NULL`
- [ ] `command_string` is only visible in the detail page, not the list
- [ ] Jobs list merges `queued-job` and `job-attempt` records into a unified view
- [ ] Job detail shows all attempts ordered by attempt number
- [ ] Attempt detail includes related logs, queries, and exceptions from the same execution context

## Related specs
- [Functional spec](../command/specs.md), [jobs/specs.md](../jobs/specs.md)
- [Technical specs](../command/command-technical.md)
