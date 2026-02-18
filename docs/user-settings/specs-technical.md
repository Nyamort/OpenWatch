# Technical Specifications - User Settings

## 1. Data model

Core tables:

- `users` (Fortify base): `id`, `name`, `email`, `password`, `email_verified_at`, `two_factor_secret`, `created_at`, `updated_at`.
- `user_profiles` (optional metadata split): `user_id`, `username`, `avatar_path`, `timezone`, `locale`, `display_preferences` (jsonb).
- `user_notification_preferences`: `user_id`, `category_key`, `enabled` (boolean), `updated_at`.

`user_notification_preferences` schema:

| `category_key` | Default | Mutable by user |
|----------------|---------|-----------------|
| `issue.updates` | `true` | ✓ |
| `alert.triggered` | `true` | ✓ |
| `alert.recovered` | `true` | ✓ |
| `security.account` | `true` | ✗ (immutable by policy) |
| `security.login` | `true` | ✗ (immutable by policy) |

`display_preferences` jsonb keys: `date_format` (relative | absolute), `table_density` (compact | comfortable), `theme` (system | light | dark).

Missing preference keys fall back to defaults — normalized on read, persisted on first explicit save.

## 2. Profile update flows

Profile fields editable by user:
- `name` (users.name) → validated: required, max 255 chars.
- `username` (user_profiles.username) → validated: unique across platform, alphanumeric + underscores, max 50 chars.
- `avatar` → uploaded to storage, path saved in `user_profiles.avatar_path`. Size limit: 2MB, types: jpg/png/webp.

Email change (deferred post-MVP): requires re-verification flow — sends verification email to new address; old email notified of change attempt; change applied only after new email verified.

Profile update writes audit event: `user_setting.profile_updated` with actor_id + session fingerprint + changed fields (not values).

## 3. Notification preferences

Load preferences: `SELECT * FROM user_notification_preferences WHERE user_id = ?` — build a map of all keys; fill missing keys with defaults.

Update preference: `UPSERT INTO user_notification_preferences (user_id, category_key, enabled) VALUES (?, ?, ?) ON CONFLICT (user_id, category_key) DO UPDATE SET enabled = ?`.

Immutable keys: `security.account` and `security.login` are validated server-side — any attempt to disable them returns `422` with clear error message.

Preference changes take effect for future notifications only — no retroactive effect on already-queued notification jobs.

## 4. Password and credential management

Password change flow (via Fortify):
1. `POST /user/password` with `current_password`, `password`, `password_confirmation`.
2. Validate `current_password` matches hashed password in `users` table.
3. Validate new password meets policy (min 12 chars, complexity rules configurable).
4. Hash new password + update `users.password`.
5. Revoke all other sessions (configurable via `auth.password_timeout` policy).
6. Write audit event: `user_setting.password_changed`.

Password history (post-MVP): store last N password hashes to prevent reuse.

## 5. Session management

Session listing: `SELECT id, ip_address, user_agent, last_activity FROM sessions WHERE user_id = ? ORDER BY last_activity DESC`.

Each session row displays: IP address, user agent (parsed device/browser), relative last activity time, and "current" badge for active session.

Session revocation: `DELETE FROM sessions WHERE id = ? AND user_id = ?` (user_id guard prevents cross-user revocation). Cannot revoke current session if policy forbids (`config('session.revoke_current') = false`).

## 6. API routes and contracts

| Method | Route | Description |
|--------|-------|-------------|
| GET | `/user/settings` | Load profile + preferences + sessions |
| PUT | `/user/profile` | Update name, username, avatar |
| PUT | `/user/preferences` | Update timezone, locale, display preferences |
| PUT | `/user/notifications` | Update notification preference toggles |
| POST | `/user/password` | Change password (Fortify) |
| GET | `/user/sessions` | List active sessions |
| DELETE | `/user/sessions/{session}` | Revoke a session |

Profile update response:
```json
{ "user": { "id": "...", "name": "...", "username": "...", "avatar_url": "..." } }
```

Notification preferences response:
```json
{
  "preferences": [
    { "category_key": "issue.updates", "label": "Issue updates", "enabled": true, "mutable": true },
    { "category_key": "security.account", "label": "Account security", "enabled": true, "mutable": false }
  ]
}
```

## 7. Test strategy

Key feature tests:
- Profile update: name and username saved correctly; username uniqueness violation returns 422.
- Avatar upload: file stored, path persisted; oversized file rejected with 422.
- Notification preference: `issue.updates` toggled to `false` persists correctly; future notification delivery skips user.
- Immutable preference: attempting to disable `security.account` returns 422.
- Password change: valid current password + new password updates hash; invalid current password returns 422.
- Session list: returns only sessions for current user.
- Session revocation: deletes correct session row; cannot revoke session belonging to another user.
- Cannot access another user's settings page (403).
- Audit event written for profile update and password change.

## Related Resources

- **Functional Spec**: [specs.md](./specs.md)
- **Related Specs**: [auth/specs.md](../auth/specs.md), [organisation/specs.md](../organisation/specs.md)
- **Implementation Tasks**:
  - [028 - User Settings Core](./tasks/028-user-settings-core.md)
  - [032 - Auth/User Notifications Completion](./tasks/032-auth-user-notifications-completion.md)
