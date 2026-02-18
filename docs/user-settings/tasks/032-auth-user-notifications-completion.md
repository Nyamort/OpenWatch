# Task T-032: User Security and Notification Settings
- Domain: `user-settings`
- Status: `not started`
- Priority: `P1`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Complete user-level security controls (password change/session revoke), account metadata visibility, and notification category preferences for future delivery behavior.

## How to execute
1. Add password change with current-credential confirmation and policy.
2. Add personal notification preferences per category with critical-category locked policy.
3. Add session list/revoke controls where policy permits.
4. Add metadata display and empty/default state handling.

## Architecture implications
- **Context**: user settings + notification sending filters.
- **Storage**: user security preferences and category-level config.
- **Security**: step-up confirmation for sensitive updates.
- **Delivery**: alert/issue notification dispatch must respect per-user preferences.

## Acceptance checkpoints
- Critical security notifications remain enforceably active.
- Password updates propagate policy and session rules immediately.

## Done criteria
- `FR-USR-011` to `FR-USR-024` complete.
