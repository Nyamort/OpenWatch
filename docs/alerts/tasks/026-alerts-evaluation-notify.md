# Task T-026: Alert Evaluation Engine and Notification Transitions
- Domain: `alerts`
- Status: `completed`
- Priority: `P1`
- Dependencies: `T-025`, `T-015`

## Description
Implement the scheduled alert evaluation loop: read active rules from cache, query telemetry aggregates, compute threshold violation, transition state (`ok` ↔ `triggered`), send trigger/recovery emails idempotently, and record delivery history.

## How to implement
1. Create migrations: `alert_states` (`alert_rule_id`, `status` enum ok/triggered, `triggered_at`, `recovered_at`, `last_evaluated_at`) and `alert_histories` (`alert_rule_id`, `transition`, `value`, `evaluated_at`, `notified_at`).
2. Implement `AlertEvaluator` service: given a rule, query the correct extraction table aggregated over `window_minutes`, compare result to `threshold` using `operator`. Returns `ok | triggered` + the actual metric value.
3. Implement `EvaluateAlertRules` job (scheduled every minute via `routes/console.php`):
   - Load enabled rules from Redis cache (fallback to DB)
   - For each rule: call `AlertEvaluator`, compare to current state in `alert_states`
   - On `ok → triggered`: update state, dispatch `SendAlertTriggeredNotification` job
   - On `triggered → ok`: update state, dispatch `SendAlertRecoveredNotification` job
   - On no change: update `last_evaluated_at` only, no notification
4. Implement `SendAlertTriggeredNotification` and `SendAlertRecoveredNotification` mailable jobs: include rule name, metric value, threshold, project/env context, and a deep link.
5. Idempotency guard: if state is already `triggered`, do not send a second trigger notification. Only send on state transition.
6. Write feature tests: ok→triggered transition sends email, triggered→ok sends recovery, no re-send while in same state, evaluator reads correct metric from extraction table.

## Key files to create or modify
- `database/migrations/xxxx_create_alert_states_table.php`
- `database/migrations/xxxx_create_alert_histories_table.php`
- `app/Models/AlertState.php`
- `app/Models/AlertHistory.php`
- `app/Services/Alerts/AlertEvaluator.php`
- `app/Jobs/EvaluateAlertRules.php`
- `app/Jobs/SendAlertTriggeredNotification.php`
- `app/Jobs/SendAlertRecoveredNotification.php`
- `app/Mail/AlertTriggeredMail.php`
- `app/Mail/AlertRecoveredMail.php`
- `routes/console.php` — schedule `EvaluateAlertRules` every minute
- `tests/Feature/Alerts/AlertEvaluationTest.php`

## Acceptance criteria
- [ ] Evaluator runs on schedule (every minute) and processes all enabled rules
- [ ] When metric crosses threshold: state transitions to `triggered` and trigger email is sent
- [ ] When metric recovers below threshold: state transitions to `ok` and recovery email is sent
- [ ] No second trigger email is sent while the rule is already in `triggered` state
- [ ] Email contains rule name, current value, threshold, and a deep link to the analytics page
- [ ] Evaluation history is recorded in `alert_histories` for each evaluation cycle
- [ ] Disabled rules are not evaluated

## Related specs
- [Functional spec](../specs.md) — `FR-ALERT-015` to `FR-ALERT-024`
- [Technical spec](../specs-technical.md)
