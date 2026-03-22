# Task T-001: Foundation Authentication Flows (Registration / Login / Logout)
- Domain: `auth`
- Status: `completed`
- Priority: `P0`
- Dependencies: none

## Description
Implement the full guest-to-authenticated lifecycle using Laravel Fortify: registration with email+password, login with credential validation, logout with session invalidation. This is the base layer every other task builds on.

## How to implement
1. Configure `FortifyServiceProvider` with `CreateNewUser` and `ResetUserPassword` actions and enable the correct Fortify features in `config/fortify.php`.
2. Register auth routes in `routes/auth.php` and ensure redirect targets are correct for web + Inertia flows.
3. Enforce password complexity via `PasswordValidationRules` concern applied in `CreateNewUser`.
4. Rotate CSRF token and invalidate session on logout.
5. Emit an `AuthAuditEvent` (login success, login failure, logout) for downstream audit consumption.
6. Write feature tests covering: registration happy path, duplicate email rejection, login with bad credentials, successful login redirect, logout invalidation.

## Key files to create or modify
- `app/Actions/Fortify/CreateNewUser.php` — user creation with validation
- `app/Actions/Fortify/ResetUserPassword.php` — password reset action
- `app/Providers/FortifyServiceProvider.php` — Fortify feature registration
- `config/fortify.php` — feature flags
- `routes/auth.php` — auth route definitions
- `resources/js/pages/auth/login.tsx` — login page
- `resources/js/pages/auth/register.tsx` — register page
- `tests/Feature/Auth/AuthenticationTest.php`
- `tests/Feature/Auth/RegistrationTest.php`

## Acceptance criteria
- [ ] A new user can register with a valid email and password and is redirected to the dashboard
- [ ] Registration is rejected for a duplicate email with a field-level error
- [ ] Registration is rejected if password does not meet policy (length, complexity)
- [ ] Login succeeds with valid credentials and creates an authenticated session
- [ ] Login fails silently (no user enumeration) for bad credentials
- [ ] Logout invalidates the session and redirects to the login page
- [ ] Login failure emits a loggable audit event with source IP

## Related specs
- [Functional spec](../specs.md) — `FR-AUTH-001` to `FR-AUTH-010`
- [Technical spec](../specs-technical.md)
