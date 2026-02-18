# Task T-011: `POST /api/agent-auth` Implementation
- Domain: `api`
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement bootstrap endpoint validating long-lived environment token and issuing short-lived ingestion session tokens with contract response.

## How to execute
1. Implement controller with strict token validation checks.
2. Return `token`, `expires_in`, `refresh_in`, and `ingest_url`.
3. Enforce consistent error schema for unauthorized/missing/revoked states.
4. Add replay-safe audit event emission.

## Architecture implications
- **Context**: Ingestion entry-point.
- **Storage**: token validation service + session token table/cache store.
- **Security**: short TTL, revocation-aware session tokens.
- **Observability**: metric counters for failures and throttling.

## Acceptance checkpoints
- Missing/invalid/revoked tokens return 401/403 with explicit retry guidance.

## Done criteria
- `FR-API-001` to `FR-API-019` complete.
