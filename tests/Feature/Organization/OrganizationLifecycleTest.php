<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Organization\DeleteOrganization;
use App\Actions\Organization\SwitchOrganization;
use App\Actions\Organization\UpdateOrganization;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('it creates an organization and assigns creator as owner', function () {
    $user = User::factory()->create();
    $action = new CreateOrganization;

    $org = $action->handle($user, [
        'name' => 'Acme Corp',
        'slug' => 'acme-corp',
    ]);

    expect($org)->toBeInstanceOf(Organization::class)
        ->and($org->name)->toBe('Acme Corp')
        ->and($org->slug)->toBe('acme-corp');

    $user->refresh();
    expect($user->active_organization_id)->toBe($org->id);

    $member = $org->members()->where('user_id', $user->id)->first();
    expect($member)->not->toBeNull();

    $ownerRole = $org->roles()->where('slug', 'owner')->first();
    expect($member->organization_role_id)->toBe($ownerRole->id);
});

test('it rejects duplicate slug', function () {
    $user = User::factory()->create();
    $action = new CreateOrganization;

    $action->handle($user, ['name' => 'First Org', 'slug' => 'my-org']);

    $anotherUser = User::factory()->create();

    expect(fn () => $action->handle($anotherUser, ['name' => 'Second Org', 'slug' => 'my-org']))
        ->toThrow(ValidationException::class);
});

test('it updates organization fields', function () {
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Old Name', 'slug' => 'old-slug']);

    $updated = (new UpdateOrganization)->handle($org, [
        'name' => 'New Name',
        'slug' => 'new-slug',
        'timezone' => 'America/New_York',
    ]);

    expect($updated->name)->toBe('New Name')
        ->and($updated->slug)->toBe('new-slug')
        ->and($updated->timezone)->toBe('America/New_York');
});

test('it soft-deletes organization', function () {
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Doomed Corp', 'slug' => 'doomed-corp']);

    (new DeleteOrganization)->handle($org, $user);

    expect(Organization::find($org->id))->toBeNull();
    expect(Organization::withTrashed()->find($org->id))->not->toBeNull();
});

test('it switches active organization', function () {
    $user = User::factory()->create();
    $org1 = (new CreateOrganization)->handle($user, ['name' => 'Org One', 'slug' => 'org-one']);
    $org2 = (new CreateOrganization)->handle($user, ['name' => 'Org Two', 'slug' => 'org-two']);

    (new SwitchOrganization)->handle($user, $org1);

    $user->refresh();
    expect($user->active_organization_id)->toBe($org1->id);
});

test('it blocks switching to org where user is not a member', function () {
    $user = User::factory()->create();
    $owner = User::factory()->create();
    $org = (new CreateOrganization)->handle($owner, ['name' => 'Other Org', 'slug' => 'other-org']);

    expect(fn () => (new SwitchOrganization)->handle($user, $org))
        ->toThrow(ValidationException::class);
});
