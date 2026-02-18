# Functional Specifications - Attempt Detail Analytics

## 1. Purpose

This page displays one selected job attempt in full detail.

## 2. Scope

Included:

- Attempt-level metadata and runtime context.
- Event and exception details linked to the attempt.
- Timeline visualization for the attempt execution.

Excluded:

- Retry/requeue mutation actions.
- Cross-job aggregates (handled in Jobs and Job Detail pages).

## 3. Inherited Requirements

- `FR-AN-JDETAIL-016`: navigation comes from Job Detail attempt rows with context preserved.
- `FR-AN-REQ-JOBS-017`: consistent status color mapping for attempt status.

## 4. Header and Context

- `FR-AN-ADETAIL-001`: Breadcrumb is `Jobs / <JOB_CLASS_NAME>`.
- `FR-AN-ADETAIL-002`: Main title is the selected job class/name.
- `FR-AN-ADETAIL-003`: A contextual issue indicator/control can be shown in the header area when issue linkage exists.
- `FR-AN-ADETAIL-004`: Header context preserves project, environment, user filter, period, and selected job/attempt IDs.

## 5. Info Card

- `FR-AN-ADETAIL-005`: The top card is titled `Info`.
- `FR-AN-ADETAIL-006`: Info rows include at minimum: `status`, `queued_at`, `date`, `connection`, `queue`, `peak_memory`, and `server` when available.
- `FR-AN-ADETAIL-007`: Attempt `status` is displayed as a colored badge.
- `FR-AN-ADETAIL-008`: A `User` subsection is included with `username` or equivalent identity when available.

## 6. Events and Exceptions

- `FR-AN-ADETAIL-009`: An `Events` subsection lists counters for related telemetry types:
  - `queries`
  - `mail`
  - `cache`
  - `outgoing_requests`
  - `notifications`
  - `queued_jobs`.
- `FR-AN-ADETAIL-010`: Events section includes summary chips for total events and total attempt duration.
- `FR-AN-ADETAIL-011`: Exception section is shown when at least one exception is linked to the attempt.
- `FR-AN-ADETAIL-012`: Each exception item shows handled/unhandled status badge, exception location/class context, and message preview.
- `FR-AN-ADETAIL-013`: Exception item supports a view/expand action for full details.

## 7. Timeline

- `FR-AN-ADETAIL-014`: A `Timeline` section visualizes ordered execution spans for the selected attempt.
- `FR-AN-ADETAIL-015`: Timeline includes the main attempt span and child spans when available (`query`, `exception`, and other linked spans).
- `FR-AN-ADETAIL-016`: Timeline rows display stage label and duration.
- `FR-AN-ADETAIL-017`: Timeline supports horizontal navigation/zoom for dense spans.
- `FR-AN-ADETAIL-018`: Clicking a timeline event can open corresponding analytics detail when linked data exists.

## 8. Navigation and Edge Cases

- `FR-AN-ADETAIL-019`: Back navigation returns to Job Detail with preserved filters/context.
- `FR-AN-ADETAIL-020`: If the selected attempt cannot be resolved, the page falls back to Job Detail with guidance.


## Technical Specifications

See dedicated technical specification: [attempt-detail-technical.md](./attempt-detail-technical.md)
