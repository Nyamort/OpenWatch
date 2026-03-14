# Task T-007: Organization Limits, Plan Gates, and Org Switching
- Domain: `organisation`
- Status: `not started`
- Priority: `P1`
- Dependencies: `T-005`, `T-006`

## Description
Implement organization-level configurable quotas (members, projects, ingest volume, retention), warning thresholds, hard-limit enforcement, near-real-time usage visibility, and safe multi-org context switching with persisted last-active org per user.

## How to implement
1. Create `organization_plans` and `organization_usage_snapshots` tables; attach a plan to each org.
2. Implement `QuotaService`: expose `check(string $resource, Organization $org): QuotaStatus` returning `ok | warning | exceeded`.
3. Add quota gate in `CreateProject`, `InviteMember`, and ingestion pipeline entry points — call `QuotaService` before proceeding.
4. On `exceeded`: return actionable error with quota context (current usage, limit, upgrade path if applicable).
5. Expose usage metrics endpoint for Owner/Admin: reads from `organization_usage_snapshots` refreshed asynchronously (job or observer).
6. Implement org switcher: `SwitchOrganization` action validates membership, updates `users.current_organization_id` (or session), invalidates permission cache, returns new context atomically.
7. Persist last-active org per user so it is restored on next session.
8. Write feature tests: hard-limit blocks creation, warning threshold is detected, org switch updates context, non-member cannot switch to an org.

## Key files to create or modify
- `database/migrations/xxxx_create_organization_plans_table.php`
- `database/migrations/xxxx_create_organization_usage_snapshots_table.php`
- `app/Services/Organization/QuotaService.php`
- `app/Actions/Organization/SwitchOrganization.php`
- `app/Http/Controllers/Organization/OrganizationSwitcherController.php`
- `app/Http/Controllers/Organization/UsageController.php`
- `app/Jobs/RefreshOrganizationUsageSnapshot.php`
- `resources/js/components/organization-switcher.tsx` — UI switcher component
- `tests/Feature/Organization/OrganizationQuotasTest.php`
- `tests/Feature/Organization/OrganizationSwitcherTest.php`

## Acceptance criteria
- [ ] Creating a project beyond the plan limit is rejected with a quota-exceeded error
- [ ] Warning threshold is surfaced before the hard limit is reached
- [ ] Usage metrics are visible to Owner and Admin in near real-time
- [ ] Org switch is atomic: permissions and visible data update on the next request
- [ ] Non-member cannot switch to another organization
- [ ] Last-active org is restored on next login
- [ ] Plan change events are audit-logged with actor and timestamp

## Related specs
- [Functional spec](../specs.md) — `FR-ORG-035` to `FR-ORG-051`
- [Technical spec](../specs-technical.md)
