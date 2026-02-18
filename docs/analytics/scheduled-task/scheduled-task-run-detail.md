# Functional Specifications - Scheduled Task Run Detail Analytics

## 1. Purpose

This page displays one selected scheduled-task run in full detail.

## 2. Scope

Included:

- Run-focused drilldown from Scheduled Task Detail table.
- Exception/code preview, run metadata, related events, and timeline.
- Run-level context with preserved task scope.

Excluded:

- Retry/rerun mutation actions from analytics UI.
- Aggregate task trend analysis (handled in Scheduled Tasks and Scheduled Task Detail pages).

## 3. Inherited Requirements

- `FR-AN-STDETAIL-017`: navigation comes from Scheduled Task Detail rows with context preserved.
- `FR-AN-REQ-TASK-003`: status color semantics remain consistent.

## 4. Header and Context

- `FR-AN-STRUN-001`: Breadcrumb is `Scheduled Tasks / <TASK_NAME>`.
- `FR-AN-STRUN-002`: Main title is selected task command/name.
- `FR-AN-STRUN-003`: Header may expose issue-creation affordance for problematic runs.
- `FR-AN-STRUN-004`: Run status badge is displayed near the top and supports failure states (example: `UNHANDLED`).

## 5. Exception and Stack Preview

- `FR-AN-STRUN-005`: When an exception exists, an `Exception` section is shown above run metadata.
- `FR-AN-STRUN-006`: Exception section includes message and file location context.
- `FR-AN-STRUN-007`: Exception section includes expandable stack frames list.
- `FR-AN-STRUN-008`: Stack frame list supports in-place expansion and collapse.

## 6. Info and Events

- `FR-AN-STRUN-009`: An `Info` section shows run metadata at minimum: `date`, `status`, `peak memory`, `server`.
- `FR-AN-STRUN-010`: Info section can display scheduler flags when available (`without overlapping`, `on one server`, `run in background`, `even in maintenance mode`).
- `FR-AN-STRUN-011`: An `Events` section shows counters for linked telemetry types:
  - `queries`
  - `mail`
  - `cache`
  - `outgoing_requests`
  - `notifications`
  - `queued_jobs`.
- `FR-AN-STRUN-012`: Events section includes summary chips for total events and run duration.

## 7. Related Errors and Timeline

- `FR-AN-STRUN-013`: A related exceptions list section is shown when one or more exceptions are attached.
- `FR-AN-STRUN-014`: Exception list items include status, source location, and view/expand affordance.
- `FR-AN-STRUN-015`: A `Timeline` section visualizes ordered run spans for the selected execution.
- `FR-AN-STRUN-016`: Timeline includes main task span and child spans such as `bootstrap`, `exception`, and `terminating` when available.
- `FR-AN-STRUN-017`: Timeline rows display stage label and duration.
- `FR-AN-STRUN-018`: Timeline supports horizontal navigation/scroll for dense spans.

## 8. Navigation and Edge Cases

- `FR-AN-STRUN-019`: Back navigation returns to Scheduled Task Detail with preserved filters/context.
- `FR-AN-STRUN-020`: If selected run cannot be resolved, the page falls back to Scheduled Task Detail with guidance.


## Technical Specifications

See dedicated technical specification: [scheduled-task-run-detail-technical.md](./scheduled-task-run-detail-technical.md)
