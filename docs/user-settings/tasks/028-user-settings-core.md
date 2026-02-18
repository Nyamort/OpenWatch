# Task T-028: User Settings Profile and Preferences
- Domain: `user-settings`
- Status: `not started`
- Priority: `P1`
- Checked on: `2026-02-18`
- Already done in codebase? `No`

## Description
Implement own-profile settings access, updates, locale/timezone/display preferences, persistence, and validation with immediate UI reflection.

## How to execute
1. Add profile update endpoint/page with constrained editable fields.
2. Add preference persistence service with sensible defaults.
3. Ensure immediate propagation to UI contexts.
4. Add explicit validation and partial update protection.

## Architecture implications
- **Context**: user settings bounded context.
- **Storage**: user profile + preference JSON with migration-safe schema.
- **Security**: own-settings only constraint on user route authorization.
- **Cache**: prefer refresh from DB per request (no stale preference leak).

## Acceptance checkpoints
- Users cannot access/update other users’ settings.
- Invalid values show field-level errors only for affected inputs.

## Done criteria
- `FR-USR-001` to `FR-USR-010` implemented.
