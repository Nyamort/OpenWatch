# Technical Specifications - Request Detail

## 1. Record retrieval
- Fetch one request event by immutable event identifier (`request_id` / trace pair + ts)
  and verify scope.
- Secondary query loads linked events by `trace_id`/`_group` for timeline and related sections.

## 2. Response payload
- Include method/path/status/server/duration/timestamps and optional linked artifacts.
- Include header/body sections with redaction policy applied before response serialization.
- Include event counters for related types (queries, notifications, mail, cache, etc.).

## 3. UI rendering
- Top breadcrumb and action links derive from current route context.
- Timeline component renders ordered stages with unavailable stages filtered out.
- Exception section supports expandable detail when structured trace exists.
