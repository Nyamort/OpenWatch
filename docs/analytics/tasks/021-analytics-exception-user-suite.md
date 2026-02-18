# Task T-021: Exception and User Analytics
- Domain: `analytics`
- Status: `not started`
- Priority: `P1`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement exception and user analytics including list, detail views, occurrence history, and user pivot links.

## How to execute
1. Implement exception list/detail with handled/unhandled and occurrence navigation.
2. Implement user list/detail with impacted counts and redirect to filtered analytics on pivot.
3. Add search and filter semantics plus chronological constraints.
4. Add graceful handling for missing references and stale rows.

## Architecture implications
- **Context**: telemetry context bridge between exception/user and source records.
- **Data**: user lookup deduplication table or computed identity index.
- **Relations**: exception/user occurrences join by trace/group/execution.
- **Privacy**: redaction policy for user-related sensitive payload fields.

## Acceptance checkpoints
- User pivot filters update target pages with context.
- Exception detail includes raw metadata and related telemetry.

## Done criteria
- `docs/analytics/exception/*`, `docs/analytics/user/*` implemented.
