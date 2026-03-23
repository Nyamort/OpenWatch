<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\OrganizationMember;
use App\Models\User;

test('member of org A cannot access org B resources', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    $orgA = (new CreateOrganization)->handle($userA, ['name' => 'Org A', 'slug' => 'org-a-'.uniqid()]);
    $orgB = (new CreateOrganization)->handle($userB, ['name' => 'Org B', 'slug' => 'org-b-'.uniqid()]);

    $projectB = (new CreateProject)->handle($orgB, ['name' => 'App', 'slug' => 'app-b-'.uniqid()]);
    $envB = (new CreateEnvironment(new GenerateToken))->handle($projectB, [
        'name' => 'Prod', 'slug' => 'prod-b-'.uniqid(), 'type' => 'production',
    ])->environment;

    // User A tries to access Org B's analytics
    $response = $this->actingAs($userA)
        ->get("/organizations/{$orgB->slug}/projects/{$projectB->slug}/environments/{$envB->slug}/analytics/requests");

    $response->assertStatus(403);
});

test('viewer cannot update organization', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $org = (new CreateOrganization)->handle($owner, ['name' => 'Test Org', 'slug' => 'test-org-'.uniqid()]);

    $viewerRole = $org->roles()->where('slug', 'viewer')->first();
    OrganizationMember::create([
        'organization_id' => $org->id,
        'user_id' => $viewer->id,
        'organization_role_id' => $viewerRole->id,
    ]);

    $response = $this->actingAs($viewer)->patch("/organizations/{$org->slug}", ['name' => 'Hacked']);
    $response->assertStatus(403);
});

test('owner can update organization', function () {
    $owner = User::factory()->create();
    $org = (new CreateOrganization)->handle($owner, ['name' => 'My Org', 'slug' => 'my-org-'.uniqid()]);

    $response = $this->actingAs($owner)->patch("/organizations/{$org->slug}", [
        'name' => 'Updated Org',
        'slug' => $org->slug,
    ]);
    $response->assertRedirect();
    $this->assertDatabaseHas('organizations', ['id' => $org->id, 'name' => 'Updated Org']);
});
