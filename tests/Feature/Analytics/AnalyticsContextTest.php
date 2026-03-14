<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\User;
use App\Services\Analytics\AnalyticsContextResolver;

test('it resolves analytics context from valid slugs', function () {
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Test Org', 'slug' => 'test-org-ctx']);
    $project = (new CreateProject)->handle($org, ['name' => 'My App', 'slug' => 'my-app-ctx']);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production',
        'slug' => 'production-ctx',
        'type' => 'production',
    ]);

    $resolver = new AnalyticsContextResolver;
    $ctx = $resolver->resolve('test-org-ctx', 'my-app-ctx', 'production-ctx', $user);

    expect($ctx->organization->id)->toBe($org->id)
        ->and($ctx->project->id)->toBe($project->id)
        ->and($ctx->environment->id)->toBe($env->id);
});

test('it rejects project that does not belong to the organization', function () {
    $user = User::factory()->create();
    $org1 = (new CreateOrganization)->handle($user, ['name' => 'Org One', 'slug' => 'org-one-ctx']);
    $org2 = (new CreateOrganization)->handle($user, ['name' => 'Org Two', 'slug' => 'org-two-ctx']);

    $projectInOrg2 = (new CreateProject)->handle($org2, ['name' => 'App Two', 'slug' => 'app-two-ctx']);

    $resolver = new AnalyticsContextResolver;

    expect(fn () => $resolver->resolve('org-one-ctx', 'app-two-ctx', 'any-env', $user))
        ->toThrow(\Illuminate\Auth\Access\AuthorizationException::class);
});

test('it throws ModelNotFoundException for unknown org slug', function () {
    $user = User::factory()->create();

    $resolver = new AnalyticsContextResolver;

    expect(fn () => $resolver->resolve('non-existent-org', 'some-project', 'some-env', $user))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
