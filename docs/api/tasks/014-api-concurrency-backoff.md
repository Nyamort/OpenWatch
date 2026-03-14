# Task T-014: Concurrency Limits and Backoff Contract
- Domain: `api`
- Status: `not started`
- Priority: `P0`
- Dependencies: `T-011`, `T-012`

## Description
Enforce a maximum of 2 concurrent ingest requests per agent identity (session token + environment). A third concurrent request is rejected immediately with `429` and the stop contract. Implement a Redis distributed semaphore with TTL for safe recovery on worker crash or timeout.

## How to implement
1. Implement `ConcurrencyLimiter` service backed by Redis: `acquire(string $key, int $max, int $ttl): bool` and `release(string $key): void`.
2. Semaphore key: `ingest:concurrency:{environment_id}:{session_token_id}`.
3. In `IngestController` (T-012): call `acquire` before processing; if false → `429` + `{ stop: true, message, refresh_in }`.
4. On successful response (200 or 403 stop): always call `release` in a `finally` block.
5. Set semaphore TTL slightly above the max expected request duration to auto-release on worker crash.
6. Write feature tests: two concurrent requests proceed, third is rejected with `429` and correct body, semaphore is released after response, TTL causes auto-release after timeout.

## Key files to create or modify
- `app/Services/Ingestion/ConcurrencyLimiter.php`
- `app/Http/Controllers/Api/IngestController.php` — integrate limiter
- `tests/Feature/Api/ConcurrencyLimitTest.php`

## Acceptance criteria
- [ ] Two simultaneous ingest requests from the same agent are both accepted
- [ ] A third concurrent request returns `429` immediately with `{ stop: true, message, refresh_in }`
- [ ] The semaphore is released after every response, including error paths
- [ ] If a worker crashes mid-request, the semaphore auto-releases after TTL (no deadlock)
- [ ] Retry must wait at least `refresh_in` seconds before the next attempt

## Related specs
- [Functional spec](../specs.md) — `FR-API-024` to `FR-API-027`
- [Technical spec](../specs-technical.md)
