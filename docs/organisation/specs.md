# Functional Specifications - Organization Management

## 1. Purpose

This document defines organization-centric functional requirements for a Laravel Nightwatch-like platform.

## 2. Scope

Included:

- Organization lifecycle and tenancy boundaries.
- Membership, invitations, and organization roles.
- Organization-level access to monitored assets.
- Plan limits, quotas, and usage visibility.
- Organization security controls and auditability.
- Organization-defined roles and permission sets.

Role definitions and assignments are implemented in this document; enforcement of those permissions is handled by the authentication module.

Excluded:

- User authentication mechanics (registration, login, 2FA).
- Low-level observability ingestion pipeline internals.
- Third-party accounting and tax processing details.

## 3. Actors

- `Platform Admin`: internal operator managing platform-level governance.
- `Organization Owner`: full control of one organization, billing, and security settings.
- `Organization Admin`: manages members, projects, environments, and alert policies.
- `Organization Developer`: configures projects/environments and investigates issues.
- `Organization Viewer`: read-only access to organization dashboards and incident history.

## 4. Functional Requirements

## 4.1 Organization Lifecycle

- `FR-ORG-001`: An authenticated user can create a new organization with name and unique slug.
- `FR-ORG-002`: Organization slug uniqueness is enforced globally.
- `FR-ORG-003`: Creator becomes `Organization Owner` automatically.
- `FR-ORG-004`: Organization profile supports update of name, logo, timezone, and default locale.
- `FR-ORG-005`: Organization can be soft-deleted by owner with explicit confirmation flow.
- `FR-ORG-006`: Soft-deleted organizations can be restored within a configurable retention window.

## 4.2 Tenant Isolation

- `FR-ORG-007`: All organization data is strictly tenant-scoped and inaccessible cross-organization.
- `FR-ORG-008`: Every monitored resource (project, environment, alert rule) belongs to exactly one organization.
- `FR-ORG-009`: API and UI endpoints enforce organization context before authorization checks.
- `FR-ORG-010`: Data exports and searches only include records for the active organization.
- `FR-ORG-011`: Organization context is explicit on every request path and cannot be inferred from stale session state.

## 4.3 Membership and Invitations

- `FR-ORG-012`: Organization Owner or Organization Admin can invite users by email to join an organization.
- `FR-ORG-013`: Invitation includes role assignment and expiration timestamp.
- `FR-ORG-014`: Invitation acceptance links are signed and single-use.
- `FR-ORG-015`: Organization Owner or Organization Admin can revoke pending invitations.
- `FR-ORG-016`: Organization Owner or Organization Admin can remove members from the organization.
- `FR-ORG-017`: Removal of an Owner requires explicit ownership transfer to another active user first.
- `FR-ORG-018`: Removing the last member for a security-critical role is blocked when it would leave required governance coverage missing (for example, no active `Organization Owner`).
- `FR-ORG-019`: Membership and role changes are effective immediately for subsequent requests.

## 4.4 Organization Roles and Permissions

- `FR-ORG-020`: Organization permissions are driven by organization-defined roles and permission sets.
- `FR-ORG-021`: Default roles include `Organization Owner`, `Organization Admin`, `Organization Developer`, and `Organization Viewer` during bootstrap.
- `FR-ORG-022`: Organizations can create, update, and remove custom roles in addition to defaults.
- `FR-ORG-023`: `Organization Owner` is the only role allowed to transfer ownership.
- `FR-ORG-024`: `Organization Admin` can manage members and permissions except ownership transfer and removal of the active owner.
- `FR-ORG-025`: `Organization Developer` can manage technical resources but not billing or member administration.
- `FR-ORG-026`: `Organization Viewer` has read-only access to dashboards, incidents, and release history.
- `FR-ORG-027`: Authorization failures return consistent forbidden responses for API/XHR clients.

## 4.5 Monitored Assets per Organization

- `FR-ORG-028`: Organization can register multiple projects to monitor.
- `FR-ORG-029`: Each project can define multiple environments (`production`, `staging`, `development`).
- `FR-ORG-030`: Ingestion/security tokens are managed in the Project module (`docs/projects/specs.md`) and scoped by project/environment.
- `FR-ORG-031`: Project module token requirements (entropy, storage, display constraints) apply to scoped secrets.
- `FR-ORG-032`: Secret token storage follows immutable-or-encrypted representation requirements as defined in project token specs.
- `FR-ORG-033`: Rotation policy for scoped tokens follows the project-level token specification and supports configurable grace windows.
- `FR-ORG-034`: Ownership checks protect access to logs, traces, exceptions, and incident objects.

## 4.6 Quotas, Plans, and Limits

- `FR-ORG-035`: If limit governance is enabled, an organization has configurable limits (members, projects, ingest volume, retention).
- `FR-ORG-036`: When limits are enabled, the system warns before hard limits are reached using configurable thresholds.
- `FR-ORG-037`: When limits are enabled, hard-limit behavior is explicit (block create action, degrade ingestion, or read-only mode).
  - For ingestion limit: reject new events with explicit quota exceeded response and optional backoff hint.
  - For resource creation limit: block create actions with actionable error code.
  - For near-violations: allow read-only mode for non-critical read operations when configured by plan policy.
- `FR-ORG-038`: When limits are enabled, usage metrics are visible to Organization Owner or Organization Admin in near real-time.
- `FR-ORG-039`: Plan changes are auditable with actor and timestamp.

## 4.7 Security and Compliance Controls

- `FR-ORG-040`: Organization can restrict access by verified email domains (optional).
- `FR-ORG-041`: Sensitive organization actions require step-up confirmation (password or 2FA challenge).
- `FR-ORG-042`: Organization secrets are never displayed in plaintext after initial creation.

## 4.8 Auditability

- `FR-ORG-043`: Critical organization events are audit-logged (create/update/delete, invite, role change, ownership transfer, token rotation, plan change).
- `FR-ORG-044`: Audit records include actor, target, organization ID, IP, user agent, and timestamp.
- `FR-ORG-045`: Audit trail is immutable for non-platform users and retained per retention policy (minimum and maximum configured by platform policy).
- `FR-ORG-046`: Retention policy can trigger anonymization for stale records while preserving integrity metadata.
- `FR-ORG-047`: Audit records are filterable by event type, actor, and date range.

## 4.9 Cross-Organization User Experience

- `FR-ORG-048`: A user can belong to multiple organizations under one account.
- `FR-ORG-049`: UI provides explicit active-organization switcher.
- `FR-ORG-050`: Organization switching updates permissions and visible datasets atomically.
- `FR-ORG-051`: Last active organization is persisted per user.

## 5. Global Organization Behaviors

- `FR-ORG-052`: Organization-scoped endpoints return consistent pagination, sorting, and filtering formats.
- `FR-ORG-053`: Empty states exist for new organizations with no projects yet.
- `FR-ORG-054`: Dangerous actions (delete org, transfer ownership, revoke token) require explicit confirmation UI.
- `FR-ORG-055`: System notifications are organization-aware and respect member role visibility.

## 6. MVP Organization Boundary

Required for MVP:

- Organization create/update and tenant isolation.
- Membership invitations and role-based permissions.
- Multi-organization switcher for users.
- Organization-scoped monitored assets.
- Audit trail.

Can be deferred post-MVP:

- Advanced organization policy orchestration and enterprise governance workflows.
- Advanced quota automation and plan proration logic.
- Cross-organization consolidated reporting for platform operators.

## 7. Acceptance Checklist

- Each `FR-ORG-*` requirement has at least one acceptance test case.
- Tenant isolation is verified for API and UI access paths.
- Invitation and role change flows are auditable end-to-end.
- Organization switching never leaks data between tenants.
- Token rotation and secret handling meet security requirements.


## Technical Specifications

See dedicated technical specification: [specs-technical.md](./specs-technical.md)

## Related Resources

- **Technical Spec**: [specs-technical.md](./specs-technical.md)
- **Related Specs**: [auth/specs.md](../auth/specs.md), [projects/specs.md](../projects/specs.md)
- **Implementation Tasks**:
  - [007 - Org Quotas Switcher](./tasks/007-org-quotas-switcher.md)
  - [008 - Org Audit Compliance](./tasks/008-org-audit-compliance.md)
