<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\User;
use App\Services\Analytics\AnalyticsContextResolver;

test('it resolves analytics context from environment slug', function () {
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Test Org', 'slug' => 'test-org-ctx']);
    $project = (new CreateProject)->handle($org, ['name' => 'My App']);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production',
        'type' => 'production',
    ])->environment;

    $resolver = new AnalyticsContextResolver;
    $ctx = $resolver->resolve($env->slug, $user);

    expect($ctx->organization->id)->toBe($org->id)
        ->and($ctx->project->id)->toBe($project->id)
        ->and($ctx->environment->id)->toBe($env->id);
});

test('it throws ModelNotFoundException for unknown environment slug', function () {
    $user = User::factory()->create();

    $resolver = new AnalyticsContextResolver;

    expect(fn () => $resolver->resolve('non-existent-env', $user))
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});
