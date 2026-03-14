# Task T-013: Payload Record Type Validation
- Domain: `api`
- Status: `not started`
- Priority: `P1`
- Dependencies: `T-012`, `T-030`

## Description
Build per-type schema validation for the 13 supported record types (`request`, `query`, `cache-event`, `command`, `log`, `notification`, `mail`, `queued-job`, `job-attempt`, `scheduled-task`, `outgoing-request`, `exception`, `user`). Mandatory keys must be present (empty value allowed). Unknown `t` values are rejected with `400`.

## How to implement
1. Create a `RecordValidatorRegistry` that maps each `t` value to a dedicated validator class.
2. Implement a base `RecordValidator` with shared required fields: `v`, `t`, `timestamp`, `deploy`, `server`, and at least one of `_group` or `trace_id`.
3. Implement per-type validator extending the base, declaring the additional required keys per spec (e.g., `RequestRecordValidator`, `QueryRecordValidator`, etc.).
4. In `ProcessTelemetryBatch` job: iterate records, validate each via the registry, collect failures, reject the batch or skip invalid records per policy.
5. Unknown `t` value → `400` with `{ message, refresh_in }` backoff hint.
6. Persist validated records via `TelemetryRepository` (T-030), fanout to correct extraction table.
7. Write feature tests: each of the 13 record types with all required fields → accepted; each type with a missing required field → rejected; unknown `t` → 400.

## Key files to create or modify
- `app/Services/Ingestion/RecordValidatorRegistry.php`
- `app/Services/Ingestion/Validators/BaseRecordValidator.php`
- `app/Services/Ingestion/Validators/RequestRecordValidator.php`
- `app/Services/Ingestion/Validators/QueryRecordValidator.php`
- `app/Services/Ingestion/Validators/ExceptionRecordValidator.php`
- *(one file per record type)*
- `app/Jobs/ProcessTelemetryBatch.php` — integrate registry
- `tests/Feature/Api/PayloadValidationTest.php`

## Acceptance criteria
- [ ] All 13 record types with complete required fields are accepted and persisted
- [ ] A record missing any required field is rejected with a descriptive error
- [ ] An unknown `t` value returns `400` with `message` and `refresh_in`
- [ ] Base required fields (`v`, `t`, `timestamp`, `deploy`, `server`, `_group`/`trace_id`) are validated on every type
- [ ] A mandatory key present with an empty value (`""`, `null`, `[]`) is accepted
- [ ] Each valid record fans out to the correct extraction table

## Related specs
- [Functional spec](../specs.md) — `FR-API-033` to `FR-API-049`
- [Technical spec](../specs-technical.md)
