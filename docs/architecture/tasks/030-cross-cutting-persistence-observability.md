# Task T-030: Persistence, Partitioning, and Observability Infrastructure
- Domain: `cross-cutting`
- Status: `completed`
- Priority: `P0`
- Dependencies: `T-029`

## Description
Create the foundational persistence layer for telemetry: the append-only `telemetry_records` table (partitioned by month), per-type extraction tables, covering indexes for time-window and tenant queries, and retention/anonymization jobs. Also adds structured request tracing IDs propagated through all ingest jobs.

## How to implement
1. Create `telemetry_records` migration: `id`, `organization_id`, `project_id`, `environment_id`, `record_type` (enum of 13 types), `trace_id`, `group_key`, `execution_id`, `payload` (JSONB), `recorded_at` (timestamptz). Partition by month on `recorded_at`.
2. Create per-type extraction tables (one per record type: `extraction_requests`, `extraction_queries`, etc.) with typed columns matching the spec — these are the read models for analytics queries.
3. Add covering indexes: `(organization_id, project_id, environment_id, recorded_at DESC)` on `telemetry_records`; type-specific indexes on extraction tables per the technical spec.
4. Create `TelemetryRepository` service: append-only write (no update/delete), fan-out insert to the appropriate extraction table via a queued `ProcessTelemetryRecord` job.
5. Create retention jobs: `PurgeExpiredTelemetryRecords` (hard delete by policy), `AnonymizeStaleAuditRecords` (from T-008).
6. Add `X-Request-ID` / `trace_id` propagation: generated at request entry point, attached to all log lines and job dispatches.
7. Write feature tests: telemetry record insert fans out to the correct extraction table, retention job deletes records past the window, partition boundaries are respected in queries.

## Key files to create or modify
- `database/migrations/xxxx_create_telemetry_records_table.php`
- `database/migrations/xxxx_create_extraction_requests_table.php` (and one per type)
- `app/Models/TelemetryRecord.php`
- `app/Services/Telemetry/TelemetryRepository.php`
- `app/Jobs/ProcessTelemetryRecord.php`
- `app/Jobs/PurgeExpiredTelemetryRecords.php`
- `app/Http/Middleware/AttachRequestTraceId.php`
- `bootstrap/app.php` — register trace ID middleware
- `tests/Feature/Telemetry/TelemetryPersistenceTest.php`

## Acceptance criteria
- [ ] A telemetry record insert is reflected in the corresponding extraction table after job processing
- [ ] No direct update or delete on `telemetry_records` is possible via the application layer
- [ ] Queries on `telemetry_records` for a given org/project/environment/period use the covering index (verified via `EXPLAIN`)
- [ ] Retention job deletes records older than the configured window
- [ ] All ingest jobs carry the originating `trace_id` in their context
- [ ] Partition by month is in place and queries respect partition pruning

## Related specs
- [Technical spec](../specs-technical.md)
