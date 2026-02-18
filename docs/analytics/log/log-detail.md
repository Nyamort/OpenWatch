# Functional Specifications - Log Detail Analytics

## 1. Purpose

This page displays one selected log event in full detail.

## 2. Scope

Included:

- Log-focused drilldown from the Logs feed.
- Source context, structured log context, and full message payload visibility.
- Expand/collapse behavior for large log context structures.

Excluded:

- Log mutation actions from analytics UI.
- Aggregated log feed controls (handled in Logs list page).

## 3. Inherited Requirements

- `FR-AN-REQ-LOG-007`: navigation comes from Logs feed rows with context preserved.
- `FR-AN-REQ-LOG-005`: log level styling remains consistent with feed badges.

## 4. Header and Source

- `FR-AN-LDETAIL-001`: Header displays log level badge and log message summary on a single line.
- `FR-AN-LDETAIL-002`: A `Source` card is displayed under the header.
- `FR-AN-LDETAIL-003`: Source card includes source-type badge (example: `REQUEST`) and a navigation affordance to open related source context when available.

## 5. Log Context

- `FR-AN-LDETAIL-004`: A `Log Context` section displays structured context as formatted JSON/object view.
- `FR-AN-LDETAIL-005`: Log context supports expand/collapse behavior and shows item count summary.
- `FR-AN-LDETAIL-006`: Nested objects/arrays are explorable in-place without leaving the page.
- `FR-AN-LDETAIL-007`: Context values include exception metadata when present (`class`, `message`, `code`, `file`, `trace`, etc.).
- `FR-AN-LDETAIL-008`: Long traces are rendered in scrollable form for readability.

## 6. Safety and Display

- `FR-AN-LDETAIL-009`: Sensitive values in context are redacted according to platform redaction policy.
- `FR-AN-LDETAIL-010`: File paths and stack frames are displayed as plain text unless explicit safe linking is enabled by policy.

## 7. Navigation and Edge Cases

- `FR-AN-LDETAIL-011`: Back navigation returns to Logs feed with preserved filters/context (`search`, level, user, period).
- `FR-AN-LDETAIL-012`: If selected log event is missing or expired, the page falls back to Logs feed with guidance.


## Technical Specifications

See dedicated technical specification: [log-detail-technical.md](./log-detail-technical.md)
