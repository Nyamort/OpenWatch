# Functional Specifications - Project and Environment Management

## 1. Purpose

This document defines the functional requirements for managing projects and environments in a Laravel Nightwatch-like platform.

## 2. Scope

Included:

- Project lifecycle within an organization.
- Project metadata (name, icon, description).
- Environment lifecycle under a project (for example: production, staging, development).
- Environment-scoped authentication tokens for agents.
- Project and environment hierarchy for telemetry routing.
- Auditability of project and environment operations.
- Analytics requirements for record-type statistics pages are defined in `docs/analytics/specs.md`.

Excluded:

- Organization-level ownership and membership rules (see `docs/organisation/specs.md`).
- Authentication flow and identity management (see `docs/auth/specs.md`).
- Data retention and quota policy (see `docs/organisation/specs.md`).
- Full error/trace/query processing internals.

## 3. Actors

- `Organization Owner`: full control of an organization, including projects.
- `Organization Admin`: can create/update/delete projects and environments according to organization permissions.
- `Organization Developer`: manages technical configuration and project environments.
- `Organization Viewer`: read-only access to project metadata and status.

## 4. Functional Requirements

## 4.1 Project Lifecycle

- `FR-PROJ-001`: A user with project-create permission can create a new project within an active organization.
- `FR-PROJ-002`: A project has a display name and icon at minimum.
- `FR-PROJ-003`: Project name uniqueness is enforced per organization. An optional organization-scoped namespace slug may be used for path-safe identification and is unique when provided. If omitted, the system derives a slug from display name.
- `FR-PROJ-004`: Project details include description, timezone, optional internal metadata, and a computed health status derived from environment health signals.
- `FR-PROJ-005`: Health status is derived from environment snapshots and includes:
  - environment heartbeat freshness,
  - recent ingest error ratio,
  - recent routing failure ratio.
  It is recalculated on a schedule (at least every minute) and exposed as one of: `healthy`, `degraded`, `unhealthy`, or `unknown`.
- `FR-PROJ-006`: Projects can be updated (name, icon, description, metadata).
- `FR-PROJ-007`: Deleting a project requires explicit confirmation and marks the project as deleted/archived according to retention policy; mutation endpoints reject updates while readable state is retained.
- `FR-PROJ-008`: A deleted project can be restored within configured retention rules.
- `FR-PROJ-009`: When a project is deleted or archived, new telemetry for its environments is blocked immediately; existing telemetry remains readable according to retention rules.

## 4.2 Environments under Project

- `FR-PROJ-010`: A project is created without an auto-provisioned default environment.
- `FR-PROJ-011`: Project onboarding requires at least one environment to be defined before telemetry can be active.
- `FR-PROJ-012`: Environment names are unique within the same project.
- `FR-PROJ-013`: Environments can be created, renamed, and configured (status, metadata) by authorized users.
- `FR-PROJ-014`: A project can mark one active environment as "primary" for dashboards and onboarding; this can be changed later.
- `FR-PROJ-015`: New environments can be added after project creation.
- `FR-PROJ-016`: Archived environments are excluded from active telemetry routing but preserved for historical context based on retention policy.

## 4.3 Nightwatch Environment Tokens

- `FR-PROJ-020`: Each environment receives at least one secret Nightwatch token for ingestion/auth of agents during creation, including in project-onboarding flow; environments are unusable for ingestion until at least one token exists.
- `FR-PROJ-021`: Token metadata includes environment binding, environment status, creator, creation date, and expiration policy.
- `FR-PROJ-022`: Tokens are displayed only once at creation and never shown in plaintext again.
- `FR-PROJ-023`: Secret tokens are stored as irreversible or encrypted material and are never logged in plaintext.
- `FR-PROJ-024`: The system supports token rotation per environment.
- `FR-PROJ-025`: Rotating a token replaces the primary token immediately and applies configured grace-window behavior for prior tokens (default 0s):
  - 0s: prior tokens are invalidated immediately.
  - >0s: prior tokens remain valid as deprecated grace tokens until the window expires.
- `FR-PROJ-026`: Token leakage or manual invalidation can revoke token access without deleting the environment.
- `FR-PROJ-027`: Agents authenticate using one primary token per environment; during grace window only a deprecated grace token may also be accepted.

## 4.4 Project Scoping and Routing

- `FR-PROJ-030`: Every ingested event is bound to exactly one project and one environment.
- `FR-PROJ-031`: Routing of ingested events is determined by token-to-environment mapping.
- `FR-PROJ-032`: Unknown or revoked token usage results in explicit rejected status with traceable audit event.
- `FR-PROJ-033`: Project context is included in API/UI views for all project-scoped operations.
- `FR-PROJ-034`: Cross-organization data visibility is blocked by tenant isolation rules.

## 4.5 Governance and Auditing

- `FR-PROJ-040`: Creation, update, token generation, token rotation, and deletion actions are auditable.
- `FR-PROJ-041`: Audit records include actor, project/environment identifiers, organization identifier, event type, source IP, user agent, and timestamp.
- `FR-PROJ-042`: Audit filters support project, actor, environment, and event type.
- `FR-PROJ-043`: Dangerous actions (delete project, delete environment, revoke all tokens) require confirmation.

## 4.6 Analytics Integration

- `FR-PROJ-044`: A dedicated analytics module defines all record-type statistics experiences and is linked from project/environment navigation.
- `FR-PROJ-045`: Project and environment scoped access to analytics pages follows organization permission rules.

## 5. Global Behaviors

- `FR-PROJ-046`: All project/environment APIs support pagination, sorting, and filtering.
- `FR-PROJ-047`: Empty states are shown for new organizations with no projects.
- `FR-PROJ-048`: UI supports bulk environment operations by authorized users (token rotation, status change).
- `FR-PROJ-049`: Project/environment changes are reflected immediately in the navigation context.

## 6. MVP Project Scope

Required for MVP:

- Project creation/update/delete.
- Environment creation and management.
- Environment-scoped secret token generation.
- Token rotation and configured grace-window invalidation behavior.
- Project/environment audit trail.
- Analytics navigation and access controls for record-type statistics pages.

Can be deferred post-MVP:

- Environment-specific compliance metadata.
- Advanced environment health lifecycle automation.
- Token-scoped fine-grained permission segmentation beyond role/permission checks.
- Custom comparative dashboards and advanced per-type widgets not in baseline shared layout.

## 7. Acceptance Checklist

- Every `FR-PROJ-*` requirement has at least one acceptance test case.
- A project and an environment can be created and linked end-to-end.
- Environment tokens are correctly generated, rotated, and invalidated according to policy.
- Telemetry events route only when token and project/environment mapping is valid.
- No cross-organization project/environment data leak.


## Technical Specifications

See dedicated technical specification: [specs-technical.md](./specs-technical.md)

## Related Resources

- **Technical Spec**: [specs-technical.md](./specs-technical.md)
- **Related Specs**: [organisation/specs.md](../organisation/specs.md), [api/specs.md](../api/specs.md), [analytics/specs.md](../analytics/specs.md)
