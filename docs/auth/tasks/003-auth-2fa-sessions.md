# Task T-003: Two-Factor Authentication and Session Controls
- Domain: `auth`
- Status: `completed`
- Priority: `P1`
- Dependencies: `T-001`, `T-002`

## Description
Add TOTP-based 2FA (enable / confirm / disable with step-up), recovery codes (generate, display once, invalidate on use), login challenge flow, and user-facing session listing with selective revocation.

## How to implement
1. Enable `Features::twoFactorAuthentication()` in Fortify; require step-up confirmation before enabling or disabling 2FA.
2. Build the TOTP enable flow: generate secret, show QR code, confirm with valid code before activating.
3. Generate 8 recovery codes on 2FA enable; display once, store hashed. Add regenerate action behind step-up.
4. Add login challenge page: if user has 2FA enabled, redirect post-credential-check to challenge page before session is established.
5. Build session list endpoint reading from the `sessions` table (or equivalent) — expose IP, user agent, last activity, current flag.
6. Add session revoke action; block revocation of the currently active session unless policy allows.
7. Write feature tests for: 2FA enable/disable with step-up, recovery code single-use, login blocked without valid TOTP, session listing, session revocation.

## Key files to create or modify
- `app/Http/Controllers/Settings/TwoFactorAuthenticationController.php` — enable/disable/confirm
- `app/Http/Controllers/Settings/SessionController.php` — list + revoke sessions
- `resources/js/pages/auth/two-factor-challenge.tsx` — login TOTP prompt
- `resources/js/pages/settings/two-factor.tsx` — 2FA management panel
- `resources/js/pages/settings/sessions.tsx` — active sessions list
- `tests/Feature/Auth/TwoFactorAuthenticationTest.php`
- `tests/Feature/Auth/SessionManagementTest.php`

## Acceptance criteria
- [ ] 2FA cannot be enabled without completing TOTP code confirmation
- [ ] 2FA cannot be disabled without a step-up password confirmation
- [ ] Recovery codes are shown exactly once and then unavailable in plaintext
- [ ] A used recovery code is invalidated and cannot be reused
- [ ] Login with 2FA enabled requires the TOTP challenge before granting session
- [ ] Invalid TOTP code on login does not grant access
- [ ] Session list shows all active sessions with IP and last-activity
- [ ] A user can revoke any session except the current one

## Related specs
- [Functional spec](../specs.md) — `FR-AUTH-020` to `FR-AUTH-027`
- [Technical spec](../specs-technical.md)
