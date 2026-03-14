# Task T-031: Analytics ↔ Issue and Issue ↔ Alert Integration
- Domain: `issues`, `analytics`, `alerts`
- Status: `not started`
- Priority: `P0`
- Dependencies: `T-022`, `T-023`, `T-024`, `T-015`, `T-021`, `T-025`

## Description
Wire the cross-module integration layer: analytics pages can create issues from exception/request/job contexts; issues expose backlinks to originating telemetry with period+filter preserved; and alert evaluator (T-026) can auto-create issues on rule trigger.

## How to implement

### Analytics → Issue creation
1. Add "Create Issue" action button on exception detail, request detail, and job attempt detail pages (T-016, T-017, T-019, T-021).
2. Implement `CreateIssueFromAnalytics` action wrapping `CreateIssue` (T-022): package current analytics context (`trace_id`, `group_key`, `execution_id`, `period`, `filters`) as the source snapshot payload.
3. On success: redirect to the newly created issue (or existing open issue if deduplicated) preserving context in the URL.
4. Guard: require `Developer` or above role; Viewers see the button as disabled.

### Issue → Analytics backlinks
5. In issue detail (T-024), render a "View in [Analytics Type]" backlink using the stored `source.group_key` / `source.trace_id`: links to the analytics detail page with the originating period pre-filled.
6. Ensure backlink resolves gracefully if the originating telemetry record no longer exists (show a tombstone state, not a 500).

### Alert → Issue auto-creation (optional, configurable per rule)
7. Add `create_issue_on_trigger` boolean to `alert_rules` table.
8. In `EvaluateAlertRules` job (T-026): on `ok → triggered` transition, if flag is set, dispatch `CreateIssueFromAlert` job with rule context.
9. Implement `CreateIssueFromAlert` action: create issue with type=performance, title from rule name, source snapshot from alert state.

### Tests
10. Write integration feature tests: creating issue from exception detail redirects to correct issue, dedup still works (existing open issue), backlink resolves to correct analytics page, backlink tombstone on missing record, alert auto-create issue on trigger.

## Key files to create or modify
- `app/Actions/Issues/CreateIssueFromAnalytics.php`
- `app/Actions/Issues/CreateIssueFromAlert.php`
- `app/Jobs/CreateIssueFromAlert.php`
- `database/migrations/xxxx_add_create_issue_on_trigger_to_alert_rules_table.php`
- `resources/js/pages/analytics/exceptions/show.tsx` — add Create Issue button
- `resources/js/pages/analytics/requests/show.tsx` — add Create Issue button
- `resources/js/pages/analytics/jobs/show.tsx` — add Create Issue button
- `resources/js/pages/issues/show.tsx` — add analytics backlink
- `resources/js/components/issues/analytics-backlink.tsx`
- `tests/Feature/Issues/IssueAnalyticsIntegrationTest.php`

## Acceptance criteria
- [ ] "Create Issue" button appears on exception, request, and job detail pages for Developer+ roles
- [ ] Viewer role sees the button disabled (not hidden)
- [ ] Creating an issue from analytics preserves the source `trace_id`, `group_key`, period, and filter context
- [ ] Deduplication still works — creating from the same exception twice resolves to the same open issue
- [ ] Issue detail shows a "View in Analytics" backlink pointing to the originating record
- [ ] If the originating telemetry record has been purged, the backlink shows a tombstone (not a 500)
- [ ] Alert rule with `create_issue_on_trigger` creates an issue on the first `ok → triggered` transition

## Related specs
- [Functional spec](../specs.md) — `FR-ISS-001`, `FR-ISS-045`, `FR-ISS-046`
- [Technical spec](../specs-technical.md)
