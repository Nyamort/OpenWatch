# Task T-010: Environment Ingestion Token Lifecycle
- Domain: `projects`
- Status: `not started`
- Priority: `P0`
- Dependencies: `T-009`

## Description
Implement high-entropy environment-scoped ingestion tokens: generate on environment creation, display plaintext exactly once, store only an encrypted/hashed representation, support rotation with configurable grace window where both old and new tokens are valid, and revocation.

## How to implement
1. Create `project_tokens` migration: `id`, `environment_id`, `token_hash` (sha256 of raw token), `status` (active/deprecated/revoked), `expires_at`, `grace_until`, `rotated_at`, timestamps.
2. Implement `GenerateToken` action: generate 32-byte CSPRNG token, sha256-hash for storage, return raw value to caller (once only).
3. Implement `RotateToken` action: deprecate active token (set `grace_until` to `now() + grace_window`), generate new active token.
4. Implement `RevokeToken` action: set status to `revoked` immediately, no grace window.
5. Add `ValidateIngestToken` service (used by T-011): lookup by `token_hash`, check status is `active` or (`deprecated` AND `now() < grace_until`).
6. Expose token list (no plaintext) and token rotation/revocation endpoints for Owner/Developer.
7. Write feature tests: token generated on env creation, raw value returned once, rotation grace window validation, revoked token rejected, deprecated-but-in-grace token accepted.

## Key files to create or modify
- `database/migrations/xxxx_create_project_tokens_table.php`
- `app/Models/ProjectToken.php`
- `app/Actions/Projects/GenerateToken.php`
- `app/Actions/Projects/RotateToken.php`
- `app/Actions/Projects/RevokeToken.php`
- `app/Services/Ingestion/ValidateIngestToken.php`
- `app/Http/Controllers/Projects/TokenController.php`
- `resources/js/pages/projects/tokens.tsx` — token management UI
- `tests/Feature/Projects/ProjectTokenLifecycleTest.php`

## Acceptance criteria
- [ ] Token raw value is returned exactly once (at creation) and never retrievable again
- [ ] Token is stored as a sha256 hash — no plaintext in the database
- [ ] Rotation generates a new active token and marks the old one deprecated with a grace window
- [ ] A deprecated token within its grace window is still accepted for ingestion
- [ ] A deprecated token past its grace window is rejected
- [ ] A revoked token is rejected immediately with no grace window
- [ ] Token values never appear in application logs

## Related specs
- [Functional spec](../specs.md) — `FR-PROJ-020` to `FR-PROJ-027`
- [Technical spec](../specs-technical.md)
