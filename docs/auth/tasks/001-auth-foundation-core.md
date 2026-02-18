# Task T-001: Foundation Authentication Flows (Registration/Login/Logout)
- Domain: `auth`
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement guest-to-authenticated flows with account bootstrap and secure session lifecycle: registration, email/password validation, login, logout, and protected redirect behavior.

## How to implement
1. Add/adjust routes under `routes/web.php` and `routes/auth.php` for register/login/logout flows.
2. Use Fortify actions + custom validators for password complexity and account creation rules.
3. Implement verification-safe session cookies and CSRF rotation on logout.
4. Ensure consistent JSON + web responses for auth endpoints.
5. Add tests at feature level for happy path + negative auth paths.

## Architecture implications
- **Context**: `Identity & Access` bounded context.
- **Storage**: `users`, `password_resets`, `failed_jobs`/`rate_limits` usage via Laravel internals.
- **Service layer**: use case actions wrapping Fortify interactions so controllers remain thin.
- **Auth events**: emit auth audit events (login success/failure, logout) for later consumption by centralized audit.

## Acceptance checkpoints
- Registration enforces unique email and password policy.
- Login rejects bad credentials without information leakage.
- Logout invalidates session and CSRF context.

## Done criteria
- Implemented and tested in web + API-compatible flows.
- `FR-AUTH-001` to `FR-AUTH-010` covered.
