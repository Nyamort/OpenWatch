# Technical Specifications - Job Detail Analytics

## 1. Data model
- Restrict source scope to selected `name` and active filters.
- Aggregate attempts by status and durations for selected job.

## 2. Service flow
- Reuse shared job aggregate service with `job_name` parameter.
- Build two chart streams: count/status trend and duration trend.
- Attempts table supports pagination and segmented filters.

## 3. UX and contracts
- Keep period and user context through breadcrumbs and back links.
- Threshold display fallback to `N/A` when no policy exists.
