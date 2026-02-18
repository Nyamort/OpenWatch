# Task T-026: Alert Evaluation and Notification Transitions
- Domain: `alerts`
- Status: `not started`
- Priority: `P1`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement rule evaluation loop, deduplicated state transitions (`ok`<->`triggered`), and recovery/trigger email flows.

## How to execute
1. Add scheduled evaluator job reading active alert rules and telemetry aggregates.
2. Transition to triggered/recovered with timestamps and idempotent guard.
3. Send emails for trigger/recovery with rule context and deep links.
4. Track delivery attempts/outcomes and include in alert activity.

## Architecture implications
- **Context**: alert engine + job worker.
- **Storage**: `alert_states`, `alert_histories`, queue jobs for outbound mail.
- **Reliability**: prevent duplicate active alerts and repeated sends while already in-state.
- **Observability**: metrics for state churn and email failures.

## Acceptance checkpoints
- State transitions do not duplicate notifications for same rule in same state.
- Recovery email sent on transition back to ok.

## Done criteria
- `FR-ALERT-015` to `FR-ALERT-024` complete.
