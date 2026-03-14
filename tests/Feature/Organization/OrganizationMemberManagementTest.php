<?php

use App\Actions\Organization\AcceptInvitation;
use App\Actions\Organization\CreateOrganization;
use App\Actions\Organization\InviteMember;
use App\Actions\Organization\RemoveMember;
use App\Actions\Organization\TransferOwnership;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

test('it invites a member by email', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $org = (new CreateOrganization)->handle($owner, ['name' => 'Test Org', 'slug' => 'test-org-invite']);

    $developerRole = $org->roles()->where('slug', 'developer')->first();

    $invitation = (new InviteMember)->handle($org, $owner, [
        'email' => 'newmember@example.com',
        'organization_role_id' => $developerRole->id,
    ]);

    expect($invitation)->toBeInstanceOf(OrganizationInvitation::class)
        ->and($invitation->email)->toBe('newmember@example.com')
        ->and($invitation->organization_id)->toBe($org->id)
        ->and($invitation->accepted_at)->toBeNull();

    Notification::assertSentOnDemand(
        \App\Notifications\OrganizationInvitationNotification::class
    );
});

test('it rejects expired invitation', function () {
    $owner = User::factory()->create();
    $org = (new CreateOrganization)->handle($owner, ['name' => 'Expired Org', 'slug' => 'expired-org']);
    $developerRole = $org->roles()->where('slug', 'developer')->first();

    $invitation = OrganizationInvitation::factory()->expired()->create([
        'organization_id' => $org->id,
        'organization_role_id' => $developerRole->id,
        'invited_by_user_id' => $owner->id,
    ]);

    $newUser = User::factory()->create();

    expect(fn () => (new AcceptInvitation)->handle($invitation, $newUser))
        ->toThrow(ValidationException::class);
});

test('it blocks re-using accepted invitation', function () {
    $owner = User::factory()->create();
    $org = (new CreateOrganization)->handle($owner, ['name' => 'Accepted Org', 'slug' => 'accepted-org']);
    $developerRole = $org->roles()->where('slug', 'developer')->first();

    $firstUser = User::factory()->create();
    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $org->id,
        'organization_role_id' => $developerRole->id,
        'invited_by_user_id' => $owner->id,
    ]);

    (new AcceptInvitation)->handle($invitation, $firstUser);

    $secondUser = User::factory()->create();
    $invitation->refresh();

    expect(fn () => (new AcceptInvitation)->handle($invitation, $secondUser))
        ->toThrow(ValidationException::class);
});

test('it removes a member', function () {
    $owner = User::factory()->create();
    $org = (new CreateOrganization)->handle($owner, ['name' => 'Remove Org', 'slug' => 'remove-org']);
    $developerRole = $org->roles()->where('slug', 'developer')->first();

    $memberUser = User::factory()->create();
    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $org->id,
        'organization_role_id' => $developerRole->id,
        'invited_by_user_id' => $owner->id,
    ]);
    $member = (new AcceptInvitation)->handle($invitation, $memberUser);

    (new RemoveMember)->handle($org, $member);

    expect($org->members()->where('user_id', $memberUser->id)->exists())->toBeFalse();
});

test('it blocks removing the last owner', function () {
    $owner = User::factory()->create();
    $org = (new CreateOrganization)->handle($owner, ['name' => 'Last Owner Org', 'slug' => 'last-owner-org']);

    $ownerMember = $org->members()->where('user_id', $owner->id)->first();

    expect(fn () => (new RemoveMember)->handle($org, $ownerMember))
        ->toThrow(ValidationException::class);
});

test('it transfers ownership', function () {
    $owner = User::factory()->create();
    $org = (new CreateOrganization)->handle($owner, ['name' => 'Transfer Org', 'slug' => 'transfer-org']);
    $developerRole = $org->roles()->where('slug', 'developer')->first();

    $newOwnerUser = User::factory()->create();
    $invitation = OrganizationInvitation::factory()->create([
        'organization_id' => $org->id,
        'organization_role_id' => $developerRole->id,
        'invited_by_user_id' => $owner->id,
    ]);
    $newOwnerMember = (new AcceptInvitation)->handle($invitation, $newOwnerUser);

    $currentOwnerMember = $org->members()->where('user_id', $owner->id)->first();

    (new TransferOwnership)->handle($org, $currentOwnerMember, $newOwnerMember);

    $newOwnerMember->refresh();
    $currentOwnerMember->refresh();

    $ownerRole = $org->roles()->where('slug', 'owner')->first();
    $adminRole = $org->roles()->where('slug', 'admin')->first();

    expect($newOwnerMember->organization_role_id)->toBe($ownerRole->id)
        ->and($currentOwnerMember->organization_role_id)->toBe($adminRole->id);
});
