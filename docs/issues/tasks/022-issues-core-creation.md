# Task T-022: Issue Creation and Deduplication
- Domain: `issues`
- Status: `not started`
- Priority: `P0`
- Dependencies: `T-015`, `T-021`, `T-029`

## Description
Implement issue entity creation from analytics contexts (exception, request, job) with canonical fingerprinting, deduplication (no duplicate open issues for the same fingerprint), and source linkage preserving the originating telemetry reference.

## How to implement
1. Create migrations: `issues` (`id`, `organization_id`, `project_id`, `environment_id`, `title`, `fingerprint`, `type` enum, `status` enum, `priority`, `assignee_id`, `first_seen_at`, `last_seen_at`, `occurrence_count`, timestamps) and `issue_sources` (`issue_id`, `source_type`, `trace_id`, `group_key`, `execution_id`, `snapshot` JSONB).
2. Implement `FingerprintService`: compute canonical fingerprint per issue type (for exceptions: sha256 of class+message+file+line; for requests: method+route+status; for jobs: class+queue).
3. Implement `CreateIssue` action:
   - Compute fingerprint via `FingerprintService`
   - Check for existing open issue with same fingerprint in scope → if found, increment `occurrence_count` and update `last_seen_at` (no duplicate created)
   - Otherwise create new issue, attach source record
   - Use DB transaction + unique constraint on `(organization_id, project_id, environment_id, fingerprint, status=open)` to handle race conditions
4. Emit `IssueCreated` event for downstream listeners (audit, notifications).
5. Write feature tests: first occurrence creates issue, second occurrence increments count (no duplicate), concurrent requests do not create duplicates, source linkage is stored.

## Key files to create or modify
- `database/migrations/xxxx_create_issues_table.php`
- `database/migrations/xxxx_create_issue_sources_table.php`
- `app/Models/Issue.php`
- `app/Models/IssueSource.php`
- `app/Services/Issues/FingerprintService.php`
- `app/Actions/Issues/CreateIssue.php`
- `app/Events/IssueCreated.php`
- `tests/Feature/Issues/IssueCoreCreationTest.php`

## Acceptance criteria
- [ ] Creating an issue from an exception produces a fingerprint based on class+message+file+line
- [ ] A second occurrence with the same fingerprint increments the existing open issue's count
- [ ] No duplicate open issues are created, even under concurrent requests (unique constraint + lock)
- [ ] The source record preserves `trace_id`, `group_key`, `execution_id`, and a JSON snapshot of the originating telemetry
- [ ] Issue creation emits an `IssueCreated` event
- [ ] Cross-org issue creation is blocked

## Related specs
- [Functional spec](../specs.md) — `FR-ISS-001` to `FR-ISS-008`
- [Technical spec](../specs-technical.md)
