# Task T-006: Memberships, Invitations, and Roles
- Domain: `organisation`
- Status: `completed`
- Priority: `P0`
- Dependencies: `T-005`

## Description
Implement invite/accept/revoke flows with signed single-use tokens, role assignment and custom role CRUD, ownership transfer, and immediate permission propagation. Default roles (`Owner`, `Admin`, `Developer`, `Viewer`) are seeded at org creation.

## How to implement
1. Create `organization_members`, `organization_roles`, `organization_permissions`, `organization_invitations` migrations.
2. Seed default roles and their permission sets on org creation (hook into `CreateOrganization` action).
3. Implement `InviteMember` action: generate signed, single-use, expiring invitation token (sha256-stored); dispatch invitation email.
4. Implement `AcceptInvitation` action: validate signature, expiry, and single-use; create member record; mark invitation consumed.
5. Implement `RevokeInvitation` and `RemoveMember` actions with role-based guard (owner/admin only).
6. Implement ownership transfer: atomic swap requiring the new owner to be an active member; cannot remove the last owner.
7. Implement `CreateRole`, `UpdateRole`, `DeleteRole` for custom roles; block deletion of default roles.
8. Invalidate `PermissionResolver` cache on any role or membership change.
9. Write feature tests: invite flow end-to-end, expired invitation rejection, duplicate invitation, ownership transfer guard, last-owner removal block.

## Key files to create or modify
- `database/migrations/xxxx_create_organization_members_table.php`
- `database/migrations/xxxx_create_organization_roles_table.php`
- `database/migrations/xxxx_create_organization_invitations_table.php`
- `app/Models/OrganizationMember.php`
- `app/Models/OrganizationRole.php`
- `app/Models/OrganizationInvitation.php`
- `app/Actions/Organization/InviteMember.php`
- `app/Actions/Organization/AcceptInvitation.php`
- `app/Actions/Organization/RemoveMember.php`
- `app/Actions/Organization/TransferOwnership.php`
- `app/Actions/Organization/CreateRole.php`
- `app/Notifications/OrganizationInvitationNotification.php`
- `app/Http/Controllers/Organization/MemberController.php`
- `app/Http/Controllers/Organization/InvitationController.php`
- `app/Http/Controllers/Organization/RoleController.php`
- `tests/Feature/Organization/OrganizationMemberManagementTest.php`

## Acceptance criteria
- [ ] Invitation email contains a signed, single-use link that expires after configured TTL
- [ ] Accepting a used or expired invitation is rejected with a clear error
- [ ] Only `Owner` or `Admin` can invite, revoke invitations, and remove members
- [ ] Removing the last `Owner` is blocked with an actionable error
- [ ] Ownership transfer requires the new owner to be an active member
- [ ] Role changes take effect on the member's next request (cache invalidated immediately)
- [ ] Custom roles can be created and assigned; default roles cannot be deleted
- [ ] Removed member loses access immediately on next request

## Related specs
- [Functional spec](../specs.md) — `FR-ORG-012` to `FR-ORG-027`
- [Technical spec](../specs-technical.md)
