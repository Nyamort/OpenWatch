# Functional Specifications - Jobs Analytics

## 1. Purpose

This page analyzes both `queued-job` and `job-attempt` records for a selected project and environment.

## 2. Scope

Included:

- Unified analytics page named `Jobs` for two record types: `queued-job` and `job-attempt`.
- Outcome, retry, and duration visibility across queued processing lifecycle.
- Grouping/filtering by queue runtime dimensions.

## 3. Inherited Base Requirements

- `FR-AN-010`: record type label in top-left area.
- `FR-AN-011`: period presets in top-right area (`1h`, `24h`, `7d`, `14d`, `30d`, `custom`).
- `FR-AN-012`: default period is `24h`.
- `FR-AN-020`: paginated and sortable records list.
- `FR-AN-021`: default sorting by `timestamp` descending.
- `FR-AN-023`: row-to-detail drill-through.

## 4. Page-Specific Requirements

- `FR-AN-REQ-JOBS-001`: The page presents both `queued-job` and `job-attempt` records in one dataset view.
- `FR-AN-REQ-JOBS-002`: Users can filter by record subtype (`queued-job`, `job-attempt`, or all).
- `FR-AN-REQ-JOBS-003`: The page shows outcome and failure trend for selected period.
- `FR-AN-REQ-JOBS-004`: The page supports grouping by `job_id` and job `name`.
- `FR-AN-REQ-JOBS-005`: The page supports filtering by `connection` and `queue`.
- `FR-AN-REQ-JOBS-006`: For `job-attempt` records, grouping/filtering supports retry stage (`attempt`, `attempt_id`).
- `FR-AN-REQ-JOBS-007`: Rows include shared fields when available: `name`, `job_id`, `connection`, `queue`, `status`, `duration`.
- `FR-AN-REQ-JOBS-008`: Rows include attempt-specific fields when the record type is `job-attempt`: `attempt`, `attempt_id`.
- `FR-AN-REQ-JOBS-009`: The top-right control bar includes user selector and period presets (`1H`, `24H`, `7D`, `14D`, `30D`, custom range control).
- `FR-AN-REQ-JOBS-010`: The page header metric cards are split into:
  - left card: attempts and status-class counters.
  - right card: duration summary and latency indicators.
- `FR-AN-REQ-JOBS-011`: Left card headline metric is total attempts for active filters.
- `FR-AN-REQ-JOBS-012`: Left card status counters include `failed`, `processed`, and `released`.
- `FR-AN-REQ-JOBS-013`: Left chart tooltip includes bucket timestamp, `processed`, `released`, `failed`, and `total`.
- `FR-AN-REQ-JOBS-014`: Right card headline metric shows duration range (`min-max`) for active filters.
- `FR-AN-REQ-JOBS-015`: Right card summary shows `avg` and `p95` duration values.
- `FR-AN-REQ-JOBS-016`: Right chart tooltip includes bucket timestamp and both `avg` and `p95` values.
- `FR-AN-REQ-JOBS-017`: Status colors are consistent across cards/charts/tooltips (`failed` in error color, `processed` in neutral color, `released` in warning color).
- `FR-AN-REQ-JOBS-018`: A jobs section header shows total unique jobs count for active filters (example: `1 Job`).
- `FR-AN-REQ-JOBS-019`: A `Search jobs` input filters rows by job class/name text.
- `FR-AN-REQ-JOBS-020`: Jobs table columns are: `jobs`, `queued`, `processed`, `released`, `failed`, `total`, `avg`, `p95`, `action`.
- `FR-AN-REQ-JOBS-021`: Table supports sortable aggregate columns (`queued`, `processed`, `released`, `failed`, `total`, `avg`, `p95`).
- `FR-AN-REQ-JOBS-022`: `jobs` column displays the job class/name and optional job icon.
- `FR-AN-REQ-JOBS-023`: Action column provides drilldown to [`job-detail`](./job-detail.md) while preserving active filters.
- `FR-AN-REQ-JOBS-024`: Empty-state behavior is explicit when no jobs match active filters or search term.


## Technical Specifications

See dedicated technical specification: [jobs-technical.md](./jobs-technical.md)
