# Task T-009: Project and Environment Lifecycle
- Domain: `projects`
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement project CRUD, health status metadata, and environment lifecycle under project scope.

## How to implement
1. Create project CRUD with unique name/slug rules and owner org scoping.
2. Add environment CRUD with uniqueness and status/state transitions.
3. Add computed project health indicators from environment signals.
4. Exclude archived/inactive resources from active onboarding and active routes.

## Architecture implications
- **Context**: Monitored assets domain.
- **Storage**: `projects`, `environments`, project health snapshots.
- **Jobs**: periodic recalculation worker for health states.
- **Navigation**: project/environment IDs required in analytics/API paths.

## Acceptance checkpoints
- Project cannot be considered active without at least one environment.
- Health/status recalculates at interval (>=1m).

## Done criteria
- `FR-PROJ-001` to `FR-PROJ-019` covered.
