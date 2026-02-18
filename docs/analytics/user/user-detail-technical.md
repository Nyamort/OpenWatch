# Technical Specifications - User Detail Analytics

## 1. Scope
- Select a user by stable identifier and period filters.
- Render request summary and pivot links to request/job/exception/logs in same scope.

## 2. Query model
- Top route cards from request events filtered by user.
- Requests table sorted desc with method/url/status/duration.
- Additional drilldown filters for duration (`>= avg`, `>= p95`) and status classes.

## 3. Navigation
- Back to users list preserves filters.
- Missing user context returns fallback with friendly guidance.
