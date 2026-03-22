# Task T-024: Issue Detail, Occurrences, Activity, and Comments
- Domain: `issues`
- Status: `completed`
- Priority: `P0`
- Dependencies: `T-023`

## Description
Implement the issue detail page: manage panel (status/assignee/priority quick actions), source snapshot renderer (adapts to issue type), paginated occurrence list (filterable by environment/period), chronological activity timeline, and Markdown comment compose/view with read-only mode for Viewers.

## How to implement
1. Implement `BuildIssueDetailData` action: fetch issue + source snapshot + manage panel fields + latest 5 occurrences (full list paginated separately) + activity timeline + comments.
2. Implement `BuildIssueOccurrencesData` action: paginated list of occurrences for the issue's fingerprint within the selected environment/period, linked to originating telemetry records.
3. Create `issue_comments` migration: `id`, `issue_id`, `author_id`, `body` (Markdown), `edited_at`, timestamps.
4. Implement `AddComment` action: validate body, store, emit `IssueCommentAdded` activity event.
5. Implement `EditComment` action: author-only, update `body` + set `edited_at`.
6. Implement `DeleteComment` action: author or Admin only.
7. Build Inertia page: left column (snapshot renderer, occurrence list, activity+comments); right sidebar (manage panel with quick lifecycle actions). Viewers see read-only snapshot; write actions hidden.
8. Source snapshot renderer: adapts output based on `issue.type` (exception → stack trace; request → method/URL/status; job → class/queue/status).
9. Write feature tests: detail loads with all sections, occurrence list filtered by env, Viewer cannot comment, author can edit own comment, Admin can delete any comment.

## Key files to create or modify
- `app/Actions/Issues/BuildIssueDetailData.php`
- `app/Actions/Issues/BuildIssueOccurrencesData.php`
- `app/Actions/Issues/AddComment.php`
- `app/Actions/Issues/EditComment.php`
- `app/Actions/Issues/DeleteComment.php`
- `database/migrations/xxxx_create_issue_comments_table.php`
- `app/Models/IssueComment.php`
- `app/Http/Controllers/Issues/IssueDetailController.php`
- `app/Http/Controllers/Issues/IssueCommentController.php`
- `resources/js/pages/issues/show.tsx`
- `resources/js/components/issues/snapshot-renderer.tsx`
- `resources/js/components/issues/comment-composer.tsx`
- `tests/Feature/Issues/IssueDetailCollabTest.php`

## Acceptance criteria
- [ ] Issue detail page loads manage panel, snapshot, occurrences, activity, and comments in a single request (with deferred props for occurrences)
- [ ] Occurrence list is paginated and filterable by environment and time period
- [ ] Each occurrence links to the originating telemetry record (request, exception, job)
- [ ] Activity timeline shows all status changes, assignments, and comments in chronological order
- [ ] Viewer role sees issue detail in read-only mode (no write actions, no comment input)
- [ ] Comment author can edit their own comment; edited comments show `edited_at` timestamp
- [ ] Admin can delete any comment; non-Admin can only delete their own

## Related specs
- [Functional spec](../specs.md) — `FR-ISS-027` to `FR-ISS-043`
- [Technical spec](../specs-technical.md)
