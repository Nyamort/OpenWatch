# Task T-003: Two-Factor Authentication and Session Controls
- Domain: `auth`
- Status: `not started`
- Priority: `P1`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Add TOTP enable/disable flow, recovery code lifecycle, login challenge support, and session/device listing/revocation.

## How to implement
1. Enable Fortify TOTP feature with secure code handling and confirmation states.
2. Add recovery code generation + one-time invalidation.
3. Build session listing endpoint/page with metadata (IP/device/last activity).
4. Add revoke action for sessions with policy restrictions.
5. Add force sign-out action for privileged admin operations.

## Architecture implications
- **Context**: `Identity & Access` + `User Settings` for own-session controls.
- **Storage**: `authentications`/`sessions` style store + secure one-time codes.
- **Security**: require step-up confirmation before enabling/disabling 2FA.
- **Performance**: session listing should use indexed pagination.

## Acceptance checkpoints
- 2FA states visible as `disabled/pending_confirmation/enabled`.
- Recovery code usage is single-use.

## Done criteria
- `FR-AUTH-020` to `FR-AUTH-027` covered.
