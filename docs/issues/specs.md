# Functional Specifications - Issue Workflow

## 1. Purpose

This document defines functional requirements for issue creation, triage, and resolution workflows in a Laravel Nightwatch-like platform.

## 2. Scope

Included:

- Issue creation from analytics contexts (for example `To Issue` from exception/request/job/command detail pages).
- Issue list workflows with type tabs, search, and state/ownership filters.
- Issue detail workflows with manage panel, occurrences, and activity/comments.
- Traceability between an issue and linked telemetry events.
- Auditability of critical issue workflow actions.

Excluded:

- External ticketing system implementation details.
- AI-assisted issue summarization internals.
- Alert rule definitions (covered in alerting specifications).

## 3. Actors

- `Organization Owner`: full access to issue workflows in organization scope.
- `Organization Admin`: full issue workflow access in organization scope.
- `Organization Developer`: creates, updates, and resolves issues in authorized scopes.
- `Organization Viewer`: read-only access to issue lists and details.

## 4. Functional Requirements

## 4.1 Issue Creation and Source Context

- `FR-ISS-001`: Authorized users can create an issue from a supported analytics detail page.
- `FR-ISS-002`: Issue creation captures source context at minimum:
  - organization,
  - project,
  - environment,
  - source type (for example: exception, command run, request),
  - source identifier(s).
- `FR-ISS-003`: Creating an issue from analytics preserves navigation context and returns the user to the issue detail view.
- `FR-ISS-004`: Issue creation is blocked for unauthorized users with a consistent forbidden response.

## 4.2 Issue Identity and Deduplication

- `FR-ISS-005`: Each issue has a stable unique identifier in organization scope.
- `FR-ISS-006`: The issue includes a canonical title and source-derived summary.
- `FR-ISS-007`: The issue stores a primary fingerprint/reference to the originating telemetry signature.
- `FR-ISS-008`: Repeated creation attempts for the same active fingerprint are prevented or merged by policy to avoid duplicate active issues.

## 4.3 Issue List Page

- `FR-ISS-009`: The issue list provides record-type tabs with counters at minimum for `Exceptions` and `Performance`.
- `FR-ISS-010`: The issue list includes a free-text `Search` input.
- `FR-ISS-011`: The issue list includes status filters at minimum: `Open`, `Resolved`, and `Ignored`.
- `FR-ISS-012`: The issue list includes ownership filters at minimum: `Unassigned` and `Mine`.
- `FR-ISS-013`: The issue list table supports multi-row selection with checkboxes.
- `FR-ISS-013A`: Bulk actions available on selected issues are limited to:
  - change `status`,
  - change `assignee`,
  - change `priority`.
- `FR-ISS-013B`: Bulk actions apply only to selected rows and only for users with issue-write permissions.
- `FR-ISS-013C`: Bulk action updates are auditable per affected issue.
- `FR-ISS-014`: Issue list columns are: `id`, `issue`, `count`, `users`, `first seen`, `last seen`, `assigned`, `action`.
- `FR-ISS-015`: `issue` column shows title/class and may include a secondary message preview.
- `FR-ISS-016`: The `assigned` column explicitly represents unassigned state.
- `FR-ISS-017`: Column sorting is available for at least `id`, `count`, `users`, `first seen`, `last seen`, and `assigned`.
- `FR-ISS-018`: Row action opens the selected issue detail page.
- `FR-ISS-019`: Issue list supports pagination.

## 4.4 Issue Lifecycle and Ownership

- `FR-ISS-020`: Issues support lifecycle statuses at minimum: `open`, `resolved`, `ignored`.
- `FR-ISS-021`: Status changes are immediate for subsequent reads.
- `FR-ISS-021A`: Moving an issue to `ignored` removes it from `Open` results and exposes it in `Ignored` results.
- `FR-ISS-022`: Resolving an issue stores resolution metadata at minimum: resolver identity and resolved timestamp.
- `FR-ISS-023`: Authorized users can move resolved/ignored issues back to `open`.
- `FR-ISS-024`: Issues can be assigned to one organization member or remain `unassigned`.
- `FR-ISS-025`: Assignment changes are auditable with actor and timestamp.
- `FR-ISS-026`: Issue priority is manageable from issue detail (for example `no priority` by default).

## 4.5 Issue Detail Page

- `FR-ISS-027`: Issue detail header shows current issue context (status segment and issue id) and issue title.
- `FR-ISS-027A`: Exception and performance issues use the same issue detail page structure; source snapshot content adapts to issue type.
- `FR-ISS-028`: Issue detail includes a description editor area with `Write` and `Preview` tabs.
- `FR-ISS-029`: Issue detail supports `Copy Markdown` for description/source blocks.
- `FR-ISS-030`: Issue detail may expose `Generate Description` when generation features are enabled.
- `FR-ISS-031`: Issue detail includes a `Manage` panel with `status`, `priority`, and `assignee` controls.
- `FR-ISS-032`: Issue detail includes a `Details` section with at minimum `first seen` and `last seen`; unknown values are explicitly represented for unavailable fields.
- `FR-ISS-033`: Issue detail includes an `Occurrences` summary section grouped by environment and selected time window (for example last 14 days).
- `FR-ISS-034`: Issue detail supports subscribe/unsubscribe action for issue updates.
- `FR-ISS-035`: Issue detail displays source snapshot context (for example exception/code preview) with state badge (for example `UNHANDLED`) when available.
- `FR-ISS-036`: Source snapshot supports expandable frame groups (for example vendor frames) when stack frames exist.
- `FR-ISS-037`: Issue detail displays linked telemetry occurrences list with filters for time window and environment.
- `FR-ISS-038`: Occurrence rows include at minimum timestamp/relative time, source badge/context, actor/user hint when available, and navigation action.
- `FR-ISS-039`: Occurrences list supports pagination.
- `FR-ISS-040`: Issue detail includes an `Activity` section with lifecycle events and comments.
- `FR-ISS-041`: Users with write permission can add comments using `Write`/`Preview` composer tabs.
- `FR-ISS-042`: Issue detail provides a `Resolve issue` action in the activity/comment area for quick triage closure.
- `FR-ISS-043`: Issue detail is read-only for users without issue-write permissions.

## 4.6 Analytics Integration

- `FR-ISS-044`: Supported analytics pages may show a `To Issue` action for authorized users.
- `FR-ISS-045`: When `To Issue` is used, the new or linked issue keeps originating filters/context for investigation continuity.
- `FR-ISS-046`: Issue pages expose backlinks to originating analytics context (project, environment, source record type).

## 4.7 Auditability and Isolation

- `FR-ISS-047`: Issue create, assign, priority change, status change, subscribe change, and comment actions are audit-logged.
- `FR-ISS-048`: Audit records include actor, organization ID, issue ID, action type, and timestamp.
- `FR-ISS-049`: Issue data is tenant-isolated by organization.

## 5. Global Behaviors

- `FR-ISS-050`: Empty states are explicit when no issues match active filters.
- `FR-ISS-051`: Dangerous actions (for example bulk resolve if enabled) require explicit confirmation.

## 6. MVP Issue Scope

Required for MVP:

- Create issue from analytics detail pages.
- Exceptions and performance issue tabs in issue list.
- Open/resolved/ignored lifecycle with reopen.
- Assignee management.
- Priority management from issue detail.
- Bulk actions for status, assignee, and priority from issue list selections.
- Issue list with search, status filters, ownership filters, sorting, and pagination.
- Issue detail with manage panel, source snapshot, occurrences list, and activity/comments.
- Subscribe/unsubscribe action.
- Audit trail for create/assign/priority/status/comment/subscribe.

Can be deferred post-MVP:

- External ticket synchronization.
- Advanced deduplication policy controls and merge workflows.
- SLA/escalation automation.

## 7. Acceptance Checklist

- Each `FR-ISS-*` requirement has at least one acceptance test case.
- An issue can be created from a supported analytics source and opened in issue detail.
- Issue list filters (`Open/Resolved/Ignored`, `Unassigned/Mine`) update results consistently.
- Issue assignment, priority, and lifecycle transitions are reflected immediately.
- Linked telemetry context is visible and navigable from issue detail.
- Cross-organization issue data access is blocked.


## Technical Specifications

See dedicated technical specification: [specs-technical.md](./specs-technical.md)

## Related Resources

- **Technical Spec**: [specs-technical.md](./specs-technical.md)
- **Related Specs**: [analytics/specs.md](../analytics/specs.md), [alerts/specs.md](../alerts/specs.md)
- **Implementation Tasks**:
  - [022 - Issues Core Creation](./tasks/022-issues-core-creation.md)
  - [023 - Issues List Lifecycle](./tasks/023-issues-list-lifecycle.md)
  - [024 - Issues Detail Collab](./tasks/024-issues-detail-collab.md)
