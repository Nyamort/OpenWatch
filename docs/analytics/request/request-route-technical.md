# Technical Specifications - Route-Scoped Request Analytics

## 1. Domain-specific model
- Uses same request aggregate as request page plus route filter constraint.
- Route identity can be normalized by path and method set for cache friendliness.

## 2. Queries
- Chart queries scoped by `route_path` and `project/environment`, plus user + period.
- Request rows are non-aggregated and paginated.
- Segmented filters: duration (`>= avg`, `>= p95`) and status class filter.

## 3. Navigation and fallback
- Back links preserve route identifier and list filters.
- If route no longer exists in current filters, fallback to global request page and emit explicit guidance.
