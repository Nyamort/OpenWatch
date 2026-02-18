# Functional Specifications - Command Run Detail Analytics

## 1. Purpose

This page displays one selected command execution in full detail.

## 2. Scope

Included:

- Run-focused drilldown from Command Detail execution table.
- Exception/code preview, run metadata, related events, logs, and timeline.
- Run-level context with preserved command scope.

Excluded:

- Retry/rerun mutation actions from analytics UI.
- Aggregate command trend analysis (handled in Commands and Command Detail pages).

## 3. Inherited Requirements

- `FR-AN-CDETAIL-015`: navigation comes from Command Detail rows with context preserved.
- `FR-AN-REQ-CMD-003`: status color semantics remain consistent.

## 4. Header and Context

- `FR-AN-CRUN-001`: Breadcrumb is `Commands / <COMMAND_NAME>`.
- `FR-AN-CRUN-002`: Main title is the selected command name.
- `FR-AN-CRUN-003`: Header can expose issue-creation affordance for problematic runs.
- `FR-AN-CRUN-004`: Run status badge is displayed near the top and supports failure states (example: `UNHANDLED`).

## 5. Exception and Code Preview

- `FR-AN-CRUN-005`: When an exception exists, an `Exception` section is shown above run metadata.
- `FR-AN-CRUN-006`: Exception section includes source file/location context and code excerpt.
- `FR-AN-CRUN-007`: Code excerpt supports line markers and highlighted fault line.
- `FR-AN-CRUN-008`: Exception section can show collapsed/expandable stack frames count.

## 6. Info and Events

- `FR-AN-CRUN-009`: An `Info` section shows run metadata at minimum: `exit code`, `date`, `peak memory`, `server`.
- `FR-AN-CRUN-010`: An `Events` section shows counters for linked telemetry types:
  - `queries`
  - `mail`
  - `cache`
  - `outgoing_requests`
  - `notifications`
  - `queued_jobs`.
- `FR-AN-CRUN-011`: Events section includes summary chips for total events and run duration.

## 7. Related Errors and Logs

- `FR-AN-CRUN-012`: A related exceptions list section is shown when one or more exceptions are attached.
- `FR-AN-CRUN-013`: Exception list items include status, source location, and view/expand affordance.
- `FR-AN-CRUN-014`: A related logs section is shown for run-scoped logs with timestamp and level badge.

## 8. Timeline

- `FR-AN-CRUN-015`: A `Timeline` section visualizes ordered run spans for the selected execution.
- `FR-AN-CRUN-016`: Timeline includes main command span and child spans such as `bootstrap`, `exception`, and `terminating` when available.
- `FR-AN-CRUN-017`: Timeline rows display stage label and duration.
- `FR-AN-CRUN-018`: Timeline supports horizontal navigation/scroll for dense spans.

## 9. Navigation and Edge Cases

- `FR-AN-CRUN-019`: Back navigation returns to Command Detail with preserved filters/context.
- `FR-AN-CRUN-020`: If selected run cannot be resolved, the page falls back to Command Detail with guidance.


## Technical Specifications

See dedicated technical specification: [command-run-detail-technical.md](./command-run-detail-technical.md)
