# Task T-011: Agent Auth Bootstrap — `POST /api/agent-auth`
- Domain: `api`
- Status: `completed`
- Priority: `P0`
- Dependencies: `T-010`, `T-029`

## Description
Implement the agent bootstrap endpoint that validates a long-lived `NIGHTWATCH_TOKEN` (environment ingestion token) and issues a short-lived session token used for all subsequent ingest calls. The response includes `token`, `expires_in`, `refresh_in`, and `ingest_url`.

## How to implement
1. Register the route `POST /api/agent-auth` outside web middleware (stateless, no CSRF), under a dedicated `api-ingest` middleware group.
2. Parse `Authorization: Bearer <NIGHTWATCH_TOKEN>` header; reject missing header with `401` + `{ message, refresh_in: 60 }`.
3. Call `ValidateIngestToken` service (T-010): check hash, status (active or deprecated-in-grace), environment binding. Return `401`/`403` with appropriate message on failure.
4. On success: generate a short-lived session token (UUID or signed JWT), store in Redis with TTL (`expires_in`), bind to `environment_id`.
5. Return `200` with `{ token, expires_in, refresh_in, ingest_url }`. `ingest_url` resolves from config.
6. Emit a loggable audit event on success and on each failure type (invalid, expired, revoked) with source IP — no raw token values in logs.
7. Write feature tests for: valid token → 200 with required fields, missing header → 401 + refresh_in, revoked token → 403, expired/deprecated-past-grace → 401, malformed payload → 400.

## Key files to create or modify
- `app/Http/Controllers/Api/AgentAuthController.php`
- `app/Services/Ingestion/SessionTokenService.php` — generate + store session token in Redis
- `app/Http/Middleware/ParseAgentAuthHeader.php`
- `routes/api.php` — `POST /api/agent-auth`
- `config/ingest.php` — `ingest_url`, `session_ttl`, `refresh_in` defaults
- `tests/Feature/Api/AgentAuthTest.php`

## Acceptance criteria
- [ ] Valid `NIGHTWATCH_TOKEN` returns `200` with `token`, `expires_in`, `refresh_in`, `ingest_url`
- [ ] Missing `Authorization` header returns `401` with `message` and `refresh_in: 60`
- [ ] Invalid/unknown token returns `401`
- [ ] Revoked token returns `403` with explicit message
- [ ] Deprecated token past grace window returns `401`
- [ ] Response never includes the original `NIGHTWATCH_TOKEN` or any raw token material
- [ ] Failure events are loggable with source IP and reason, no token values

## Related specs
- [Functional spec](../specs.md) — `FR-API-001` to `FR-API-019`
- [Technical spec](../specs-technical.md)
