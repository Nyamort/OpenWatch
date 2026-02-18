# Task T-019: Command and Jobs Analytics Suite
- Domain: `analytics`
- Priority: `P0`
- Status: `not started`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Deliver command and jobs analytics including merged jobs page (`queued-job` + `job-attempt`) and corresponding detail levels.

## How to implement
1. Build command list/detail with success/failed counters, run timelines, and threshold indicator.
2. Build jobs aggregated page by status counters and merged event model.
3. Implement job detail and attempt detail pages with status filtering and timeline metadata.
4. Ensure search/sort behavior aligns with each type and period context.

## Architecture implications
- **Context**: analytics type adapter layer.
- **Data model**: type normalization view that merges two event families for jobs.
- **Storage/index**: composite indexes on status + timestamps + execution identifier.
- **Performance**: index-only scans for heavy lists.

## Acceptance checkpoints
- Jobs surface both event types in one list.
- Detail drilldowns from command/jobs pages preserve context.

## Done criteria
- `docs/analytics/command/*`, `docs/analytics/jobs/*`, and `FR-AN-033` implemented.
