# Task T-018: Cache-Event Analytics
- Domain: `analytics`
- Status: `not started`
- Priority: `P1`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement aggregated cache-event dashboard without per-row drilldown, focused on key aggregates and failure breakdown.

## How to execute
1. Implement list queries for operation-by-key aggregates.
2. Add failure-rate and counts, plus default sorting and explicit empty state.
3. Ensure no row-level detail action exposed.
4. Reuse shared period/context controls from analytics shell.

## Architecture implications
- **Context**: analytics read model for cache events.
- **Schema**: event-level payload to aggregate table for repeated key access.
- **UI**: list-only pattern with compact KPI summary.

## Acceptance checkpoints
- Default ordering and sorting are explicit and deterministic.
- No detail action appears in list rows.

## Done criteria
- `docs/analytics/cache-event/cache-event*.md` covered.
