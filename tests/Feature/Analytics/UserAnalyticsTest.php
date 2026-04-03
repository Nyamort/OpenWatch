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

function insertUserActivity(array $ctx, string $username, array $overrides = []): void
{
    app(ClickHouseService::class)->insert('extraction_user_activities', [
        array_merge([
            'telemetry_record_id' => nextTelemetryId(),
            'organization_id' => $ctx['org']->id,
            'project_id' => $ctx['project']->id,
            'environment_id' => $ctx['env']->id,
            'user_id' => $username,
            'name' => 'Test User',
            'username' => $username,
            'recorded_at' => now()->utc()->format('Y-m-d H:i:s'),
        ], $overrides),
    ]);
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

test('user analytics returns graph and stats via deferred props', function () {
    $ctx = setupUserAnalyticsContext(uniqid());

    $email = 'alice@example.com';
    $email2 = 'bob@example.com';

    insertUserActivity($ctx, $email);
    insertUserActivity($ctx, $email);  // duplicate — should count as 1 unique
    insertUserActivity($ctx, $email2);

    insertUserRequest($ctx, $email);
    insertUserRequest($ctx, $email2);
    insertUserRequest($ctx, '');  // guest

    $url = "/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/users";

    $response = $this->actingAs($ctx['user'])
        ->withHeaders([
            'X-Inertia-Partial-Component' => 'analytics/users/index',
            'X-Inertia-Partial-Data' => 'graph,stats,users,pagination',
        ])
        ->get($url);

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/users/index')
        ->has('graph')
        ->has('stats')
        ->has('users', 2)
        ->where('stats.authenticated_users', 2)
        ->where('stats.authenticated_requests', 2)
        ->where('stats.guest_requests', 1)
    );
});

test('user analytics table rows contain expected columns', function () {
    $ctx = setupUserAnalyticsContext('cols-'.uniqid());

    $email = 'test@example.com';
    insertUserActivity($ctx, $email, ['name' => 'Test User']);
    insertUserRequest($ctx, $email, ['status_code' => 200]);
    insertUserRequest($ctx, $email, ['status_code' => 404]);
    insertUserRequest($ctx, $email, ['status_code' => 500]);

    $url = "/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/users";

    $response = $this->actingAs($ctx['user'])
        ->withHeaders([
            'X-Inertia-Partial-Component' => 'analytics/users/index',
            'X-Inertia-Partial-Data' => 'users,pagination',
        ])
        ->get($url);

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/users/index')
        ->has('users', 1, fn ($row) => $row
            ->where('email', $email)
            ->has('name')
            ->has('2xx')
            ->has('4xx')
            ->has('5xx')
            ->has('request_count')
            ->has('job_count')
            ->has('exception_count')
            ->has('last_seen')
        )
    );
});

test('user analytics is blocked for non-members', function () {
    $ctx = setupUserAnalyticsContext(uniqid());
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/users");

    $response->assertStatus(403);
});
