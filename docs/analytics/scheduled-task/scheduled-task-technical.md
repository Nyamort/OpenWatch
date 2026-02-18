# Technical Specifications - Scheduled Task Analytics

## 1. Data model and indexed fields

Source: `telemetry_records` where `type = scheduled-task`, enriched via `extraction_scheduled_task` read model.

Extracted columns in `extraction_scheduled_task`:
- `record_id`, `organization_id`, `project_id`, `environment_id`, `ts_utc`
- `task_name` → display name of the scheduled task
- `task_command` → Artisan command or closure identifier
- `schedule_expression` → cron expression or frequency string (e.g. `* * * * *`, `everyMinute`)
- `status` → `processed` | `skipped` | `failed`
- `duration_ms` → execution time in milliseconds
- `started_at`, `ended_at` → precise execution timestamps
- `without_overlapping` → boolean flag (task has overlap protection)
- `on_one_server` → boolean flag (single-server execution)
- `trace_id` → for correlation with queries/exceptions spawned during task execution

Status derivation:
- `processed` → task ran successfully to completion
- `skipped` → task was due but skipped (overlap guard, maintenance mode, etc.)
- `failed` → task ran but threw exception or returned non-zero exit

## 2. Task identity

Tasks are grouped by `(task_command + schedule_expression)` to produce a stable identity key — this handles cases where the same command runs on multiple schedules (e.g. `cleanup:temp` running both `everyHour` and `daily`).

Search applies to `task_name` and `task_command` fields.

## 3. Aggregation strategy

The list endpoint returns **task-identity-level grouped rows**:

```sql
SELECT
  task_name,
  task_command,
  schedule_expression,
  COUNT(*) FILTER (WHERE status = 'processed') AS processed_count,
  COUNT(*) FILTER (WHERE status = 'skipped') AS skipped_count,
  COUNT(*) FILTER (WHERE status = 'failed') AS failed_count,
  COUNT(*) AS total,
  AVG(duration_ms) AS avg_ms,
  PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY duration_ms) AS p95_ms,
  MAX(ts_utc) AS last_seen
FROM extraction_scheduled_task
WHERE organization_id = ? AND project_id = ? AND environment_id = ?
  AND ts_utc BETWEEN ? AND ?
GROUP BY task_name, task_command, schedule_expression
ORDER BY last_seen DESC
```

Header cards:
- Left: total runs + `processed_count` + `skipped_count` + `failed_count`.
- Right: duration range + `avg_ms` + `p95_ms`.

Chart series: status counts per bucket + avg/p95 duration per bucket. Failed counts styled in error red.

## 4. API contracts

```
GET /analytics/scheduled-tasks
```

Query params: `period`, `from`, `to`, `status`, `search`, `page`, `sort` (total | processed | skipped | failed | avg | p95 | last_seen), `direction`.

`rows` entry shape:
```json
{
  "task_name": "Clean Up Temporary Files",
  "task_command": "cleanup:temp",
  "schedule_expression": "everyHour",
  "processed_count": 24,
  "skipped_count": 2,
  "failed_count": 1,
  "total": 27,
  "avg_ms": 3200,
  "p95_ms": 8400,
  "last_seen": "2026-03-14T12:00:00Z"
}
```

```
GET /analytics/scheduled-tasks/{task_identity}
```

Returns task detail: individual run records, paginated. Supports `status` filter. Each row links to a run detail page with full execution context (queries, exceptions, logs).

## 5. Index strategy

```sql
-- Primary grouping index
CREATE INDEX idx_ext_stask_command_scope
  ON extraction_scheduled_task (organization_id, project_id, environment_id, task_command, ts_utc DESC);

-- Status filter index
CREATE INDEX idx_ext_stask_status
  ON extraction_scheduled_task (project_id, environment_id, status, ts_utc DESC);

-- Failed partial index
CREATE INDEX idx_ext_stask_failed
  ON extraction_scheduled_task (project_id, environment_id, ts_utc DESC)
  WHERE status = 'failed';

-- Trace correlation
CREATE INDEX idx_ext_stask_trace
  ON extraction_scheduled_task (project_id, environment_id, trace_id);
```

## 6. Security and tenant isolation

- All queries scoped by `organization_id + project_id + environment_id`.
- Schedule expression and task command are metadata — not sensitive; safe to display in list.
- Trace correlation to exceptions/queries stays within organization scope.
- `without_overlapping` and `on_one_server` flags shown in detail view for debugging context.
- Cross-organization access denied.

## 7. Test strategy

Key feature tests:
- Task list groups multiple runs of same `(task_command + schedule_expression)` into one row.
- Same command on two different schedules produces two separate rows.
- `status = failed` filter returns only tasks with `failed_count > 0`.
- `status = skipped` filter correctly identifies skipped runs.
- Sort by `failed_count` orders rows correctly.
- Task detail returns individual run records (not grouped), paginated.
- Failed runs styled in error class in response config metadata.
- Cross-organization access denied.

## Related Resources

- **Functional Spec**: [scheduled-task.md](./scheduled-task.md)
- **Detail Pages**: [scheduled-task-detail-technical.md](./scheduled-task-detail-technical.md), [scheduled-task-run-detail-technical.md](./scheduled-task-run-detail-technical.md)
- **Parent Spec**: [analytics/specs.md](../specs.md)
