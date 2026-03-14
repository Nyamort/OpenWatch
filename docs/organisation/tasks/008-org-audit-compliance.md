# Task T-008: Organization Audit Log and Compliance Controls
- Domain: `organisation`
- Status: `not started`
- Priority: `P1`
- Dependencies: `T-005`, `T-006`

## Description
Implement an immutable organization audit log capturing all critical org events (create/update/delete, invite, role change, ownership transfer, token rotation, plan change). Add retention policy with configurable anonymization for stale records and filterable audit viewer for Owner/Admin.

## How to implement
1. Create `organization_audit_events` table: `id`, `organization_id`, `actor_id`, `event_type` (enum), `target_type`, `target_id`, `metadata` (JSON), `ip`, `user_agent`, `created_at` — no `updated_at`, no soft-delete (immutable).
2. Create `OrganizationAuditEvent` model with append-only writes only (disable `update()` and `delete()` at model level).
3. Implement `AuditLogger` service used by all org actions (inject into `CreateOrganization`, `InviteMember`, `TransferOwnership`, etc.).
4. Add `RecordAuditEvent` listener on an `OrganizationAuditableEvent` contract so actions emit events rather than calling the logger directly.
5. Implement retention worker `AnonymizeStaleAuditEvents` job: replaces `actor_id` / `ip` / `user_agent` with anonymized markers after configured retention window while keeping event type and timestamps.
6. Build audit viewer endpoint with filters: `event_type`, `actor_id`, date range, pagination — Owner/Admin only.
7. Write feature tests: events are recorded for each critical action, non-Owner cannot access audit log, anonymization job processes records beyond retention window.

## Key files to create or modify
- `database/migrations/xxxx_create_organization_audit_events_table.php`
- `app/Models/OrganizationAuditEvent.php` — append-only model
- `app/Services/Organization/AuditLogger.php`
- `app/Contracts/OrganizationAuditableEvent.php`
- `app/Jobs/AnonymizeStaleAuditEvents.php`
- `app/Http/Controllers/Organization/AuditController.php`
- `routes/web.php` — audit routes
- `tests/Feature/Organization/OrganizationAuditTest.php`

## Acceptance criteria
- [ ] Every critical org action (invite, role change, delete, transfer, token rotation) produces an audit record
- [ ] Audit records include actor ID, IP, user agent, timestamp, and event type
- [ ] Audit records cannot be modified or deleted by any application-level action
- [ ] Audit log is filterable by event type, actor, and date range
- [ ] Non-Owner/Admin members cannot access the audit log
- [ ] Anonymization job replaces PII fields on records beyond retention window without deleting the record
- [ ] Anonymized records still preserve event type and timestamp for compliance integrity

## Related specs
- [Functional spec](../specs.md) — `FR-ORG-043` to `FR-ORG-047`
- [Technical spec](../specs-technical.md)
