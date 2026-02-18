# Task T-023: Issue List and Lifecycle Actions
- Domain: `issues`
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement issue list experience with tabs, filters, bulk actions, and lifecycle transitions.

## How to execute
1. Build tabs/counters for exceptions/performance/open/resolved/ignored.
2. Add list filters: search, status, assignee, ownership.
3. Implement bulk update for status/assignee/priority with authorization checks.
4. Implement open/resolved/ignored transitions and reopen path.

## Architecture implications
- **Context**: issues query/read service with filter builder.
- **Data**: indexes on status/assigned_at/updated_at for fast filters.
- **Batch operations**: bulk update transaction boundaries for consistency.
- **UX**: disable unauthorized bulk actions.

## Acceptance checkpoints
- bulk actions only apply to selected rows and allowed roles.
- ownership filters work correctly.

## Done criteria
- `FR-ISS-009` to `FR-ISS-026` implemented.
