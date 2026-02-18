# Technical Specifications - Threshold Alerts

## 1. Service boundaries
- Backend domain: `Alerts` bounded context under organization/project/environment scope.
- Core boundaries:
  - Rule management (CRUD + enable state).
  - State evaluation worker.
  - Notification delivery and activity history.
- Inbound calls are served from authenticated web routes + background workers.

## 2. Suggested architecture
- **Backend**: Laravel 12, job-based evaluation service (`EvaluateAlertRuleJob`) and schedule-safe execution worker.
- **Data model**:
  - `threshold_rules` (organization_id, project_id?, environment_id?, metric_type, operator, threshold_value, window_seconds, state, enabled, recipients).
  - `threshold_recipients` (user_id or email).
  - `threshold_alert_events` (rule_id, project_id, environment_id, state, started_at, ended_at, transition_reason).
  - `alert_notification_outboxes` (attempt_count, status, provider_response, last_error).
- **Read model**:
  - Rule list/detail service with explicit filters: scope + status + state + search.

## 3. Architecture and flows
- Rule CRUD:
  - `POST /settings/thresholds` → `StoreThresholdRuleRequest`.
  - `PUT /settings/thresholds/{rule}` for update and scope/recipient changes.
  - `POST /settings/thresholds/{rule}/toggle` for enable/disable.
  - `DELETE /settings/thresholds/{rule}` soft-delete with explicit confirmation.
- Evaluation flow:
  1. Ingestion process writes metric snapshots.
  2. Scheduled job evaluates active rules for the relevant scope.
  3. On state transition, create or update `threshold_alert_events` row.
  4. Notify asynchronously through queued jobs.
- Notifications and delivery state are always written in the same transaction as alert transition when possible, then retried asynchronously.

## 4. Database and query model
- Add composite indexes:
  - `(organization_id, project_id, environment_id, enabled, deleted_at)` on rules.
  - `(rule_id, state, started_at)` for alert history.
  - `(organization_id, state, created_at)` for alert list views.
  - `(organization_id, delivery_status, created_at)` for delivery retries.
- Use partial indexes for unresolved states where supported.

## 5. Security and isolation
- Enforce policy checks with form of `Organization` scope guard.
- Recipients can be users in allowed scope only; stale recipients must be marked inactive, never silently removed from history.
- Redact recipient addresses in logs unless needed for troubleshooting.

## 6. Frontend
- `Inertia + TS` pages under app settings section.
- Mutating buttons only shown for owner/admin roles.
- Drill-in detail page preserves active period and scope context when returning from notifications list.

## 7. Audit and operations
- Event categories: created, updated, enabled, disabled, deleted, recipient-added, recipient-removed, transition-triggered, transition-resolved, delivery-failed.
- All events include actor_id, reason, source IP, route, and timestamps.
- Add one dashboard metric for unresolved alerts and one for delivery failures.
