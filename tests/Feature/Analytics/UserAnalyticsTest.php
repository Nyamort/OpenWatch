<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\User;
use App\Services\ClickHouse\ClickHouseService;

function setupUserAnalyticsContext(string $suffix = ''): array
{
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'User Org '.$suffix, 'slug' => 'user-org-'.$suffix]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'user-app-'.$suffix]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production',
        'slug' => 'user-prod-'.$suffix,
        'type' => 'production',
    ])->environment;

    return compact('user', 'org', 'project', 'env');
}

function insertUserRequest(array $ctx, string $userValue, array $overrides = []): void
{
    app(ClickHouseService::class)->insert('extraction_requests', [
        array_merge([
            'telemetry_record_id' => nextTelemetryId(),
            'organization_id' => $ctx['org']->id,
            'project_id' => $ctx['project']->id,
            'environment_id' => $ctx['env']->id,
            'trace_id' => 'trace-'.uniqid(),
            'user' => $userValue,
            'method' => 'GET',
            'url' => 'https://example.com/',
            'route_name' => 'home',
            'route_path' => '/',
            'route_action' => 'HomeController@index',
            'status_code' => 200,
            'duration' => 100,
            'request_size' => 0,
            'response_size' => 500,
            'peak_memory_usage' => 1024,
            'exceptions' => 0,
            'queries' => 0,
            'recorded_at' => now()->utc()->format('Y-m-d H:i:s'),
        ], $overrides),
    ]);
}

test('user analytics aggregates by user field', function () {
    $ctx = setupUserAnalyticsContext(uniqid());

    $userId = 'user-'.uniqid();
    insertUserRequest($ctx, $userId);
    insertUserRequest($ctx, $userId);
    insertUserRequest($ctx, 'another-user-'.uniqid());

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/users");

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/users/index')
        ->has('analytics.users', 2)
    );
});

test('user analytics is blocked for non-members', function () {
    $ctx = setupUserAnalyticsContext(uniqid());
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/users");

    $response->assertStatus(403);
});
