# Task T-005: Organization Lifecycle and Tenant Boundaries
- Domain: `organisation`
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement organization create/update/soft-delete/restore and enforce strict org tenancy guarantees on all domain entities.

## How to implement
1. Add CRUD for organizations and organization-scoped profile metadata.
2. Implement soft delete/restore flows with confirmable actions.
3. Enforce org-bound foreign keys and tenant-scoped scopes in repositories.
4. Add global query scopes and tests covering cross-tenant leakage.

## Architecture implications
- **Context**: Tenant boundary owner context.
- **Persistence**: `organizations`, `organization_members`, `organization_settings`, deleted-at lifecycle.
- **Authorization**: every query starts with validated `active_org_id`.
- **Migration**: constraints + indexes to protect uniqueness (`slug`, org-member tuples).

## Acceptance checkpoints
- User can create and switch org only via allowed paths.
- No cross-org access after context enforcement.

## Done criteria
- `FR-ORG-001` to `FR-ORG-011` validated.
