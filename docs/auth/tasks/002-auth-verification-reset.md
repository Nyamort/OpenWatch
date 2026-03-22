# Task T-002: Email Verification, Password Reset and Step-Up Confirmation
- Domain: `auth`
- Status: `completed`
- Priority: `P0`
- Dependencies: `T-001`

## Description
Add email verification gate (signed link, TTL), password reset lifecycle (request → email → consume token → new password), and step-up confirmation gate for sensitive actions. No token should ever appear in plaintext logs.

## How to implement
1. Enable `Features::emailVerification()` in Fortify and configure signed verification routes with expiry.
2. Add `EnsureEmailIsVerified` middleware to protected routes and an Inertia-friendly blocked state page.
3. Implement password reset request: validate email existence silently, dispatch `ResetPasswordNotification` via queue, store signed token.
4. Implement password reset submit: validate token (single-use, TTL), update password, invalidate all sessions except current.
5. Add `password.confirm` middleware support for sensitive actions (step-up gate).
6. Write feature tests for: verified/unverified gate, expired token rejection, used token rejection, silent email-not-found on reset request.

## Key files to create or modify
- `app/Providers/FortifyServiceProvider.php` — enable email verification feature
- `app/Notifications/ResetPasswordNotification.php` — queued password reset email
- `routes/auth.php` — verification and reset routes
- `resources/js/pages/auth/verify-email.tsx` — verification pending page
- `resources/js/pages/auth/forgot-password.tsx` — reset request form
- `resources/js/pages/auth/reset-password.tsx` — new password form
- `resources/js/pages/auth/confirm-password.tsx` — step-up confirmation page
- `tests/Feature/Auth/EmailVerificationTest.php`
- `tests/Feature/Auth/PasswordResetTest.php`

## Acceptance criteria
- [ ] Unverified users are blocked from protected routes and shown a verification-pending page
- [ ] Verification link expires after configured TTL and shows a clear error
- [ ] Password reset request returns the same response whether the email exists or not (no enumeration)
- [ ] Reset token is single-use — a second attempt with the same token is rejected
- [ ] After reset, all other sessions are invalidated
- [ ] Step-up confirmation gate prompts for password before sensitive actions
- [ ] Token values never appear in application logs

## Related specs
- [Functional spec](../specs.md) — `FR-AUTH-011` to `FR-AUTH-019`
- [Technical spec](../specs-technical.md)
