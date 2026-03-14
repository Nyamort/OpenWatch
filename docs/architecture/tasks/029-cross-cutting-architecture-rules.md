# Task T-029: Cross-Cutting Tenant Resolution, Policy Gates, and API Error Contract
- Domain: `cross-cutting`
- Status: `not started`
- Priority: `P0`
- Dependencies: `T-001`

## Description
Standardize the middleware pipeline for tenant resolution (org → project → environment), shared policy base class, and consistent error response shape across all Inertia web routes and JSON API routes. This task should be completed before any domain feature that needs org-scoped access control.

## How to implement
1. Create `SetOrganizationContext` middleware: resolve organization from route parameter (`{organization}`) or session (`current_organization_id`); bind it to the request; abort 403 if user is not a member.
2. Create `SetProjectContext` middleware: resolve project from route parameter, verify it belongs to the active org; abort 404 (scoped, not 403) to avoid leaking existence.
3. Create `SetEnvironmentContext` middleware: same pattern for environment under project.
4. Register middleware in `bootstrap/app.php` in the correct order after auth middleware.
5. Create `ApiErrorResponse` helper and `HandlesApiErrors` trait: map exceptions (`AuthorizationException` → 403, `ModelNotFoundException` → 404, `ValidationException` → 422) to a consistent JSON shape `{ message, errors? }`.
6. Extend Inertia's error handling to use the same shape for XHR/Inertia requests.
7. Write integration tests: missing org param → 403, org exists but user not a member → 403, project belongs to different org → 404, JSON client gets correct error shape, Inertia client gets correct shape.

## Key files to create or modify
- `app/Http/Middleware/SetOrganizationContext.php`
- `app/Http/Middleware/SetProjectContext.php`
- `app/Http/Middleware/SetEnvironmentContext.php`
- `app/Http/Traits/HandlesApiErrors.php`
- `app/Exceptions/Handler.php` — register exception rendering
- `bootstrap/app.php` — middleware registration and ordering
- `tests/Feature/TenantIsolationTest.php`

## Acceptance criteria
- [ ] Every request to an org-scoped route resolves org context before any controller logic runs
- [ ] A user not a member of the org receives `403` — not a `404` that would leak existence
- [ ] A project route with a project from a different org returns `404` (scoped, not 403)
- [ ] Removing a user's membership takes effect on their immediately next request
- [ ] JSON API clients receive `{ message, errors? }` for all error types
- [ ] Inertia/XHR clients receive the same error shape
- [ ] No route can bypass tenant resolution middleware

## Related specs
- [Functional spec](../specs.md)
- [Technical spec](../specs-technical.md)
