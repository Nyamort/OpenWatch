# Task T-014: Concurrency Limits, Quotas and Backoff
- Domain: `api`
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Enforce maximum 2 concurrent ingest requests per agent/environment; reject overload with retry contract (`429` and stop semantics).

## How to execute
1. Add per-identity/environment concurrency counters in Redis keyed by session token.
2. Reject the 3rd concurrent request with standardized backoff payload.
3. On stop state, return explicit `stop: true` and `refresh_in` value.
4. Add idempotent decrement/release paths for worker crashes and timeouts.

## Architecture implications
- **Context**: API + Redis control plane.
- **Pattern**: distributed semaphore with TTL and safe recovery.
- **Resilience**: degrade gracefully with consistent payload shape.
- **Metrics**: alert on repeated limit hits.

## Acceptance checkpoints
- 3rd in-flight request is rejected immediately.
- Retry must respect refresh delay.

## Done criteria
- `FR-API-024` to `FR-API-027` implemented.
