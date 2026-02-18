# Task T-027: Dashboard Core Experience
- Domain: `dashboard`
- Status: `not started`
- Priority: `P1`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Build authenticated dashboard home with period controls, activity/application/users sections, and links into analytics and alerts.

## How to execute
1. Implement sectioned dashboard layout and period presets.
2. Implement metric cards for requests/exceptions/jobs/users with aggregated stats.
3. Add contextual links preserving period/project/environment.
4. Add empty and loading states for each section.

## Architecture implications
- **Context**: dashboard composer service using analytics summary repository.
- **Storage**: read models or materialized aggregates for fast card load.
- **Navigation**: central context helper for link generation.
- **Performance**: parallel fetch of section data.

## Acceptance checkpoints
- Period change updates all sections consistently.
- Dashboard links retain context and filtering.

## Done criteria
- `FR-DB-001` to `FR-DB-031` complete.
