# Task T-004: Organization-Scoped Authorization Integration
- Domain: `auth`, shared with `organisation`
- Status: `completed`
- Priority: `P0`
- Dependencies: `T-001`, `T-005`, `T-006`, `T-029`

## Description
Ensure every protected route resolves the active organization context before any policy check runs. Policies must delegate permission resolution to the organization role/permission system — no hardcoded role checks in controllers.

## How to implement
1. Apply `SetOrganizationContext` middleware (from T-029) on all authenticated web and API routes.
2. Create a base `OrganizationPolicy` that resolves permissions from the organization member's role via a `PermissionResolver` service (use Redis cache with TTL + invalidation on role change).
3. Register specific policies (`ProjectPolicy`, `EnvironmentPolicy`, etc.) extending the base.
4. Add a `verified` + org-context guard to routes that require both email verification and an active org.
5. Return consistent `403` JSON/Inertia responses for forbidden access (no information leakage about resource existence).
6. Write integration tests proving: cross-org access is blocked, role-downgrade takes effect on next request, Viewer cannot perform write actions.

## Key files to create or modify
- `app/Http/Middleware/SetOrganizationContext.php` — resolves org from route binding
- `app/Services/Authorization/PermissionResolver.php` — role → permission lookup with cache
- `app/Policies/OrganizationPolicy.php` — base policy
- `app/Policies/ProjectPolicy.php` — project-scoped policy
- `bootstrap/app.php` — middleware registration order
- `app/Providers/AuthServiceProvider.php` — policy registration
- `tests/Feature/Auth/OrganizationAuthorizationTest.php`

## Acceptance criteria
- [ ] A request without an active organization context is rejected before any business logic runs
- [ ] A user belonging to Org A cannot access any resource of Org B
- [ ] Role change for a member takes effect on the immediately following request (no stale cache)
- [ ] A `Viewer` role member receives `403` attempting any write operation
- [ ] Forbidden responses have the same shape for both Inertia and JSON API clients
- [ ] `PermissionResolver` hits Redis on warm requests and falls back to DB on cache miss

## Related specs
- [Functional spec](../specs.md) — `FR-AUTH-030` to `FR-AUTH-035`
- [Technical spec](../specs-technical.md)
