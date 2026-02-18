# Functional Specifications - Request Detail

## 1. Purpose

This page displays full details for one `request` record.

## 2. Scope

Included:

- Full, request-scoped details for a single `request` record.
- Route-level context (`Requests / <route_path>`) and request metadata.
- Header/body visibility, event correlation, and request lifecycle timeline.
- Navigation back to route-scoped and global request analytics views.
- Stable rendering across empty optional fields.

Excluded:

- Ability to mutate or replay requests.
- Request replay/mitigation actions.

## 3. Inherited Requirements

- `FR-AN-023`: Row actions support navigation to raw-event detail view.
- `FR-AN-REQ-REQ-019`: Navigation preserves project/environment period context when coming from list/drilldown pages.

## 4. Page Content

- `FR-AN-RDETAIL-001`: The page breadcrumb is `Requests / <ROUTE_PATH>`, where `<ROUTE_PATH>` comes from the selected route context.
- `FR-AN-RDETAIL-002`: The page title is a method badge plus route path text (example: `[GET] /api/orders/{id}`).
- `FR-AN-RDETAIL-003`: The page displays a top card with a header containing the method badge and absolute request path.
- `FR-AN-RDETAIL-004`: The top card body displays request core metadata rows at minimum: `date`, `status_code`, `server`, `response_size`, and `peak_memory_usage`.
- `FR-AN-RDETAIL-005`: `status_code` is rendered as a visual badge using status-class styling (`2xx` success, `4xx` warning, `5xx` error).
- `FR-AN-RDETAIL-006`: The top card includes a `User` section with `ip` as mandatory when present in source record; other user identity fields are optional.
- `FR-AN-RDETAIL-007`: The top card includes an `Events` section showing linked telemetry counters for:
  - `queries`
  - `mail`
  - `cache`
  - `outgoing_requests`
  - `notifications`
  - `queued_jobs`.
- `FR-AN-RDETAIL-008`: The `Events` section header includes summary chips for total events count and request duration.
- `FR-AN-RDETAIL-009`: An `Exception` section is shown when one or more exceptions are linked to the request.
- `FR-AN-RDETAIL-010`: Each exception item shows handled/unhandled status badge, exception class/location context, and message preview.
- `FR-AN-RDETAIL-011`: Exception item supports view/expand action for full details.
- `FR-AN-RDETAIL-012`: The request `headers` are shown in a second card that is collapsible and visible by default.
- `FR-AN-RDETAIL-013`: The headers card supports collapsing/expanding while preserving redaction policy for sensitive values.
- `FR-AN-RDETAIL-014`: The page includes a `Timeline` section that renders request lifecycle stages with horizontal duration bars.
- `FR-AN-RDETAIL-015`: Timeline stages include request total and sub-stages when present (for example: `bootstrap`, `middleware`, `controller/action`, `render`, `sending`, `terminating`).
- `FR-AN-RDETAIL-016`: Each timeline row shows stage label and duration; unavailable stages are hidden rather than rendered as zero-only placeholders.
- `FR-AN-RDETAIL-017`: If `trace_id` or `_group` exists, event links point to correlated analytics pages in the same project/environment context.
- `FR-AN-RDETAIL-018`: If a route context is available, details page links back to that specific route-scoped analytics view; otherwise it links to global request analytics.
- `FR-AN-RDETAIL-019`: The page may expose request body sections when payload data is available, with redaction rules applied.

## 5. Navigation

- `FR-AN-RDETAIL-020`: A back link and breadcrumb navigation preserve context (`project`, `environment`, period, user filter, and route filter when present).


## Technical Specifications

See dedicated technical specification: [request-detail-technical.md](./request-detail-technical.md)
