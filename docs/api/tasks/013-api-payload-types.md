# Task T-013: Payload Record Type Validation for Ingestion
- Domain: `api`
- Status: `not started`
- Priority: `P1`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Build schema-level validation per record type and mandatory field checks for supported `t` values.

## How to execute
1. Define per-type DTO schemas and nullable/missing-value policy (mandatory key present, empty value allowed).
2. Add unknown `t` guard with backoff-aware failure response.
3. Persist correlation keys and execution context for cross-page linkage.
4. Add contract tests for each supported type and required-fields matrix.

## Architecture implications
- **Context**: API parser layer + analytics domain adapters.
- **Storage**: normalized metadata columns + raw payload for optional fields.
- **Extensibility**: add new event type by registry + handler implementation.
- **Governance**: typed validator prevents partial ingest corruption.

## Acceptance checkpoints
- Unknown event type does not corrupt pipeline.
- Mandatory keys are enforced per type.

## Done criteria
- `FR-API-033` to `FR-API-039` validated.
