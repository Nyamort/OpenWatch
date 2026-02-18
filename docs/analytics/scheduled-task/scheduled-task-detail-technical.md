# Technical Specifications - Scheduled Task Detail Analytics

## 1. Scope
- Detail filter by task command/name and active period.
- Include schedule badge metadata (`cron` string, etc.).

## 2. Data retrieval
- Run-level table with status + duration + message.
- Segment filters: duration threshold and status subset.
- Rows sorted desc, paginated.
