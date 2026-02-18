# Task T-010: Environment Secret Token Lifecycle
- Domain: `projects`
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement environment-scoped ingestion tokens, one-time secret display, rotation with configurable grace window, and secure revocation behavior.

## How to implement
1. Generate high-entropy tokens at environment creation/on demand.
2. Store only irreversible/encrypted representation by design.
3. Return clear-text token exactly once at creation.
4. Implement rotation with immediate/grace-window acceptance paths.
5. Integrate token validity checks into ingestion pipeline.

## Architecture implications
- **Context**: API ingestion security boundary.
- **Storage**: token table includes environment binding, status, rotated_at, grace_until.
- **Security**: strict KMS-backed encryption and no plaintext logging.
- **Tests**: explicit transition tests for grace window and revocation.

## Acceptance checkpoints
- Token routes cannot expose raw token after create.
- Rotation updates behavior immediately for new requests.

## Done criteria
- `FR-PROJ-020` to `FR-PROJ-027` fully implemented.
