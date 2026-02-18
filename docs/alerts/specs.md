# Functional Specifications - Threshold Alerts

## 1. Purpose

This document defines functional requirements for threshold-based alerting and email notifications.

## 2. Scope

Included:

- Threshold configuration menu.
- Threshold rule lifecycle (create, update, enable/disable, delete).
- Alert evaluation outcomes (triggered/resolved).
- Email notifications to configured recipients.
- Alert history visibility and auditability.

Excluded:

- Notification provider implementation details.
- SMS/Slack/PagerDuty integrations.
- Advanced anomaly detection models.

## 3. Actors

- `Organization Owner`: full alert administration.
- `Organization Admin`: full alert administration.
- `Organization Developer`: manage thresholds in authorized scopes.
- `Organization Viewer`: read-only visibility on active alerts and history.

## 4. Functional Requirements

## 4.1 Threshold Menu

- `FR-ALERT-001`: The product exposes a dedicated `Thresholds` menu in authenticated workspace navigation.
- `FR-ALERT-002`: Thresholds are configured in organization/project/environment context.
- `FR-ALERT-003`: Users only see threshold rules for scopes they are authorized to access.

## 4.2 Rule Configuration

- `FR-ALERT-004`: Authorized users can create a threshold rule with at minimum:
  - rule name,
  - metric source/type,
  - condition operator and threshold value,
  - evaluation window,
  - recipients list.
- `FR-ALERT-005`: A rule can target one metric at a time in MVP.
- `FR-ALERT-006`: Rules support at minimum comparison operators:
  - greater than,
  - greater than or equal,
  - lower than,
  - lower than or equal.
- `FR-ALERT-007`: Rules can be edited after creation.
- `FR-ALERT-008`: Rules can be enabled or disabled without deletion.
- `FR-ALERT-009`: Rules can be deleted with explicit confirmation.
- `FR-ALERT-010`: Invalid rule input shows actionable validation messages.

## 4.3 Recipients and Ownership

- `FR-ALERT-011`: Recipient selection supports organization members in authorized scope.
- `FR-ALERT-012`: Recipient selection supports multiple recipients per rule.
- `FR-ALERT-013`: Rules explicitly display recipient state (for example no recipient configured is invalid for activation).
- `FR-ALERT-014`: If a recipient loses access or is removed, rule recipient state is updated and remains visible for admin correction.

## 4.4 Alert State Lifecycle

- `FR-ALERT-015`: A rule can produce alert states at minimum:
  - `ok`,
  - `triggered`.
- `FR-ALERT-016`: When rule condition becomes true, the alert moves to `triggered`.
- `FR-ALERT-017`: When rule condition returns to normal, the alert returns to `ok`.
- `FR-ALERT-018`: Trigger and recovery timestamps are stored and visible in alert history.
- `FR-ALERT-019`: Repeated evaluations while already triggered must not create duplicate active alerts for the same rule/scope.

## 4.5 Email Notifications

- `FR-ALERT-020`: When an alert transitions to `triggered`, an email notification is sent to configured recipients.
- `FR-ALERT-021`: When an alert transitions back to `ok`, a recovery email notification is sent to configured recipients.
- `FR-ALERT-022`: Notification emails include at minimum:
  - organization/project/environment context,
  - rule name,
  - metric summary,
  - threshold condition,
  - trigger/recovery timestamp,
  - link to relevant page.
- `FR-ALERT-023`: If no valid recipients exist, the alert state change is still recorded and surfaced as notification delivery issue.
- `FR-ALERT-024`: Email notification attempts and outcomes are visible in alert activity/history.

## 4.6 List and Detail Views

- `FR-ALERT-025`: Threshold rules list supports pagination, sorting, and search.
- `FR-ALERT-026`: Rules list includes at minimum:
  - rule name,
  - scope,
  - status (enabled/disabled),
  - current alert state (`ok`/`triggered`),
  - last triggered at,
  - recipients count.
- `FR-ALERT-027`: Each rule has a detail page with current configuration, recent evaluations, and notification history.
- `FR-ALERT-028`: Rule detail allows direct edit, enable/disable, and recipient updates for authorized users.

## 4.7 Auditability and Isolation

- `FR-ALERT-029`: Create/update/enable/disable/delete actions on threshold rules are audit-logged.
- `FR-ALERT-030`: Trigger/recovery alert transitions are audit-logged.
- `FR-ALERT-031`: Audit records include actor (if user action), organization ID, scope identifiers, rule ID, action type, and timestamp.
- `FR-ALERT-032`: Threshold and alert data are tenant-isolated by organization.

## 5. Global Behaviors

- `FR-ALERT-033`: Empty states are explicit when no threshold rules exist.
- `FR-ALERT-034`: Destructive actions (delete rule) require explicit confirmation.

## 6. MVP Scope

Required for MVP:

- Thresholds menu and scoped rules list.
- Create/update/enable/disable/delete threshold rules.
- Recipient selection for organization members.
- Alert state transitions (`ok` <-> `triggered`).
- Email on trigger and recovery.
- Rule detail with recent history.
- Audit trail for admin and lifecycle actions.

Can be deferred post-MVP:

- Multi-channel notifications.
- Escalation policies and on-call schedules.
- Composite rules and advanced correlation.

## 7. Acceptance Checklist

- Each `FR-ALERT-*` requirement has at least one acceptance test case.
- A threshold rule can be created and enabled in the right scope.
- Trigger transition sends email to configured recipients.
- Recovery transition sends recovery email.
- Rule updates are reflected immediately for subsequent evaluations.
- Cross-organization threshold visibility is blocked.


## Technical Specifications

See dedicated technical specification: [specs-technical.md](./specs-technical.md)

## Related Resources

- **Technical Spec**: [specs-technical.md](./specs-technical.md)
- **Related Specs**: [analytics/specs.md](../analytics/specs.md), [issues/specs.md](../issues/specs.md), [dashboard/specs.md](../dashboard/specs.md)
- **Implementation Tasks**:
  - [025 - Alerts Rule Configuration](./tasks/025-alerts-rule-configuration.md)
  - [026 - Alerts Evaluation Notify](./tasks/026-alerts-evaluation-notify.md)
