# Technical Specifications - Notification Detail Analytics

## 1. Scope
- Retrieve selected notification class rows plus metadata and execution context.

## 2. Data model and queries
- Duration segments and status are applied to message list queries.
- Columns include `date`, `source`, `channel`, `duration`.

## 3. Interaction
- Source preview can include execution badge from request/method info when available.
- Back/forward navigation preserves class filter and period.
