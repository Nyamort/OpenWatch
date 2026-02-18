# Technical Specifications - Query Detail Analytics

## 1. Data model
- Filter by selected query signature and project/environment context.
- Retrieve execution rows with `date`, `source`, `location`, `connection`, `duration`.

## 2. Sections
- Info card includes total_time, avg, p95, and calls.
- SQL panel from normalized query text should be sanitized and formatted.

## 3. Navigation
- Segmented duration filter updates both charts and table.
- Fallback to list view when signature does not match active context.
