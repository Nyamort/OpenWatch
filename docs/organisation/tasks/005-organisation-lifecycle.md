# Task T-005: Organization Lifecycle and Tenant Boundaries
- Domain: `organisation`
- Status: `not started`
- Priority: `P0`
- Dependencies: `T-001`

## Description
Implement organization create/update/soft-delete/restore with slug uniqueness, automatic owner assignment, and strict tenant isolation enforced via global Eloquent scopes on every domain model.

## How to implement
1. Create the `organizations` migration: `id`, `name`, `slug` (unique), `logo_url`, `timezone`, `locale`, `deleted_at`, timestamps.
2. Create the `Organization` Eloquent model with a `GlobalOrganizationScope` that constrains all queries to the current auth context's org.
3. Implement `CreateOrganization` action: validate unique slug globally, create org, attach creator as `Owner` member.
4. Implement `UpdateOrganization` action: name, logo, timezone, locale — validate slug uniqueness excluding self.
5. Implement `DeleteOrganization` action: soft-delete with explicit user confirmation token; require ownership.
6. Implement `RestoreOrganization` action: within configurable retention window only.
7. Register the org scope on all domain models (Project, Environment, Issue, etc.) — enforced at model boot.
8. Write feature tests for: create, slug collision, update, soft-delete + restore, cross-tenant query proof.

## Key files to create or modify
- `database/migrations/xxxx_create_organizations_table.php`
- `app/Models/Organization.php` + `GlobalOrganizationScope`
- `app/Actions/Organization/CreateOrganization.php`
- `app/Actions/Organization/UpdateOrganization.php`
- `app/Actions/Organization/DeleteOrganization.php`
- `app/Actions/Organization/RestoreOrganization.php`
- `app/Http/Controllers/Organization/OrganizationController.php`
- `routes/web.php` — org CRUD routes
- `tests/Feature/Organization/OrganizationLifecycleTest.php`

## Acceptance criteria
- [ ] Organization is created with a globally unique slug; duplicate slug is rejected with a field error
- [ ] Creator is automatically assigned the `Owner` role
- [ ] Organization can be updated (name, timezone, logo) by the owner
- [ ] Soft-delete requires owner confirmation; organization becomes invisible to all members
- [ ] Restore works within the retention window; org and all its data become accessible again
- [ ] Any query on a domain model (Project, Environment, etc.) scoped to the wrong org returns empty / 404
- [ ] Cross-tenant isolation is verified in a dedicated test using two orgs

## Related specs
- [Functional spec](../specs.md) — `FR-ORG-001` to `FR-ORG-011`
- [Technical spec](../specs-technical.md)
