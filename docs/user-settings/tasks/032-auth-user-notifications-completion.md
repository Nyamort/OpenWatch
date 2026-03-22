# Task T-032: User Security and Notification Settings
- Domain: `user-settings`
- Status: `completed`
- Priority: `P1`
- Dependencies: `T-028`, `T-001`

## Description
Complete user-level security controls (password change with current-credential confirmation, session listing/revocation) and notification category preferences per user (issue updates, threshold alerts, security notifications). Critical security notifications remain enforceably on and cannot be disabled.

## How to implement
1. Create `user_notification_preferences` table: `user_id`, `category` (enum: `issue_updates`, `threshold_alerts`, `security`), `enabled` (boolean). Seed defaults as all enabled on user creation.
2. Implement `UpdateNotificationPreferences` action: update per-category toggles. Block setting `security` category to `false` (policy-locked). Apply to future notifications only.
3. Implement `ChangePassword` action (via Fortify): require `current_password` confirmation, enforce password policy, invalidate all other sessions on success. Integrate step-up gate middleware.
4. Session management (from T-003 if not already done): expose session list at `GET /settings/sessions` and session revoke at `DELETE /settings/sessions/{id}`.
5. Expose account metadata at `GET /settings/account`: `created_at`, `last_sign_in_at`, email verification status.
6. Build Inertia pages for: notification preferences (toggles per category with lock indicator for security), password change form, sessions list, account metadata.
7. All settings changes emit audit events via `AuditLogger`.
8. Write feature tests: `security` category cannot be disabled, password change requires correct current password, session revocation works, changed preferences apply to next notification dispatch.

## Key files to create or modify
- `database/migrations/xxxx_create_user_notification_preferences_table.php`
- `app/Models/UserNotificationPreference.php`
- `app/Actions/Settings/UpdateNotificationPreferences.php`
- `app/Http/Controllers/Settings/NotificationPreferencesController.php`
- `app/Http/Controllers/Settings/PasswordController.php` — extend existing
- `app/Http/Controllers/Settings/AccountController.php`
- `resources/js/pages/settings/notifications.tsx`
- `resources/js/pages/settings/password.tsx`
- `resources/js/pages/settings/account.tsx`
- `tests/Feature/Settings/UserSecuritySettingsTest.php`
- `tests/Feature/Settings/NotificationPreferencesTest.php`

## Acceptance criteria
- [ ] Password change requires the current password to be provided and correct
- [ ] Password change invalidates all other active sessions for the user
- [ ] Security notification category cannot be set to disabled (policy-locked)
- [ ] Disabling `issue_updates` stops future issue-related emails for that user
- [ ] Session list shows all active sessions with IP, user agent, and last activity
- [ ] User can revoke any session except (optionally) their current one
- [ ] All security setting changes are audit-logged

## Related specs
- [Functional spec](../specs.md) — `FR-USR-011` to `FR-USR-024`
- [Technical spec](../specs-technical.md)
