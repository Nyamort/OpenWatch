<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\User;
use App\Services\ClickHouse\ClickHouseService;

function setupOutgoingContext(string $suffix = ''): array
{
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Out Org '.$suffix, 'slug' => 'out-org-'.$suffix]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'out-app-'.$suffix]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production',
        'slug' => 'out-prod-'.$suffix,
        'type' => 'production',
    ])->environment;

    return compact('user', 'org', 'project', 'env');
}

function insertOutgoingRequest(array $ctx, array $overrides = []): void
{
    app(ClickHouseService::class)->insert('extraction_outgoing_requests', [
        array_merge([
            'telemetry_record_id' => nextTelemetryId(),
            'organization_id' => $ctx['org']->id,
            'project_id' => $ctx['project']->id,
            'environment_id' => $ctx['env']->id,
            'host' => 'api.example.com',
            'method' => 'GET',
            'url' => 'https://api.example.com/v1/users',
            'status_code' => 200,
            'duration' => 100,
            'recorded_at' => now()->utc()->format('Y-m-d H:i:s'),
        ], $overrides),
    ]);
}

test('outgoing requests index returns graph, stats and hosts', function () {
    $ctx = setupOutgoingContext(uniqid());

    insertOutgoingRequest($ctx, ['host' => 'api.example.com', 'status_code' => 200]);
    insertOutgoingRequest($ctx, ['host' => 'api.example.com', 'status_code' => 404]);
    insertOutgoingRequest($ctx, ['host' => 'cdn.example.com', 'status_code' => 200]);

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/outgoing-requests");

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/outgoing-requests/index')
        ->has('graph')
        ->has('stats')
        ->has('hosts', 2)
        ->has('pagination')
    );
});

test('outgoing requests stats count success, 4xx, and 5xx correctly', function () {
    $ctx = setupOutgoingContext(uniqid());

    insertOutgoingRequest($ctx, ['status_code' => 200]);
    insertOutgoingRequest($ctx, ['status_code' => 301]);
    insertOutgoingRequest($ctx, ['status_code' => 404]);
    insertOutgoingRequest($ctx, ['status_code' => 404]);
    insertOutgoingRequest($ctx, ['status_code' => 500]);

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/outgoing-requests");

    $response->assertInertia(fn ($page) => $page
        ->where('stats.total', 5)
        ->where('stats.success', 2)
        ->where('stats.count_4xx', 2)
        ->where('stats.count_5xx', 1)
    );
});

test('outgoing requests table groups by host', function () {
    $ctx = setupOutgoingContext(uniqid());

    insertOutgoingRequest($ctx, ['host' => 'api.example.com', 'status_code' => 200, 'duration' => 100]);
    insertOutgoingRequest($ctx, ['host' => 'api.example.com', 'status_code' => 200, 'duration' => 300]);
    insertOutgoingRequest($ctx, ['host' => 'cdn.example.com', 'status_code' => 404, 'duration' => 50]);

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/outgoing-requests");

    $response->assertInertia(fn ($page) => $page
        ->has('hosts', 2)
        ->where('hosts.0.host', 'api.example.com')
        ->where('hosts.0.total', 2)
        ->where('hosts.0.success', 2)
        ->where('hosts.0.count_4xx', 0)
        ->where('hosts.1.host', 'cdn.example.com')
        ->where('hosts.1.count_4xx', 1)
    );
});

test('outgoing requests index is blocked for non-members', function () {
    $ctx = setupOutgoingContext(uniqid());
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/outgoing-requests");

    $response->assertStatus(403);
});

test('outgoing requests index requires auth', function () {
    $ctx = setupOutgoingContext(uniqid());

    $response = $this->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/outgoing-requests");

    $response->assertRedirect();
});
