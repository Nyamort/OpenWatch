# Task T-028: User Settings — Profile and Preferences
- Domain: `user-settings`
- Status: `not started`
- Priority: `P1`
- Dependencies: `T-001`

## Description
Implement user-owned settings: profile update (display name, username, avatar), personal preferences (timezone, locale, display preferences), immediate UI propagation after save, field-level validation, and protection against partial overwrites of unrelated settings.

## How to implement
1. Add `timezone`, `locale`, `display_preferences` (JSONB) columns to the `users` table via migration.
2. Implement `UpdateProfile` action: validate display name (required, max 255), username (unique across users, slug rules), avatar (optional, URL or file upload). Emit `ProfileUpdated` event for audit.
3. Implement `UpdatePreferences` action: validate timezone against PHP's `timezone_identifiers_list()`, locale against configured supported locales, display_preferences keys against a whitelist. Merge partial updates — do not overwrite keys not present in the request.
4. Add `GET /settings/profile` and `GET /settings/preferences` Inertia pages.
5. Add `PATCH /settings/profile` and `PATCH /settings/preferences` endpoints with dedicated Form Request classes.
6. Authorization: user can only access their own settings page (`$user->id === $request->user()->id`).
7. Propagate saved preferences to the Inertia shared props so the UI reflects changes immediately without reload.
8. Write feature tests: profile update, username uniqueness rejected, preference timezone validated, user cannot access another user's settings page, partial preference update does not wipe other keys.

## Key files to create or modify
- `database/migrations/xxxx_add_preferences_to_users_table.php`
- `app/Actions/Settings/UpdateProfile.php`
- `app/Actions/Settings/UpdatePreferences.php`
- `app/Http/Controllers/Settings/ProfileController.php` — extend existing
- `app/Http/Controllers/Settings/PreferencesController.php`
- `app/Http/Requests/Settings/ProfileUpdateRequest.php` — extend existing
- `app/Http/Requests/Settings/PreferencesUpdateRequest.php`
- `resources/js/pages/settings/profile.tsx`
- `resources/js/pages/settings/preferences.tsx`
- `tests/Feature/Settings/UserProfileTest.php`
- `tests/Feature/Settings/UserPreferencesTest.php`

## Acceptance criteria
- [ ] User can update display name, username, and avatar
- [ ] Username uniqueness is enforced globally; duplicate returns a field-level error
- [ ] Timezone is validated against the system timezone list
- [ ] Partial preference update (e.g., only timezone) does not overwrite other preference keys
- [ ] Saved preferences are reflected in the UI on the same response (no second request needed)
- [ ] A user cannot navigate to or modify another user's settings page
- [ ] Invalid fields return errors for only the affected fields, not the whole form

## Related specs
- [Functional spec](../specs.md) — `FR-USR-001` to `FR-USR-010`
- [Technical spec](../specs-technical.md)
