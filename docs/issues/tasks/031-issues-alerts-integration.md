# Task T-031: Analytics to Issue and Issue to Alerting Integration Hooks
- Domain: `issues`, `analytics`, `alerts`
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement cross-module integration so analytics contexts can create issues, and issues can expose backlinks to originating telemetry with scoped context.

## How to execute
1. Add analytics context payload to issue creation actions.
2. Add breadcrumbs/backlinks from issue to analytics views.
3. Ensure issue creation from exception/request/job pages preserves filters/period.
4. Add UI actions and guard rails for unauthorized use.

## Architecture implications
- **Context**: integration layer between analytics and issues.
- **Data**: issue source linkage model using trace/group/execution IDs.
- **State**: maintain filter/state context object during transitions.
- **Observability**: audit these conversion events.

## Acceptance checkpoints
- Issue creation and backlinks keep operator context unchanged.
- Duplicate source event does not spawn duplicate open issues due to dedup.

## Done criteria
- `FR-ISS-001`, `FR-ISS-045`, `FR-ISS-046` implemented.
