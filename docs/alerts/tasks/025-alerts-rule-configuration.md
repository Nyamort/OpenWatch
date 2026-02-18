# Task T-025: Threshold Rule Configuration and Management
- Domain: `alerts`
- Status: `not started`
- Priority: `P1`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement threshold menu, rule CRUD, recipient assignment, enable/disable, delete with confirmations, and scope filtering.

## How to execute
1. Add rule CRUD screens and API with scoped project/environment binding.
2. Add operators support (>, >=, <, <=) and metric selector.
3. Add validation for recipient presence and clear invalid states.
4. Add confirmation workflows for delete and ownership checks.

## Architecture implications
- **Context**: alert administration + organization scope.
- **Storage**: `alert_rules`, `rule_recipients`, operator enums.
- **Cache**: active rule snapshot cache used by evaluator worker.
- **Security**: admin/developer permissions for writes.

## Acceptance checkpoints
- Rule can be created/updated and toggled by scope.
- No active rule with zero recipients allowed.

## Done criteria
- `FR-ALERT-001` to `FR-ALERT-014` and `FR-ALERT-025` to `FR-ALERT-028` partly covered.
