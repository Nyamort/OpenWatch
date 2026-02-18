# Functional Specifications - Agent Authentication

## 1. Purpose

This document defines the functional requirements for the agent bootstrap flow before telemetry ingestion.

## 2. Scope

Included:

- `POST /api/agent-auth` for session bootstrap.
- `POST {ingest_url}` for telemetry ingestion batches.
- Validation and exchange of a Nightwatch environment token.
- Retry guidance and failure behavior.

Excluded:

- Internal ingestion transport internals (payload transport protocol details, retry transport algorithms).
- Project/environment CRUD APIs.
- Detailed internal ingestion processing rules and storage schema.

## 3. Conventions

- `NIGHTWATCH_TOKEN` is the long-lived environment token generated for an agent.
- `session token` is the short-lived credential returned by this endpoint.
- All responses are JSON objects.
- Time-based values are expressed in seconds unless explicitly stated.

## 4. Functional Requirements

## 4.1 `POST /api/agent-auth`

- `FR-API-001`: The request must include a valid `NIGHTWATCH_TOKEN` for the target environment in `Authorization: Bearer <NIGHTWATCH_TOKEN>`.
- `FR-API-002`: The request body is optional and may be empty.
- `FR-API-003`: The system validates the token against:
  - Active/valid scope (project/environment binding).
  - Revocation status.
  - Expiration window (if applicable).
- `FR-API-004`: On successful validation, the endpoint returns:
  - HTTP `200`.
  - JSON body containing:
    - `token`: session token.
    - `expires_in`: duration in seconds before expiry (example: `3600`).
    - `refresh_in`: duration in seconds before client should refresh session (example: `300`).
    - `ingest_url`: base URL used by the agent to send telemetry.
- `FR-API-005`: The returned short-lived token is valid only in the authenticated environment context.
- `FR-API-006`: The endpoint returns the same `ingest_url` for all currently healthy endpoints available to that environment context.

## 4.2 Success Response Schema

```json
{
  "token": "<short_lived_token>",
  "expires_in": 3600,
  "refresh_in": 300,
  "ingest_url": "https://api.example.com/ingest"
}
```

## 4.3 Error Handling

- `FR-API-007`: If token authentication or authorization fails, the request is rejected.
- `FR-API-008`: For token failures (missing/invalid/expired/revoked), the response includes:
  - `message` (human-readable reason).
  - `refresh_in`: minimum wait time before retry.
  - Use `401` for missing/invalid credentials; use `403` for revoked or explicitly denied tokens.
- `FR-API-009`: If requests are too frequent or abusive, the request is rejected with `429` and a new `refresh_in` value.
- `FR-API-010`: For unexpected failures, the request is rejected with `5xx` and `message`, with optional `refresh_in` when retry may help.
- `FR-API-011`: When `refresh_in` is returned, clients must not retry before the delay elapses.
- `FR-API-012`: Error responses never include `NIGHTWATCH_TOKEN` or raw token material.

## 4.4 Security and Operational Constraints

- `FR-API-013`: Failed attempts are auditable with source and reason information.
- `FR-API-014`: Repeated failed attempts can trigger stronger backoff or temporary refusal.

## 4.5 Validation Examples

- `FR-API-015`: A malformed request payload (non-JSON or malformed JSON body when present) is rejected with `400` and `message`.
- `FR-API-016`: A valid token returns the payload in section 4.2.
- `FR-API-017`: A missing token returns a rejection with message and `refresh_in` (default `60`).
- `FR-API-018`: An expired token returns a rejection with policy-based `refresh_in`.
- `FR-API-019`: A revoked token returns a clear rejection with `refresh_in` guidance.

## 4.6 `POST {ingest_url}`

- `FR-API-020`: The ingestion endpoint accepts compressed payload batches from an authenticated agent session.
  - Request must use `Content-Encoding: gzip`.
  - The uncompressed payload must be valid JSON.
  - If the payload is not properly gzipped, the endpoint rejects with `415`.
- `FR-API-021`: The endpoint is called with `POST` and requires the short-lived token returned by `/api/agent-auth` in `Authorization: Bearer <session_token>`.
- `FR-API-022`: On successful ingestion, the endpoint returns:
  - HTTP `200`.
  - An empty JSON object: `{}`.
- `FR-API-023`: If ingestion is blocked by quota limits or explicit platform stop, the response is:
  - HTTP `403`.
  - JSON body:
    ```json
    {
      "stop": true,
      "message": "<reason>",
      "refresh_in": 900
    }
    ```
- `FR-API-024`: The agent flow supports up to 2 concurrent ingestion requests per agent identity and environment context.
- `FR-API-025`: When a 3rd concurrent request is made, it is rejected immediately with `429`.
- `FR-API-026`: Rejected over-concurrent requests return the same backoff contract as stop responses, including `refresh_in`.
- `FR-API-027`: When `stop` is true, agents postpone next requests for at least `refresh_in` before retry.
- `FR-API-028`: Ingestion batches are associated with the token-bound environment context from which they were issued.

## 4.7 Validation Examples

- `FR-API-029`: A valid compressed payload batch is acknowledged as success.
- `FR-API-030`: A quota-stop response with `stop: true` is honored with backoff.
- `FR-API-031`: Concurrent bursts above the allowed limit are rejected with retry guidance and retried after backoff.

## 4.8 Example Payload (Uncompressed JSON)

- `FR-API-032`: Client submissions to `POST {ingest_url}` include a decompressed JSON body with the following shape before compression:

```json
{
  "records": [
    {
      "v": 1,
      "t": "query",
      "timestamp": 1771416364.645742,
      "deploy": "",
      "server": "codespaces-19bb90",
      "_group": "f980d625222a118ffdb51d18d1f35d57",
      "trace_id": "b5e4f270-c1f8-476f-bce3-af2b66e70baf",
      "execution_source": "request",
      "execution_id": "b5e4f270-c1f8-476f-bce3-af2b66e70baf",
      "execution_preview": "GET /",
      "execution_stage": "before_middleware",
      "user": "",
      "sql": "select * from \"sessions\" where \"id\" = ? limit 1",
      "duration": 500,
      "connection": "sqlite",
      "connection_type": "write"
    },
    {
      "v": 1,
      "t": "query",
      "timestamp": 1771416364.656113,
      "deploy": "",
      "server": "codespaces-19bb90",
      "_group": "242ba0d7e1031d847f29c99c84e7ffb4",
      "trace_id": "b5e4f270-c1f8-476f-bce3-af2b66e70baf",
      "execution_source": "request",
      "execution_id": "b5e4f270-c1f8-476f-bce3-af2b66e70baf",
      "execution_preview": "GET /",
      "execution_stage": "after_middleware",
      "user": "",
      "sql": "update \"sessions\" set \"payload\" = ?, \"last_activity\" = ? where \"id\" = ?",
      "duration": 3520,
      "connection": "sqlite",
      "connection_type": "write"
    },
    {
      "v": 1,
      "t": "request",
      "timestamp": 1771416364.556714,
      "deploy": "",
      "server": "codespaces-19bb90",
      "_group": "8b6fdc8b80cb49821087d0c09b0075b4",
      "trace_id": "b5e4f270-c1f8-476f-bce3-af2b66e70baf",
      "user": "",
      "method": "GET",
      "url": "http://localhost:8000/",
      "status_code": 200,
      "duration": 105137,
      "route_methods": [
        "GET",
        "HEAD"
      ],
      "route_name": "",
      "route_domain": "",
      "route_path": "/",
      "route_action": "Closure",
      "ip": "127.0.0.1"
    }
  ]
}
```

## 4.9 Supported Record Types

- `FR-API-033`: Each item in `records` uses a `t` value from the supported event types:
  - `request`
  - `query`
  - `cache-event`
  - `command`
  - `log`
  - `notification`
  - `mail`
  - `queued-job`
  - `job-attempt`
  - `scheduled-task`
  - `outgoing-request`
  - `exception`
  - `user`
- `FR-API-034`: For each record type, payload shape follows the event domain.
  - All fields listed for the corresponding type are mandatory keys.
  - A mandatory key may carry an empty value when data is unavailable (`""`, `null`, `[]`, etc.).
- `FR-API-035`: Unknown `t` values are rejected with `400` and an error guidance flow (message + backoff if applicable).
- `FR-API-036`: Base record fields are required for all types:
  - `v` (schema version)
  - `t` (type)
  - `timestamp`
  - `deploy`
  - `server`
  - and an event/environment correlation reference (`_group` or `trace_id`, at least one required)
  - Empty values are accepted for optional telemetry contexts, but the keys must be present.
- `FR-API-037`: `user` records must include all required fields:
  - `id`
  - `name`
  - `username`
- `FR-API-038`: `scheduled-task` records must include all required fields:
  - `name`
  - `cron`
  - `timezone`
  - `without_overlapping`
  - `on_one_server`
  - `run_in_background`
  - `even_in_maintenance_mode`
  - `status`
  - `duration`
  - `exceptions`
  - `logs`
  - `queries`
  - `lazy_loads`
  - `jobs_queued`
  - `mail`
  - `notifications`
  - `outgoing_requests`
  - `files_read`
  - `files_written`
  - `cache_events`
  - `hydrated_models`
  - `peak_memory_usage`
  - `exception_preview`
  - `context`
- `FR-API-039`: `request` records must include all required fields:
  - `user`
  - `method`
  - `url`
  - `route_name`
  - `route_methods`
  - `route_domain`
  - `route_path`
  - `route_action`
  - `ip`
  - `duration`
  - `status_code`
  - `request_size`
  - `response_size`
  - `bootstrap`
  - `before_middleware`
  - `action`
  - `render`
  - `after_middleware`
  - `sending`
  - `terminating`
  - `exceptions`
  - `logs`
  - `queries`
  - `lazy_loads`
  - `jobs_queued`
  - `mail`
  - `notifications`
  - `outgoing_requests`
  - `files_read`
  - `files_written`
  - `cache_events`
  - `hydrated_models`
  - `peak_memory_usage`
  - `exception_preview`
  - `context`
- `FR-API-040`: `queued-job` records must include all required fields:
  - `execution_source`
  - `execution_id`
  - `execution_preview`
  - `execution_stage`
  - `user`
  - `job_id`
  - `name`
  - `connection`
  - `queue`
  - `duration`
- `FR-API-041`: `exception` records must include all required fields:
  - `execution_source`
  - `execution_id`
  - `execution_preview`
  - `execution_stage`
  - `user`
  - `class`
  - `file`
  - `line`
  - `message`
  - `code`
  - `trace`
  - `handled`
  - `php_version`
  - `laravel_version`
- `FR-API-042`: `outgoing-request` records must include all required fields:
  - `execution_source`
  - `execution_id`
  - `execution_preview`
  - `execution_stage`
  - `user`
  - `host`
  - `method`
  - `url`
  - `duration`
  - `request_size`
  - `response_size`
  - `status_code`
- `FR-API-043`: `job-attempt` records must include all required fields:
  - `user`
  - `job_id`
  - `attempt_id`
  - `attempt`
  - `name`
  - `connection`
  - `queue`
  - `status`
  - `duration`
  - `exceptions`
  - `logs`
  - `queries`
  - `lazy_loads`
  - `jobs_queued`
  - `mail`
  - `notifications`
  - `outgoing_requests`
  - `files_read`
  - `files_written`
  - `cache_events`
  - `hydrated_models`
  - `peak_memory_usage`
  - `exception_preview`
  - `context`
- `FR-API-044`: `mail` records must include all required fields:
  - `execution_source`
  - `execution_id`
  - `execution_preview`
  - `execution_stage`
  - `user`
  - `mailer`
  - `class`
  - `subject`
  - `to`
  - `cc`
  - `bcc`
  - `attachments`
  - `duration`
  - `failed`
- `FR-API-045`: `notification` records must include all required fields:
  - `execution_source`
  - `execution_id`
  - `execution_preview`
  - `execution_stage`
  - `user`
  - `channel`
  - `class`
  - `duration`
  - `failed`
- `FR-API-046`: `log` records must include all required fields:
  - `trace_id`
  - `execution_source`
  - `execution_id`
  - `execution_preview`
  - `execution_stage`
  - `user`
  - `level`
  - `message`
  - `context`
  - `extra`
- `FR-API-047`: `command` records must include all required fields:
  - `trace_id`
  - `class`
  - `name`
  - `command`
  - `exit_code`
  - `duration`
  - `bootstrap`
  - `action`
  - `terminating`
  - `exceptions`
  - `logs`
  - `queries`
  - `lazy_loads`
  - `jobs_queued`
  - `mail`
  - `notifications`
  - `outgoing_requests`
  - `files_read`
  - `files_written`
  - `cache_events`
  - `hydrated_models`
  - `peak_memory_usage`
  - `exception_preview`
  - `context`
- `FR-API-048`: `cache-event` records must include all required fields:
  - `execution_source`
  - `execution_id`
  - `execution_preview`
  - `execution_stage`
  - `user`
  - `store`
  - `key`
  - `type`
  - `duration`
  - `ttl`
- `FR-API-049`: `query` records must include all required fields:
  - `execution_source`
  - `execution_id`
  - `execution_preview`
  - `execution_stage`
  - `user`
  - `sql`
  - `file`
  - `line`
  - `duration`
  - `connection`
  - `connection_type`

## 5. MVP Boundary

Required for MVP:

- `POST /api/agent-auth`.
- `POST {ingest_url}` with stop/refresh contract.
- `NIGHTWATCH_TOKEN` validation.
- Success payload with `token`, `expires_in`, `refresh_in`, `ingest_url`.
- Backoff-aware error handling with `refresh_in`.

Can be deferred post-MVP:

- Fine-grained error taxonomies and machine-readable error `code`.
- Adaptive throttling per organization/environment.

## 6. API Notes

- `POST {ingest_url}` is resolved from the environment bootstrap response.

## 7. Acceptance Checklist

- Successful call with valid token returns the required success payload.
- Valid telemetry batch sends are accepted by `POST {ingest_url}`.
- Stop responses are honored and retried according to `refresh_in`.
- Missing/invalid tokens return a rejection response with optional `message` and retry guidance.
- Retry logic using `refresh_in` is consistent with documented behavior.
- Sensitive token values are never logged in plaintext.


## Technical Specifications

See dedicated technical specification: [specs-technical.md](./specs-technical.md)

## Related Resources

- **Technical Spec**: [specs-technical.md](./specs-technical.md)
- **Related Specs**: [projects/specs.md](../projects/specs.md), [analytics/specs.md](../analytics/specs.md)
- **Implementation Tasks**:
  - [011 - API Agent Auth](./tasks/011-api-agent-auth.md)
  - [012 - API Ingest Endpoint](./tasks/012-api-ingest-endpoint.md)
  - [013 - API Payload Types](./tasks/013-api-payload-types.md)
  - [014 - API Concurrency Backoff](./tasks/014-api-concurrency-backoff.md)
