# Task T-012: Ingestion Endpoint — `POST {ingest_url}`
- Domain: `api`
- Status: `completed`
- Priority: `P0`
- Dependencies: `T-011`, `T-030`

## Description
Implement the compressed telemetry ingestion endpoint. It validates the session token (from T-011), enforces `Content-Encoding: gzip`, decompresses and parses the JSON payload, and hands off valid records to the async processing pipeline. Returns `{}` on success.

## How to implement
1. Register `POST /ingest` (or the configured `ingest_url` path) in the api-ingest route group.
2. Authenticate via session token from `Authorization: Bearer <session_token>`: lookup from Redis, check TTL, bind environment context.
3. Reject missing/invalid session token with `401`.
4. Validate `Content-Encoding: gzip` header; reject non-gzip payloads with `415`.
5. Decompress payload (streaming where possible); reject non-JSON or malformed JSON with `400`.
6. Dispatch `ProcessTelemetryBatch` job with the raw records array and environment context.
7. Return `200` + `{}` synchronously after successful dispatch.
8. On quota stop (checked via `QuotaService` from T-007): return `403` + `{ stop: true, message, refresh_in: 900 }`.
9. Write feature tests: valid gzip JSON → 200 + `{}`, non-gzip → 415, invalid JSON → 400, missing session token → 401, quota stop → 403 with stop contract.

## Key files to create or modify
- `app/Http/Controllers/Api/IngestController.php`
- `app/Http/Middleware/ValidateGzipEncoding.php`
- `app/Http/Middleware/AuthenticateSessionToken.php`
- `app/Jobs/ProcessTelemetryBatch.php`
- `routes/api.php` — ingest route
- `tests/Feature/Api/IngestEndpointTest.php`

## Acceptance criteria
- [ ] Valid gzip-compressed JSON payload returns `200` + `{}`
- [ ] Request without `Content-Encoding: gzip` returns `415`
- [ ] Malformed or non-JSON payload after decompression returns `400`
- [ ] Invalid or expired session token returns `401`
- [ ] Quota stop returns `403` with `{ stop: true, message, refresh_in }` body
- [ ] Valid payloads are handed to the async job and not processed synchronously in the HTTP cycle
- [ ] Response time under normal load stays within SLA (< 200ms p95)

## Related specs
- [Functional spec](../specs.md) — `FR-API-020` to `FR-API-031`
- [Technical spec](../specs-technical.md)
