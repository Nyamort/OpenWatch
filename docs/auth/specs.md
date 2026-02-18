# Functional Specifications - Authentication Only

## 1. Purpose

This document defines the authentication and access-control functional requirements for a Laravel Nightwatch-like platform.

## 2. Scope

Included:

- Account lifecycle (registration, login, logout).
- Credential and session security.
- Email verification.
- Password reset and password confirmation.
- Two-factor authentication (TOTP + recovery codes).
- Role-based access control for protected areas, enforced through organization-defined roles and permissions consumed from the Organization module.
- Authentication audit trail.

Note: role-based access control is scoped by active organization context.

Excluded:

- Observability features (errors, traces, logs, dashboards, alerts).
- Billing and usage metering.
- Deployment and release intelligence.
- Organization membership and role assignment flows. (Owned by Organization Management specs.)

## 3. Actors

- `Organization Owner`: full access and security ownership within an organization.
- `Organization Admin`: organization security operator with elevated auth privileges.
- `Organization Developer`: authenticated user with product access based on organization role.
- `Organization Viewer`: authenticated read-only user.

Default roles (`Organization Owner`, `Organization Admin`, `Organization Developer`, `Organization Viewer`) are baseline labels only.
Custom roles are defined and managed by each organization in the Organization module.

## 4. Functional Requirements

## 4.1 Registration and Account Activation

- `FR-AUTH-001`: A guest can create an account with name, email, and password.
- `FR-AUTH-002`: Email uniqueness is enforced globally.
- `FR-AUTH-003`: Password must meet configurable complexity rules.
- `FR-AUTH-004`: A verification email is sent after successful registration.
- `FR-AUTH-005`: Unverified users cannot access protected application routes.

## 4.2 Login and Logout

- `FR-AUTH-006`: A registered user can authenticate with valid credentials.
- `FR-AUTH-007`: Failed authentication returns a generic error message (no account enumeration).
- `FR-AUTH-008`: Login endpoint is rate-limited per identifier and IP.
- `FR-AUTH-009`: A logged-in user can terminate the current session via logout.
- `FR-AUTH-010`: Logout invalidates session and rotates CSRF token.

## 4.3 Email Verification

- `FR-AUTH-011`: Verification link must be signed and time-limited.
- `FR-AUTH-012`: User can request a new verification email when previous link expires or is lost.
- `FR-AUTH-013`: Verification state is persisted and auditable.
- `FR-AUTH-014`: Routes requiring verified identity enforce `auth` + `verified` access rules.

## 4.4 Password Reset and Password Confirmation

- `FR-AUTH-015`: User can request a password reset link by email.
- `FR-AUTH-016`: Reset token is single-use and expires after configurable TTL.
- `FR-AUTH-017`: Password reset requires password confirmation field validation.
- `FR-AUTH-018`: Existing sessions are revocable after password change.
- `FR-AUTH-019`: Sensitive actions can require recent password confirmation.

## 4.5 Two-Factor Authentication (2FA)

- `FR-AUTH-020`: User can enable TOTP-based 2FA from account security settings.
- `FR-AUTH-021`: Enabling/disabling 2FA requires prior password confirmation.
- `FR-AUTH-022`: User receives recovery codes and can regenerate them.
- `FR-AUTH-023`: Login challenge accepts either valid TOTP code or recovery code.
- `FR-AUTH-024`: Recovery code usage is one-time and immediately invalidated.
- `FR-AUTH-025`: UI exposes 2FA status (`disabled`, `pending_confirmation`, `enabled`).

## 4.6 Session Management

- `FR-AUTH-026`: User can view active sessions (device/IP/last activity).
- `FR-AUTH-027`: User can revoke individual sessions except current session if policy forbids.
- `FR-AUTH-028`: Privileged members (at least Organization Owner or Organization Admin) can force sign-out for a compromised account.
- `FR-AUTH-029`: Session lifetime and remember-me behavior are configurable.

## 4.7 Organization-Scoped Authorization

- `FR-AUTH-030`: Every protected route requires authentication and an active organization context.
- `FR-AUTH-031`: Authorization is permission-based and role-based, using permissions from organization-defined roles and permission mappings.
- `FR-AUTH-032`: Organization-defined roles are scoped to that organization and cannot grant privileges in other organizations.
- `FR-AUTH-033`: Authentication does not create, update, or delete roles or permissions; it consumes policy data from the Organization module.
- `FR-AUTH-034`: Role and permission changes from Organization are applied to authorization decisions for subsequent requests in the current organization context.
- `FR-AUTH-035`: Authorization policy input is always resolved through the Organization module for each request; authentication module only enforces policy result and does not cache long-lived role decisions.

## 4.8 Security and Auditability

- `FR-AUTH-036`: Authentication events are recorded (login success/failure, logout, reset, 2FA changes).
- `FR-AUTH-037`: Authentication-related audit records include actor, organization, target user, source IP, user agent, and timestamp.
- `FR-AUTH-038`: Sensitive values (passwords, tokens, recovery codes) are never logged in plaintext.
- `FR-AUTH-039`: Brute-force protection and lockout policy are configurable.

## 5. Global Authentication Behaviors

- `FR-AUTH-040`: All auth endpoints return consistent error format for API/XHR usage.
- `FR-AUTH-041`: Auth flows support both browser form submissions and XHR requests.
- `FR-AUTH-042`: Time-based security checks use server time and timezone-safe comparisons.
- `FR-AUTH-043`: All auth screens provide loading, success, and failure states.

## 6. MVP Authentication Boundary

Required for MVP:

- Registration, login, logout.
- Email verification.
- Password reset.
- 2FA with recovery codes.
- Session listing and revocation.
- Role-based route authorization.
- Authentication audit logging.

Can be deferred post-MVP:

- Advanced risk-based authentication policies.

## 7. Acceptance Checklist

- Each `FR-AUTH-*` requirement has at least one acceptance test case.
- Login and reset flows are protected against brute-force attempts.
- Unverified users are blocked from verified-only routes.
- 2FA challenge and recovery code flows are fully testable.
- Audit logs prove traceability for all security-critical auth actions.


## Technical Specifications

See dedicated technical specification: [specs-technical.md](./specs-technical.md)

## Related Resources

- **Technical Spec**: [specs-technical.md](./specs-technical.md)
- **Related Specs**: [organisation/specs.md](../organisation/specs.md), [user-settings/specs.md](../user-settings/specs.md)
- **Implementation Tasks**:
  - [001 - Auth Foundation Core](./tasks/001-auth-foundation-core.md)
  - [002 - Auth Verification Reset](./tasks/002-auth-verification-reset.md)
  - [003 - Auth 2FA Sessions](./tasks/003-auth-2fa-sessions.md)
  - [004 - Auth Org-Scoped Policies](./tasks/004-auth-org-scoped-policies.md)
