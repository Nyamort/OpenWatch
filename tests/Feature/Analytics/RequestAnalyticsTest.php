<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function setupAnalyticsContext(string $suffix = ''): array
{
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Org '.$suffix, 'slug' => 'org-'.$suffix]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'app-'.$suffix]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production',
        'slug' => 'prod-'.$suffix,
        'type' => 'production',
    ])->environment;

    return compact('user', 'org', 'project', 'env');
}

function insertRequest(array $ctx, array $overrides = []): void
{
    DB::table('extraction_requests')->insert(array_merge([
        'telemetry_record_id' => nextTelemetryId($ctx),
        'organization_id' => $ctx['org']->id,
        'project_id' => $ctx['project']->id,
        'environment_id' => $ctx['env']->id,
        'trace_id' => 'trace-'.uniqid(),
        'user' => null,
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
        'recorded_at' => now(),
    ], $overrides));
}

test('requests index returns grouped routes', function () {
    $ctx = setupAnalyticsContext('req-'.uniqid());

    insertRequest($ctx, ['route_path' => '/api/users', 'method' => 'GET']);
    insertRequest($ctx, ['route_path' => '/api/users', 'method' => 'GET']);
    insertRequest($ctx, ['route_path' => '/api/posts', 'method' => 'GET']);

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/requests");

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/requests/index')
        ->has('analytics')
    );
});

test('requests index is blocked for non-members', function () {
    $ctx = setupAnalyticsContext('req-nm-'.uniqid());
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/requests");

    $response->assertStatus(403);
});

test('requests route view requires auth', function () {
    $ctx = setupAnalyticsContext('req-auth-'.uniqid());

    $response = $this->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/requests");

    $response->assertRedirect();
});

test('request index returns correct total count in summary', function () {
    $ctx = setupAnalyticsContext('req-count-'.uniqid());

    insertRequest($ctx);
    insertRequest($ctx);
    insertRequest($ctx);

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/requests");

    $response->assertInertia(fn ($page) => $page
        ->where('analytics.summary.total_requests', 3)
    );
});
