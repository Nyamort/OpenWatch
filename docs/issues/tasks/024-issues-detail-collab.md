# Task T-024: Issue Detail, Occurrences, Activity and Comments
- Domain: `issues`
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement issue detail manage panel, source snapshots, occurrences, activity history, comments, and quick actions (resolve/reopen).

## How to execute
1. Create issue detail page with manage panel and lifecycle actions.
2. Add source snapshot renderer adaptable to issue type.
3. Add paginated occurrences tied to issue fingerprint and filters.
4. Add activity timeline + comments with Markdown compose/view toggle.

## Architecture implications
- **Context**: issue context + source-event enrichment joins.
- **Storage**: `issue_activities`, `issue_comments`, `issue_occurrences` with audit linkage.
- **Search**: filter by environment/time for occurrences list.
- **Permissions**: read/write split by role.

## Acceptance checkpoints
- Manage panel updates visible on immediate subsequent reads.
- Users without write permission get read-only mode.

## Done criteria
- `FR-ISS-027` to `FR-ISS-043` complete.
