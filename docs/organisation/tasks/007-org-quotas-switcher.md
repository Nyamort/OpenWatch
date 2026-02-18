# Task T-007: Organization Limits, Plan Gates, and Org Switching
- Domain: `organisation`
- Status: `not started`
- Priority: `P1`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement organization-level quotas/limits, warning and enforcement policies, and safe organization switching with persisted context.

## How to implement
1. Add configurable quota model per organization and check points in create/ingest surfaces.
2. Add quota warning threshold telemetry + explicit hard-limit behavior.
3. Add UI/API org switcher, atomic context update and persist per user.
4. Add membership multi-org fallback and explicit access rules per role.

## Architecture implications
- **Cross-cutting**: used by ingestion and project APIs.
- **Storage**: org plan/rules table + current_active_org cache keyed by user.
- **Consistency**: enforce limits centrally to avoid bypass through UI and API.
- **State propagation**: switch updates policy cache and authorization context atomically.

## Acceptance checkpoints
- Limits are enforced consistently and auditable.
- Org switching updates permissions immediately.

## Done criteria
- `FR-ORG-035` to `FR-ORG-051` implemented.
