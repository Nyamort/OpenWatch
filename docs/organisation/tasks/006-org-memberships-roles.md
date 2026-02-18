# Task T-006: Memberships, Invitations, and Roles
- Domain: `organisation`
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement invite/accept/revoke workflows, role assignment changes, owner transfer constraints, and custom role/permission definitions.

## How to implement
1. Add invitation token model with expiry and single-use behavior.
2. Build acceptance/rejection endpoints with signed URLs.
3. Implement role catalog, custom role CRUD, and default bootstrap roles.
4. Enforce immediate permission propagation.

## Architecture implications
- **Context**: Authorization domain provider for auth.
- **Storage**: invitation table + role/permission pivot + audit trail.
- **Security**: ownership transfer guarded by atomic checks.
- **UX**: role changes should impact request-level policy refresh.

## Acceptance checkpoints
- Last owner transfer requires replacement owner.
- Owner/admin can remove/assign members and roles per policy.

## Done criteria
- `FR-ORG-012` to `FR-ORG-027` implemented.
