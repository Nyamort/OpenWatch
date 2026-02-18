# Task T-012: Ingestion Endpoint `POST {ingest_url}`
- Domain: `api`
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement compressed payload ingestion endpoint with gzip contract, structural validation, transport errors, and success semantics (`{}`).

## How to execute
1. Validate `Content-Encoding: gzip` and fail with `415` where contract is broken.
2. Inflate and validate JSON shape before parse stage.
3. Map ingest errors to 400/403/429/5xx contracts as specified.
4. Keep a minimal response on success and enforce stop/backoff fields.

## Architecture implications
- **Context**: ingestion API + backpressure.
- **Parsing service**: separated streaming decompressor and schema validator.
- **Limits**: enforce payload size and record size bounds.
- **Queueing**: hand off valid payload to async jobs.

## Acceptance checkpoints
- Valid payload returns 200 + `{}`.
- Non-gzip payload returns 415.

## Done criteria
- `FR-API-020` to `FR-API-031` in production.
