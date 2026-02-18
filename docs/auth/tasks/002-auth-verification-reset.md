# Task T-002: Email Verification, Password Reset and Confirmations
- Domain: `auth`
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement account activation flow, password reset lifecycle, signed links, expiration controls, and step-up confirmation gates.

## How to implement
1. Configure Fortify email verification routes with signed links and TTL.
2. Add password reset request + submit flows with token single-use policies.
3. Add policy check for sensitive actions requiring password confirmation.
4. Ensure all tokens are never logged in plaintext and comply with configurable expiry.

## Architecture implications
- **Context**: `Identity & Access`.
- **Storage**: password reset tokens and verification tokens managed via secure columns/secure caches.
- **Workers**: optional queueing for email dispatch.
- **Security**: audit events for `request reset`, `reset success/failure`, `email verified`, `step-up required`.

## Acceptance checkpoints
- Expired/missing/invalid tokens are rejected with user-safe messages.
- Verification status gates verified-only routes.

## Done criteria
- `FR-AUTH-011` to `FR-AUTH-019` implemented and tested.
