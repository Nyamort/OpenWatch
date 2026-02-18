# Technical Specifications - Job Attempt Detail Analytics

## 1. Scope and retrieval
- Resolve attempt by `(job_id, attempt_id, attempt)` and scope.
- Load linked exception/log/query/cache/outgoing request counters via correlation context.

## 2. Presentation
- Info card includes runtime metadata and status badge.
- Events/exception sections load lazily only when payload exists.
- Timeline uses ordered spans from normalized execution stages.

## 3. Navigation
- Back link and action links preserve selected job context and period.
- Missing attempt context returns fallback to job detail with contextual message.
