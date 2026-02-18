# Functional Specifications - User Settings

## 1. Purpose

This document defines functional requirements for user-level settings in the authenticated workspace.

## 2. Scope

Included:

- Personal profile settings.
- Personal preferences (timezone, locale, display preferences).
- Notification preferences for user-targeted emails.
- Password and security preferences available in user scope.

Excluded:

- Organization-wide settings.
- Role and permission administration.
- Billing and subscription settings.

## 3. Actors

- `Authenticated User`: can view and manage their own settings.
- `Organization Owner`: no implicit override of another user's personal settings.
- `Organization Admin`: no implicit override of another user's personal settings.

## 4. Functional Requirements

## 4.1 Access and Navigation

- `FR-USR-001`: The workspace exposes a `User Settings` area for authenticated users.
- `FR-USR-002`: A user can access only their own settings page.
- `FR-USR-003`: Unauthorized access to another user's settings is denied.

## 4.2 Profile Settings

- `FR-USR-004`: A user can update profile fields at minimum:
  - display name,
  - username,
  - avatar (optional).
- `FR-USR-005`: Profile updates are validated and return actionable field errors.
- `FR-USR-006`: Successful profile updates are reflected immediately across the UI.

## 4.3 Preferences

- `FR-USR-007`: A user can configure personal timezone.
- `FR-USR-008`: A user can configure personal locale/language (if localization is enabled).
- `FR-USR-009`: A user can configure display preferences used by the UI (for example compact tables, relative/absolute date formatting).
- `FR-USR-010`: Preferences are persisted per user and restored across sessions.

## 4.4 Notification Preferences

- `FR-USR-011`: A user can opt in/out of personal email notifications by category.
- `FR-USR-012`: Notification categories include at minimum:
  - issue updates,
  - threshold alerts addressed to the user,
  - security/account notifications.
- `FR-USR-013`: Critical security notifications remain enabled by policy and are not fully disable-able.
- `FR-USR-014`: Notification preference changes apply to future notifications only.

## 4.5 Password and Security

- `FR-USR-015`: A user can change their password after confirming current credentials.
- `FR-USR-016`: Password update enforces password policy and validation messaging.
- `FR-USR-017`: A user can view active sessions/devices and revoke their own sessions (except current session if policy forbids).
- `FR-USR-018`: Sensitive account changes require step-up confirmation when policy requires it.

## 4.6 Data and Privacy Controls

- `FR-USR-019`: A user can view core account metadata (created at, last sign-in, verified status).
- `FR-USR-020`: Personal settings changes are audit-logged with actor and timestamp.
- `FR-USR-021`: User setting data is tenant-safe and cannot leak across organizations.

## 5. Global Behaviors

- `FR-USR-022`: User settings pages provide explicit empty/default states for unset optional preferences.
- `FR-USR-023`: Save actions show success/error feedback.
- `FR-USR-024`: Invalid submissions do not partially overwrite unrelated settings.

## 6. MVP Scope

Required for MVP:

- Access to own user settings.
- Profile update (name and username).
- Timezone preference.
- Email notification preferences (issue updates + threshold alerts).
- Password change.
- Basic audit trail for settings updates.

Can be deferred post-MVP:

- Advanced session/device management.
- Fine-grained per-channel notification routing.
- Additional personalization options.

## 7. Acceptance Checklist

- Each `FR-USR-*` requirement has at least one acceptance test case.
- A user can update profile and preferences and see immediate effect.
- Notification preference changes affect future deliveries.
- A user cannot access or edit another user's settings.
- Sensitive updates are protected by validation and policy checks.


## Technical Specifications

See dedicated technical specification: [specs-technical.md](./specs-technical.md)

## Related Resources

- **Technical Spec**: [specs-technical.md](./specs-technical.md)
- **Related Specs**: [auth/specs.md](../auth/specs.md), [organisation/specs.md](../organisation/specs.md)
- **Implementation Tasks**:
  - [028 - User Settings Core](./tasks/028-user-settings-core.md)
  - [032 - Auth/User Notifications Completion](./tasks/032-auth-user-notifications-completion.md)
