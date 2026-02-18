# Technical Specifications - Command Analytics

## 1. Data model and indexed fields

Source: `telemetry_records` where `type = command`, enriched via `extraction_command` read model.

Extracted columns in `extraction_command`:
- `record_id`, `organization_id`, `project_id`, `environment_id`, `ts_utc`
- `command_class` → fully qualified class name (e.g. `App\Console\Commands\ImportProducts`)
- `command_name` → Artisan command signature (e.g. `import:products`)
- `command_string` → full command with arguments/options (for display)
- `status` → `success` | `failed` | `pending` (derived from `exit_code`)
- `exit_code` → integer exit code (0 = success, non-zero = failed)
- `duration_ms` → total command execution time in milliseconds
- `trace_id` → for correlation with queries/exceptions spawned during command

Status derivation: `exit_code = 0` → `success`; `exit_code IS NULL` → `pending` (command started but not yet finished); `exit_code != 0` → `failed`.

## 2. Aggregation strategy

The list endpoint returns **command-name-level grouped rows**:

```sql
SELECT
  command_name,
  command_class,
  COUNT(*) FILTER (WHERE status = 'success') AS success_count,
  COUNT(*) FILTER (WHERE status = 'failed') AS failed_count,
  COUNT(*) AS total,
  AVG(duration_ms) AS avg_ms,
  PERCENTILE_CONT(0.95) WITHIN GROUP (ORDER BY duration_ms) AS p95_ms,
  MAX(ts_utc) AS last_seen
FROM extraction_command
WHERE organization_id = ? AND project_id = ? AND environment_id = ?
  AND ts_utc BETWEEN ? AND ?
GROUP BY command_name, command_class
ORDER BY last_seen DESC
```

Header cards:
- Left: total runs + `success_count` + `failed_count`.
- Right: duration range + `avg_ms` + `p95_ms`.

Chart series: status counts per bucket (success/failed) + avg/p95 duration per bucket. Failed metrics styled in error red.

## 3. Status tracking

Status filter modes: `all` | `success` | `failed`.

The `pending` status (command started, exit code not yet recorded) is only relevant for long-running commands. It is included in `total` count but excluded from `success` and `failed` counts.

Search applies to `command_name` and `command_class` fields (ILIKE).

## 4. API contracts

```
GET /analytics/commands
```

Query params: `period`, `from`, `to`, `status`, `search`, `page`, `sort` (total | success | failed | avg | p95 | last_seen), `direction`.

`rows` entry shape:
```json
{
  "command_name": "import:products",
  "command_class": "App\\Console\\Commands\\ImportProducts",
  "success_count": 48,
  "failed_count": 2,
  "total": 50,
  "avg_ms": 12400,
  "p95_ms": 48000,
  "last_seen": "2026-03-14T12:00:00Z"
}
```

```
GET /analytics/commands/{command_name}
```

Returns command detail: individual run records for this command, paginated. Shows `ts_utc`, `command_string`, `exit_code`, `status`, `duration_ms`. Supports `status` filter. Each row links to a run detail page with full context (queries, exceptions, logs spawned by the command).

## 5. Index strategy

```sql
-- Primary grouping index
CREATE INDEX idx_ext_command_name_scope
  ON extraction_command (organization_id, project_id, environment_id, command_name, ts_utc DESC);

-- Status filter index
CREATE INDEX idx_ext_command_status
  ON extraction_command (project_id, environment_id, status, ts_utc DESC);

-- Failed filter (partial index)
CREATE INDEX idx_ext_command_failed
  ON extraction_command (project_id, environment_id, ts_utc DESC)
  WHERE status = 'failed';

-- Trace correlation
CREATE INDEX idx_ext_command_trace
  ON extraction_command (project_id, environment_id, trace_id);
```

## 6. Security and tenant isolation

- All queries scoped by `organization_id + project_id + environment_id`.
- Command string may contain sensitive arguments — full `command_string` shown in detail view only; list shows `command_name` only.
- Trace correlation (to queries, exceptions, logs) stays within organization scope.
- Cross-organization access denied.

## 7. Test strategy

Key feature tests:
- Command list groups multiple runs of same `command_name` into one row with correct counts.
- `status = failed` filter returns only commands with `failed_count > 0`.
- Sort by `failed_count` orders rows correctly; failed metrics styled in error class in response metadata.
- Command detail returns individual run records (not grouped), paginated.
- Pending status command (no exit code yet) counted in total but not in success/failed.
- Cross-organization access denied.
- Trace ID link from command run to related exceptions/queries resolves within same org.

## Related Resources

- **Functional Spec**: [command.md](./command.md)
- **Detail Pages**: [command-detail-technical.md](./command-detail-technical.md), [command-run-detail-technical.md](./command-run-detail-technical.md)
- **Parent Spec**: [analytics/specs.md](../specs.md)
