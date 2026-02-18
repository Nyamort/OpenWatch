# Documentation — Laravel Nightwatch Platform

This folder contains all project specifications, organized by bounded context (DDD-style).

## How to read this documentation

```
specs.md              → Functional requirements (the WHAT)
specs-technical.md    → Technical specifications (the HOW)
tasks/NNN-*.md        → Implementation tasks (the WHEN)
```

Always start with `specs.md` to understand requirements, then `specs-technical.md` for implementation decisions, then `tasks/` for sprint breakdown.

---

## Global Architecture

- [`architecture.md`](./architecture.md) — Overall architecture, bounded contexts, persistence strategy, SOLID principles, delivery phasing

---

## Glossary

- [`glossary.md`](./glossary.md) — Definitions of all shared technical terms

---

## Modules by Domain

### Identity & Access (Auth)
| File | Description |
|------|-------------|
| [`auth/specs.md`](./auth/specs.md) | Functional requirements (FR-AUTH-001 to 043) |
| [`auth/specs-technical.md`](./auth/specs-technical.md) | Fortify stack, data model, 2FA, sessions, API contracts, test strategy |
| [`auth/tasks/001-auth-foundation-core.md`](./auth/tasks/001-auth-foundation-core.md) | Foundation flows |
| [`auth/tasks/002-auth-verification-reset.md`](./auth/tasks/002-auth-verification-reset.md) | Email verification, password reset |
| [`auth/tasks/003-auth-2fa-sessions.md`](./auth/tasks/003-auth-2fa-sessions.md) | 2FA TOTP + session management |
| [`auth/tasks/004-auth-org-scoped-policies.md`](./auth/tasks/004-auth-org-scoped-policies.md) | Org-scoped authorization policies |

### Organisation
| File | Description |
|------|-------------|
| [`organisation/specs.md`](./organisation/specs.md) | Functional requirements (FR-ORG-001 to 055) |
| [`organisation/specs-technical.md`](./organisation/specs-technical.md) | Multi-tenant architecture, members, roles, permissions, audit |
| [`organisation/tasks/007-org-quotas-switcher.md`](./organisation/tasks/007-org-quotas-switcher.md) | Quotas and org switcher |
| [`organisation/tasks/008-org-audit-compliance.md`](./organisation/tasks/008-org-audit-compliance.md) | Audit and compliance |

### Projects & Environments
| File | Description |
|------|-------------|
| [`projects/specs.md`](./projects/specs.md) | Functional requirements (FR-PROJ-001 to 049) |
| [`projects/specs-technical.md`](./projects/specs-technical.md) | Data model, token lifecycle, health status, API contracts, test strategy |

### API Ingestion (Agent)
| File | Description |
|------|-------------|
| [`api/specs.md`](./api/specs.md) | Functional requirements (FR-API-001 to 049) |
| [`api/specs-technical.md`](./api/specs-technical.md) | Agent auth, token model, ingestion contract, concurrency control |
| [`api/tasks/011-api-agent-auth.md`](./api/tasks/011-api-agent-auth.md) | Agent authentication |
| [`api/tasks/012-api-ingest-endpoint.md`](./api/tasks/012-api-ingest-endpoint.md) | Ingestion endpoint |
| [`api/tasks/013-api-payload-types.md`](./api/tasks/013-api-payload-types.md) | Payload types and validation |
| [`api/tasks/014-api-concurrency-backoff.md`](./api/tasks/014-api-concurrency-backoff.md) | Concurrency and backoff |

### Analytics (Observability/Telemetry)

#### Overview
| File | Description |
|------|-------------|
| [`analytics/specs.md`](./analytics/specs.md) | Functional requirements (FR-AN-*) |
| [`analytics/specs-technical.md`](./analytics/specs-technical.md) | Storage model, query pipeline, bucketing, index strategy, SLA targets |

#### Implementation Tasks
| File | Description |
|------|-------------|
| [`analytics/tasks/015-analytics-shared-shell.md`](./analytics/tasks/015-analytics-shared-shell.md) | Shared shell and context engine |
| [`analytics/tasks/016-analytics-request-suite.md`](./analytics/tasks/016-analytics-request-suite.md) | Request analytics suite |
| [`analytics/tasks/017-analytics-query-log-mail-suite.md`](./analytics/tasks/017-analytics-query-log-mail-suite.md) | Query, log, mail |
| [`analytics/tasks/018-analytics-cache-event.md`](./analytics/tasks/018-analytics-cache-event.md) | Cache events analytics |
| [`analytics/tasks/019-analytics-command-job-suite.md`](./analytics/tasks/019-analytics-command-job-suite.md) | Command and jobs |
| [`analytics/tasks/020-analytics-outgoing-notification-task.md`](./analytics/tasks/020-analytics-outgoing-notification-task.md) | Outgoing requests, notifications, scheduled tasks |
| [`analytics/tasks/021-analytics-exception-user-suite.md`](./analytics/tasks/021-analytics-exception-user-suite.md) | Exceptions and users |

#### Analytics Pages by Record Type

| Type | Functional Spec | Technical Spec | Additional Pages |
|------|----------------|----------------|-----------------|
| `request` | [`request/request.md`](./analytics/request/request.md) | [`request-technical.md`](./analytics/request/request-technical.md) | [request-route](./analytics/request/request-route.md), [request-detail](./analytics/request/request-detail.md) |
| `query` | [`query/query.md`](./analytics/query/query.md) | [`query-technical.md`](./analytics/query/query-technical.md) | [query-detail](./analytics/query/query-detail.md) |
| `cache-event` | [`cache-event/cache-event.md`](./analytics/cache-event/cache-event.md) | [`cache-event-technical.md`](./analytics/cache-event/cache-event-technical.md) | — |
| `command` | [`command/command.md`](./analytics/command/command.md) | [`command-technical.md`](./analytics/command/command-technical.md) | [command-detail](./analytics/command/command-detail.md), [command-run-detail](./analytics/command/command-run-detail.md) |
| `log` | [`log/log.md`](./analytics/log/log.md) | [`log-technical.md`](./analytics/log/log-technical.md) | [log-detail](./analytics/log/log-detail.md) |
| `notification` | [`notification/notification.md`](./analytics/notification/notification.md) | [`notification-technical.md`](./analytics/notification/notification-technical.md) | [notification-detail](./analytics/notification/notification-detail.md) |
| `mail` | [`mail/mail.md`](./analytics/mail/mail.md) | [`mail-technical.md`](./analytics/mail/mail-technical.md) | [mail-detail](./analytics/mail/mail-detail.md) |
| `jobs` | [`jobs/jobs.md`](./analytics/jobs/jobs.md) | [`jobs-technical.md`](./analytics/jobs/jobs-technical.md) | [job-detail](./analytics/jobs/job-detail.md), [attempt-detail](./analytics/jobs/attempt-detail.md) |
| `scheduled-task` | [`scheduled-task/scheduled-task.md`](./analytics/scheduled-task/scheduled-task.md) | [`scheduled-task-technical.md`](./analytics/scheduled-task/scheduled-task-technical.md) | [scheduled-task-detail](./analytics/scheduled-task/scheduled-task-detail.md), [run-detail](./analytics/scheduled-task/scheduled-task-run-detail.md) |
| `outgoing-request` | [`outgoing-request/outgoing-request.md`](./analytics/outgoing-request/outgoing-request.md) | [`outgoing-request-technical.md`](./analytics/outgoing-request/outgoing-request-technical.md) | [outgoing-request-detail](./analytics/outgoing-request/outgoing-request-detail.md) |
| `exception` | [`exception/exception.md`](./analytics/exception/exception.md) | [`exception-technical.md`](./analytics/exception/exception-technical.md) | [exception-detail](./analytics/exception/exception-detail.md) |
| `user` | [`user/user.md`](./analytics/user/user.md) | [`user-technical.md`](./analytics/user/user-technical.md) | [user-detail](./analytics/user/user-detail.md) |

### Issues (Incident Management)
| File | Description |
|------|-------------|
| [`issues/specs.md`](./issues/specs.md) | Functional requirements (FR-ISS-001 to 051) |
| [`issues/specs-technical.md`](./issues/specs-technical.md) | Domain entities, deduplication, lifecycle, bulk ops, RBAC |
| [`issues/tasks/022-issues-core-creation.md`](./issues/tasks/022-issues-core-creation.md) | Core creation and deduplication |
| [`issues/tasks/023-issues-list-lifecycle.md`](./issues/tasks/023-issues-list-lifecycle.md) | List and lifecycle management |
| [`issues/tasks/024-issues-detail-collab.md`](./issues/tasks/024-issues-detail-collab.md) | Detail page and collaboration |

### Alerts
| File | Description |
|------|-------------|
| [`alerts/specs.md`](./alerts/specs.md) | Functional requirements (FR-ALERT-001 to 034) |
| [`alerts/specs-technical.md`](./alerts/specs-technical.md) | Threshold rules, evaluation engine, notifications, audit |
| [`alerts/tasks/025-alerts-rule-configuration.md`](./alerts/tasks/025-alerts-rule-configuration.md) | Rule configuration |
| [`alerts/tasks/026-alerts-evaluation-notify.md`](./alerts/tasks/026-alerts-evaluation-notify.md) | Evaluation and notification delivery |

### Dashboard
| File | Description |
|------|-------------|
| [`dashboard/specs.md`](./dashboard/specs.md) | Functional requirements (FR-DB-001 to 031) |
| [`dashboard/specs-technical.md`](./dashboard/specs-technical.md) | Data sources, API contracts, caching, performance SLA |
| [`dashboard/tasks/027-dashboard-experience.md`](./dashboard/tasks/027-dashboard-experience.md) | Dashboard experience |

### User Settings
| File | Description |
|------|-------------|
| [`user-settings/specs.md`](./user-settings/specs.md) | Functional requirements (FR-USR-001 to 024) |
| [`user-settings/specs-technical.md`](./user-settings/specs-technical.md) | Profile, preferences, notifications, password, session management |
| [`user-settings/tasks/028-user-settings-core.md`](./user-settings/tasks/028-user-settings-core.md) | Core settings |
| [`user-settings/tasks/032-auth-user-notifications-completion.md`](./user-settings/tasks/032-auth-user-notifications-completion.md) | User notification preferences |

### Cross-Cutting Architecture
| File | Description |
|------|-------------|
| [`architecture/tasks/029-cross-cutting-architecture-rules.md`](./architecture/tasks/029-cross-cutting-architecture-rules.md) | Cross-cutting architecture rules |
| [`architecture/tasks/030-cross-cutting-persistence-observability.md`](./architecture/tasks/030-cross-cutting-persistence-observability.md) | Persistence and observability patterns |

---

## Functional Requirement ID Reference

| Prefix | Module |
|--------|--------|
| `FR-AUTH-*` | Authentication |
| `FR-ORG-*` | Organisation |
| `FR-PROJ-*` | Projects & Environments |
| `FR-API-*` | API Ingestion |
| `FR-AN-*` | Analytics (shared behavior) |
| `FR-AN-REQ-REQ-*` | Analytics — Request |
| `FR-AN-REQ-EXC-*` | Analytics — Exception |
| `FR-AN-EXDETAIL-*` | Analytics — Exception Detail |
| `FR-ISS-*` | Issues |
| `FR-ALERT-*` | Alerts |
| `FR-DB-*` | Dashboard |
| `FR-USR-*` | User Settings |
