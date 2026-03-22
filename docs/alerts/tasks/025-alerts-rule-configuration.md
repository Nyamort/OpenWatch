# Task T-025: Threshold Alert Rule Configuration and Management
- Domain: `alerts`
- Status: `completed`
- Priority: `P1`
- Dependencies: `T-009`, `T-006`, `T-029`

## Description
Implement alert rule CRUD: threshold rules scoped to project/environment, metric selector, operator (>, >=, <, <=), threshold value, evaluation window, recipient assignment, enable/disable toggle, and delete with confirmation. No active rule may have zero recipients.

## How to implement
1. Create migrations: `alert_rules` (`id`, `organization_id`, `project_id`, `environment_id`, `name`, `metric` enum, `operator` enum, `threshold`, `window_minutes`, `enabled`, timestamps) and `alert_rule_recipients` (`alert_rule_id`, `user_id` or `email`).
2. Implement `CreateAlertRule` action: validate metric is from supported list, operator is valid, window is a permitted duration, at least one recipient. Authorize: `Developer` or above.
3. Implement `UpdateAlertRule` action: same validations. If disabling: clear `alert_states` for this rule.
4. Implement `DeleteAlertRule` action: require explicit confirmation token (not just a DELETE call); Admin or Owner only.
5. Implement `ToggleAlertRule` action: enable/disable; validate recipients present before enabling.
6. Cache active rule snapshots in Redis (invalidated on any write) for the evaluator (T-026) to consume efficiently.
7. Build Inertia pages: rule list with enabled/disabled badge, rule create/edit form, recipient multi-select, delete confirmation modal.
8. Write feature tests: create with no recipients blocked, delete confirmation required, Viewer cannot create/edit, toggle disabling clears states, recipient must be org member.

## Key files to create or modify
- `database/migrations/xxxx_create_alert_rules_table.php`
- `database/migrations/xxxx_create_alert_rule_recipients_table.php`
- `app/Models/AlertRule.php`
- `app/Models/AlertRuleRecipient.php`
- `app/Actions/Alerts/CreateAlertRule.php`
- `app/Actions/Alerts/UpdateAlertRule.php`
- `app/Actions/Alerts/DeleteAlertRule.php`
- `app/Actions/Alerts/ToggleAlertRule.php`
- `app/Http/Controllers/Alerts/AlertRuleController.php`
- `resources/js/pages/alerts/index.tsx`
- `resources/js/pages/alerts/create.tsx`
- `resources/js/pages/alerts/edit.tsx`
- `tests/Feature/Alerts/AlertRuleConfigurationTest.php`

## Acceptance criteria
- [ ] Alert rule can be created with a metric, operator, threshold, window, and at least one recipient
- [ ] Creating a rule with zero recipients is rejected
- [ ] Enabling a rule with zero recipients is rejected
- [ ] Viewer role cannot create, edit, or delete alert rules
- [ ] Delete requires explicit confirmation; accidental DELETE without confirmation is rejected
- [ ] Active rule snapshot cache is invalidated on every write (create, update, delete, toggle)
- [ ] Rule is scoped to the correct project+environment and not visible across organizations

## Related specs
- [Functional spec](../specs.md) — `FR-ALERT-001` to `FR-ALERT-014`, `FR-ALERT-025` to `FR-ALERT-028`
- [Technical spec](../specs-technical.md)
