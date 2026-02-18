# Task Execution Plan (Order of Implementation)

## Goal
Deliver all tasks in dependency-safe order so each layer is built on stable primitives (auth + tenant context + persistence + ingestion) before advanced modules.

## Execution principles
- Follow strict dependency chain: **foundation → kernel APIs → shared analytics shell → domain modules → cross links → dashboards/UX hardening**.
- Do not start module-specific functionality before tenant isolation and policies are in place.
- For each phase, complete P0 tasks first, then P1.

## Phase 1: Platform foundation (blocking layer)

### 1.1 Authentication base
1. `docs/auth/tasks/001-auth-foundation-core.md`
2. `docs/auth/tasks/002-auth-verification-reset.md`
3. `docs/auth/tasks/003-auth-2fa-sessions.md`
4. `docs/auth/tasks/004-auth-org-scoped-policies.md`

### 1.2 Multi-tenant foundation
5. `docs/organisation/tasks/005-organisation-lifecycle.md`
6. `docs/organisation/tasks/006-org-memberships-roles.md`
7. `docs/organisation/tasks/007-org-quotas-switcher.md`

### 1.3 Infrastructure / architecture gates
8. `docs/architecture/tasks/030-cross-cutting-persistence-observability.md`
9. `docs/architecture/tasks/029-cross-cutting-architecture-rules.md`

### 1.4 Assets and ingress bootstrap
10. `docs/projects/tasks/009-project-management-core.md`
11. `docs/projects/tasks/010-project-token-lifecycle.md`

## Phase 2: Ingestion/API and core security contract

12. `docs/api/tasks/011-api-agent-auth.md`
13. `docs/api/tasks/012-api-ingest-endpoint.md`
14. `docs/api/tasks/013-api-payload-types.md`
15. `docs/api/tasks/014-api-concurrency-backoff.md`

### Why this order
- `011` requires organization/project/token context from phases 1.
- `012` needs stable auth and token model from `011`.
- `013` depends on accepted request shape and ingest contract.
- `014` depends on ingest path and Redis/cache infra.

## Phase 3: Shared analytics runtime
16. `docs/analytics/tasks/015-analytics-shared-shell.md`

## Phase 4: Analytics module (P0 first, then P1)
### P0 analytics
17. `docs/analytics/tasks/016-analytics-request-suite.md`
18. `docs/analytics/tasks/017-analytics-query-log-mail-suite.md`
19. `docs/analytics/tasks/019-analytics-command-job-suite.md`

### P1 analytics
20. `docs/analytics/tasks/018-analytics-cache-event.md`
21. `docs/analytics/tasks/020-analytics-outgoing-notification-task.md`
22. `docs/analytics/tasks/021-analytics-exception-user-suite.md`

## Phase 5: Operational workflows
### Issue workflows
23. `docs/issues/tasks/022-issues-core-creation.md`
24. `docs/issues/tasks/023-issues-list-lifecycle.md`
25. `docs/issues/tasks/024-issues-detail-collab.md`
26. `docs/issues/tasks/031-issues-alerts-integration.md`

### Alert workflows
27. `docs/alerts/tasks/025-alerts-rule-configuration.md`
28. `docs/alerts/tasks/026-alerts-evaluation-notify.md`

## Phase 6: Experience and settings hardening
29. `docs/dashboard/tasks/027-dashboard-experience.md`
30. `docs/user-settings/tasks/028-user-settings-core.md`
31. `docs/user-settings/tasks/032-auth-user-notifications-completion.md`

## Parallelization suggestions (safe inside same phase)
- In Phase 1, tasks `007` and `008` can run in parallel once `006` is in place.
- In Phase 3/4, `017` and `019` can be implemented in parallel after `015` if the analytics read-model base is ready.
- In Phase 5, `024` and `026` can run in parallel once `023` is available.
- In Phase 6, `028` can start as soon as auth policy and user identity wiring are stable; `027` can start after shared analytics shell (`015`) and persistence model (`030`).

## Recommended milestone gates
- **Milestone M1**: Foundation complete (tasks 1 to 11)
- **Milestone M2**: Ingestion fully contract-compliant (tasks 12 to 15)
- **Milestone M3**: Analytics baseline (tasks 16 to 22)
- **Milestone M4**: Operations workflows (tasks 23 to 28)
- **Milestone M5**: Dashboard and user settings stabilization (tasks 29 to 31)

## Suggested sequencing by priority
- **P0 first**: `001-014`, `005`, `006`, `008`, `009`, `010`, `015`, `016`, `017`, `019`, `022`, `023`, `024`, `025`, `027`.
- **P1 second**: `007`, `018`, `020`, `021`, `026`, `028`, `030`, `031`, `032`.

## Delivery recommendation
Execute in this order exactly for first pass, then use regressions pass only on previously completed layers before moving to next phase.
