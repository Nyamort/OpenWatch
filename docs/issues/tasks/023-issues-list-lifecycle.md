# Task T-023: Issue List and Lifecycle Actions
- Domain: `issues`
- Status: `completed`
- Priority: `P0`
- Dependencies: `T-022`

## Description
Implement the issue list experience: tabbed view by type (exceptions/performance/other) and status (open/resolved/ignored), filters (search, assignee, priority, ownership), bulk lifecycle transitions (open → resolved → ignored → reopened), and role-based authorization on bulk write actions.

## How to implement
1. Implement `BuildIssueListData` action: filter by `status`, `type`, `assignee_id`, search term; paginate with configurable page size; sort by `last_seen_at` desc (default), `occurrence_count`, `first_seen_at`.
2. Implement `UpdateIssueStatus` action: transition issue status with validation (open→resolved, open→ignored, resolved→open, ignored→open). Emit `IssueStatusChanged` event. Create `issue_activities` record.
3. Implement bulk `BulkUpdateIssues` action: accept up to 100 issue IDs per request, apply status/assignee/priority update. Use transaction; partial success is acceptable (return per-issue results). Only issues in the caller's org/project scope are processed.
4. Implement `AssignIssue` action: assign to a member of the org; validate membership.
5. Build Inertia page: tab bar, filter sidebar, issue table with checkbox selection, bulk action toolbar (appears when rows selected), pagination.
6. Disable bulk action buttons for roles without write permission (Viewer).
7. Write feature tests: list filtering, status transitions, bulk update with mixed results, Viewer cannot bulk update, out-of-scope issue IDs are silently ignored.

## Key files to create or modify
- `app/Actions/Issues/BuildIssueListData.php`
- `app/Actions/Issues/UpdateIssueStatus.php`
- `app/Actions/Issues/BulkUpdateIssues.php`
- `app/Actions/Issues/AssignIssue.php`
- `app/Events/IssueStatusChanged.php`
- `database/migrations/xxxx_create_issue_activities_table.php`
- `app/Models/IssueActivity.php`
- `app/Http/Controllers/Issues/IssueController.php`
- `resources/js/pages/issues/index.tsx`
- `resources/js/components/issues/bulk-action-toolbar.tsx`
- `tests/Feature/Issues/IssueListLifecycleTest.php`

## Acceptance criteria
- [ ] Issue list is filterable by status, type, assignee, and text search
- [ ] Default sort is by `last_seen_at` descending
- [ ] Status transitions follow the allowed state machine (open↔resolved, open↔ignored)
- [ ] Bulk update processes up to 100 issues; IDs outside the caller's scope are silently skipped
- [ ] Viewer role cannot execute bulk status updates
- [ ] Every status change produces an `IssueActivity` record with actor and timestamp
- [ ] Assigning an issue to a non-member of the org is rejected

## Related specs
- [Functional spec](../specs.md) — `FR-ISS-009` to `FR-ISS-026`
- [Technical spec](../specs-technical.md)
