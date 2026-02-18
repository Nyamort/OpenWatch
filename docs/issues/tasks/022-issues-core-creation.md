# Task T-022: Issue Creation and Deduplication
- Domain: `issues`
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement issue creation from analytics contexts with stable issue identity, canonical fingerprinting, and dedup/merge policy.

## How to execute
1. Add endpoint/service to create issue from supported sources.
2. Compute canonical fingerprints from source snapshots.
3. Resolve duplicate active issue by fingerprint and apply merge policy.
4. Include context return path to redirect user back to issue detail after creation.

## Architecture implications
- **Context**: issues bounded context fed by analytics module.
- **Storage**: `issues`, `issue_fingerprints`, `issue_sources`, `occurrences`.
- **Concurrency**: idempotent create with unique constraints and advisory locks where needed.
- **Audit**: create action event with source trace metadata.

## Acceptance checkpoints
- Duplicate attempts do not create duplicate unresolved issues.
- Creation preserves analytics context in navigation.

## Done criteria
- `FR-ISS-001` to `FR-ISS-008` complete.
