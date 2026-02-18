# Task T-015: Shared Analytics Shell and Context Engine
- Domain: `analytics`
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Build shared analytics base layer: period presets, context preservation, record-type label, search/filtering, pagination/sorting defaults, and URL-state sync.

## How to execute
1. Create shared controller/action + Inertia component composition.
2. Implement period utility with presets and custom validation.
3. Implement org/project/environment context hydration and propagation via links.
4. Standardize table wrappers for sort/paginate/search and empty states.

## Architecture implications
- **Context**: shared analytics module in front-end/backend boundary.
- **Backend**: query service that accepts filters/period context and returns typed paginated DTOs.
- **Caching**: optional short-lived cache by route+period+filters.
- **Routing**: consistent URL query schema and history-safe navigation.

## Acceptance checkpoints
- All analytics pages render with identical period behavior.
- Filters and context survive drill-down/back navigation.

## Done criteria
- `FR-AN-001`, `FR-AN-002`, `FR-AN-010` to `FR-AN-026` foundational behavior complete.
