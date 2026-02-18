# Task T-004: Organization-Scoped Authorization Integration
- Domain: `auth`, shared with `organisation`
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Ensure all protected actions consume org/role/permission definitions from Organization context before allow/deny decisions.

## How to implement
1. Introduce active organization context middleware that resolves organization scope prior to policy checks.
2. Normalize policies to call organization-scoped permission resolvers.
3. Add guards for routes requiring `verified` and org context.
4. Add explicit forbidden/error shape consistency middleware for web + JSON clients.

## Architecture implications
- **Context**: cross-cutting authz layer.
- **Pattern**: domain policies become pure decision points; do not replicate role logic in controllers.
- **Data model**: every policy-aware query constrained by tenant/global scopes.
- **Reliability**: no long-lived permission cache for critical checks.

## Acceptance checkpoints
- Authorization decisions reflect immediate membership/role changes.
- No cross-organization access possible in protected requests.

## Done criteria
- `FR-AUTH-030` to `FR-AUTH-035` implemented with integration tests.
