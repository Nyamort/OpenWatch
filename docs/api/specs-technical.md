# Technical Specifications - Agent Authentication API

## 1. Runtime architecture
- Separate public API auth layer and telemetry ingestion layer:
  - `AgentAuthController` handles short-lived session token issuance.
  - `IngestController` handles compressed ingestion, schema validation, and enqueue of record ingestion jobs.
- Use middleware for JSON body handling, CORS, and request-size protection.

## 2. Token model
- `NightwatchToken` (environment-scoped) stored hashed.
- `AgentSessionToken` table with:
  - `organization_id`, `project_id`, `environment_id`, hashed token, expiry_at, rotate/refreshed fields.
  - `ip_fingerprint`, `last_seen_at` for audit and abuse control.
- `agent-auth` response generated with TTL + jitter-safe refresh_in values.

## 3. Ingestion contract
- Enforce `Content-Encoding: gzip`; reject invalid compression with `415`.
- Decompress into a bounded stream and reject payloads above configured uncompressed limits.
- JSON parse with strict schema shape validation for base fields and record-specific validators (`query`, `request`, `exception`, etc.).
- Unknown `t` values -> `400` with actionable response and short retry metadata.

## 4. Rate and concurrency control
- Enforce max concurrent ingest requests per (environment token, agent identity) = 2.
- Implement token-bucket/redis lock with hard fail for 3rd concurrent request and `429` + backoff payload.
- Add exponential backoff metadata when project is explicitly throttled or under quota pressure.

## 5. Persistence and async processing
- Ingestion handler pushes to queue per record chunk with worker fan-out:
  - `StoreRawTelemetryJob` for append-only persistence.
  - `NormalizeTelemetryJob` for extracting indexed fields used in analytics.
- Use idempotency key built from `(token_id, ingest_window_start, payload_hash)` to avoid duplicate ingestion.

## 6. Security and audit
- Never log raw tokens or session material.
- Write audit events for all auth failures and throttle denials with actor_context (token metadata + source IP).
- Store failed auth reasons with codes: invalid token, revoked token, rate limit, schema error.

## 7. Test strategy (contract)
- Integration tests for `/api/agent-auth`, successful ingestion, and concurrent reject behavior with deterministic lock responses.
