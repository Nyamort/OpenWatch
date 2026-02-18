# Task T-029: Cross-Cutting Tenant/Policy Enforcement and API Error Contract
- Domain: Cross-cutting
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Standardize tenant resolution, policy gates, and error contract across auth, API, analytics, issues, alerts, and dashboard routes.

## How to execute
1. Build middleware/request pipeline to resolve organization/project/environment scope consistently.
2. Add shared policy base and forbidden/unauthorized response format.
3. Add integration tests proving cross-org and wrong-scope requests are rejected.
4. Add middleware ordering docs and registration review.

## Architecture implications
- **Context**: API gateway-like shared request boundary.
- **Security**: no route leaves with implicit scope.
- **Maintainability**: centralized context and error mapping reduces drift.
- **Compatibility**: ensure Inertia and JSON clients receive compatible responses.

## Acceptance checkpoints
- Missing/invalid context yields consistent denied flow.
- Tenant checks executed before sensitive business logic.

## Done criteria
- FR cross-cutting from `FR-AUTH-030`, `FR-AN-122`, `FR-ORG-007`, and related route isolation specs.
