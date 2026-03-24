<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\ArchiveProject;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Models\Project;
use App\Models\User;

test('it creates a project with unique slug', function () {
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Acme', 'slug' => 'acme']);

    $project = (new CreateProject)->handle($org, [
        'name' => 'My App',
        'slug' => 'my-app',
    ]);

    expect($project)->toBeInstanceOf(Project::class)
        ->and($project->name)->toBe('My App')
        ->and($project->slug)->toBe('my-app')
        ->and($project->organization_id)->toBe($org->id);
});

test('it generates unique slugs for same-name projects within org', function () {
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Acme', 'slug' => 'acme-dup']);

    $first = (new CreateProject)->handle($org, ['name' => 'My App']);
    $second = (new CreateProject)->handle($org, ['name' => 'My App']);

    expect($first->slug)->toBe('my-app')
        ->and($second->slug)->toBe('my-app-1');
});

test('it allows same slug in different orgs', function () {
    $user = User::factory()->create();
    $org1 = (new CreateOrganization)->handle($user, ['name' => 'Org One', 'slug' => 'org-one-slug']);
    $org2 = (new CreateOrganization)->handle($user, ['name' => 'Org Two', 'slug' => 'org-two-slug']);

    $project1 = (new CreateProject)->handle($org1, ['name' => 'App', 'slug' => 'app']);
    $project2 = (new CreateProject)->handle($org2, ['name' => 'App', 'slug' => 'app']);

    expect($project1->id)->not->toBe($project2->id)
        ->and($project1->slug)->toBe($project2->slug);
});

test('it archives a project', function () {
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Acme', 'slug' => 'acme-archive']);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'app-to-archive']);

    (new ArchiveProject)->handle($project);

    $project->refresh();
    expect($project->archived_at)->not->toBeNull();

    $activeProjects = $org->projects()->active()->get();
    expect($activeProjects)->toHaveCount(0);
});

test('it creates an environment under a project', function () {
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Acme', 'slug' => 'acme-env']);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'app-env']);

    $environment = (new CreateEnvironment(new \App\Actions\Projects\GenerateToken))->handle($project, [
        'name' => 'Production',
        'slug' => 'production',
    ])->environment;

    expect($environment->name)->toBe('Production')
        ->and($environment->slug)->toBe('production')
        ->and($environment->project_id)->toBe($project->id);
});

test('it creates an initial token when environment is created', function () {
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Acme', 'slug' => 'acme-token-init']);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'app-token-init']);

    $environment = (new CreateEnvironment(new \App\Actions\Projects\GenerateToken))->handle($project, [
        'name' => 'Staging',
        'slug' => 'staging',
    ])->environment;

    $tokens = $environment->projectTokens()->where('status', 'active')->get();
    expect($tokens)->toHaveCount(1);
});
