# Task T-017: Query, Log, and Mail Analytics
- Domain: `analytics`
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement analytics pages for query, log, and mail including dedicated detail drills and sorting/search behavior.

## How to execute
1. Implement query list/detail with SQL summary and search columns.
2. Implement log list with `level/user/period` filters, newest-first pagination, and detail context page.
3. Implement mail list and mail detail with recipient counters and domain-specific cards.
4. Add consistent empty states and fallback when payload is missing.

## Architecture implications
- **Context**: analytics by event type handlers (`query`, `log`, `mail`).
- **Storage**: aggregated views for query duration buckets and mail counts.
- **UI**: table schema per type with explicit sort keys.
- **Reliability**: large payload rendering with collapsed JSON sections.

## Acceptance checkpoints
- Logs are ordered newest-first by default.
- Mail/details pages keep context and do not break on optional fields.

## Done criteria
- `docs/analytics/query/*.md`, `docs/analytics/log/*.md`, `docs/analytics/mail/*.md` requirements covered.
