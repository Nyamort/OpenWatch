<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\OrganizationMember;
use App\Models\User;

test('rotating a token flashes the new token to the session', function () {
    $owner = User::factory()->create();
    $org = (new CreateOrganization)->handle($owner, ['name' => 'Acme', 'slug' => 'acme-'.uniqid()]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'app-'.uniqid()]);
    $result = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production', 'slug' => 'prod-'.uniqid(), 'type' => 'production',
    ]);
    $environment = $result->environment;

    $response = $this->actingAs($owner)
        ->post("/settings/organizations/{$org->slug}/applications/{$project->slug}/environments/{$environment->slug}/rotate-token");

    $response->assertRedirect();
    $response->assertSessionHas('environment_token');
    $response->assertSessionHas('environment_token_name', $environment->name);
});

test('rotating a token generates a new active token and deprecates the old one', function () {
    $owner = User::factory()->create();
    $org = (new CreateOrganization)->handle($owner, ['name' => 'Acme', 'slug' => 'acme-'.uniqid()]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'app-'.uniqid()]);
    $result = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Staging', 'slug' => 'staging-'.uniqid(), 'type' => 'staging',
    ]);
    $environment = $result->environment;

    $this->actingAs($owner)
        ->post("/settings/organizations/{$org->slug}/applications/{$project->slug}/environments/{$environment->slug}/rotate-token");

    $environment->refresh();

    expect($environment->projectTokens()->where('status', 'active')->count())->toBe(1)
        ->and($environment->projectTokens()->where('status', 'deprecated')->count())->toBe(1);
});

test('viewer cannot rotate a token', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $org = (new CreateOrganization)->handle($owner, ['name' => 'Acme', 'slug' => 'acme-'.uniqid()]);

    $viewerRole = $org->roles()->where('slug', 'viewer')->first();
    OrganizationMember::create([
        'organization_id' => $org->id,
        'user_id' => $viewer->id,
        'organization_role_id' => $viewerRole->id,
    ]);

    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'app-'.uniqid()]);
    $result = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production', 'slug' => 'prod-'.uniqid(), 'type' => 'production',
    ]);
    $environment = $result->environment;

    $response = $this->actingAs($viewer)
        ->post("/settings/organizations/{$org->slug}/applications/{$project->slug}/environments/{$environment->slug}/rotate-token");

    $response->assertForbidden();
});
