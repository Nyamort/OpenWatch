# Task T-030: Persistence, Aggregation, Observability, and Audit Infrastructure
- Domain: Cross-cutting + Analytics + Organization
- Status: `not started`
- Priority: `P0`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Define production-grade persistence architecture (PostgreSQL), ingestion tables, indexes/partitions, dashboard snapshots, and audit/event observability.

## How to execute
1. Finalize PostgreSQL schema migration plan for canonical telemetry + audit + read model tables.
2. Add indexes for time-window filters and tenant/record-type joins.
3. Add table partitioning strategy and retention tasks.
4. Add audit event bus and dashboard snapshot/materialized view refresh jobs.
5. Implement structured logs redaction hooks and request tracing IDs.

## Architecture implications
- **Storage**: immutable `raw_telemetry_records`, per-type derived tables, audit partitions.
- **Performance**: materialized or precomputed aggregates for dashboard and shared analytics cards.
- **Operations**: vacuum/archival jobs and query tuning.
- **Governance**: retention and anonymization hooks for policy-driven cleanup.

## Acceptance checkpoints
- Tenant isolation visible in indexes and query plans.
- Audit and operational telemetry available for troubleshooting and compliance.

## Done criteria
- Cross-cutting requirements across all modules for `auth/project/api/alerts/issues/analytics` audit coverage and observability.
