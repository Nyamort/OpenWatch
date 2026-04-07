<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\User;
use App\Services\ClickHouse\ClickHouseService;

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
    app(ClickHouseService::class)->insert('extraction_requests', [
        array_merge([
            'environment_id' => $ctx['env']->id,
            'trace_id' => 'trace-'.uniqid(),
            'user' => null,
            'method' => 'GET',
            'url' => 'https://example.com/',
            'route_name' => 'home',
            'route_path' => '/',
            'route_methods' => 'GET|HEAD',
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

test('requests index returns graph and stats', function () {
    $ctx = setupAnalyticsContext('req-'.uniqid());

    insertRequest($ctx, ['route_path' => '/api/users', 'method' => 'GET']);
    insertRequest($ctx, ['route_path' => '/api/users', 'method' => 'GET']);
    insertRequest($ctx, ['route_path' => '/api/posts', 'method' => 'GET']);

    $url = "/environments/{$ctx['env']->slug}/analytics/requests";

    $response = $this->actingAs($ctx['user'])
        ->withHeaders([
            'X-Inertia-Partial-Component' => 'analytics/requests/index',
            'X-Inertia-Partial-Data' => 'graph,stats,paths,pagination',
        ])
        ->get($url);

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/requests/index')
        ->has('graph')
        ->has('stats')
        ->has('paths')
    );
});

test('requests index is blocked for non-members', function () {
    $ctx = setupAnalyticsContext('req-nm-'.uniqid());
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)
        ->get("/environments/{$ctx['env']->slug}/analytics/requests");

    $response->assertStatus(403);
});

test('requests route view requires auth', function () {
    $ctx = setupAnalyticsContext('req-auth-'.uniqid());

    $response = $this->get("/environments/{$ctx['env']->slug}/analytics/requests");

    $response->assertRedirect();
});

test('request index groups paths correctly', function () {
    $ctx = setupAnalyticsContext('req-paths-'.uniqid());

    insertRequest($ctx, ['route_path' => '/api/users', 'method' => 'GET', 'route_methods' => 'GET|HEAD']);
    insertRequest($ctx, ['route_path' => '/api/users', 'method' => 'GET', 'route_methods' => 'GET|HEAD', 'status_code' => 500]);
    insertRequest($ctx, ['route_path' => '/api/posts', 'method' => 'POST', 'route_methods' => 'POST']);

    $url = "/environments/{$ctx['env']->slug}/analytics/requests?sort=total&direction=desc";

    $response = $this->actingAs($ctx['user'])
        ->withHeaders([
            'X-Inertia-Partial-Component' => 'analytics/requests/index',
            'X-Inertia-Partial-Data' => 'paths',
        ])
        ->get($url);

    $response->assertInertia(fn ($page) => $page
        ->has('paths', 2)
        ->where('paths.0.path', '/api/users')
        ->where('paths.0.methods', ['GET', 'HEAD'])
        ->where('paths.0.total', 2)
        ->where('paths.0.5xx', 1)
        ->where('paths.1.path', '/api/posts')
        ->where('paths.1.methods', ['POST'])
        ->where('paths.1.total', 1)
    );
});

test('request show returns all related rows grouped by execution_stage', function () {
    $ctx = setupAnalyticsContext('req-show-'.uniqid());
    $traceId = 'trace-'.uniqid();
    $ch = app(ClickHouseService::class);

    insertRequest($ctx, [
        'trace_id' => $traceId,
        'id' => $traceId,
        'before_middleware' => 100,
        'action' => 1000,
    ]);

    $ch->insert('extraction_queries', [[
        'environment_id' => $ctx['env']->id,
        'trace_id' => $traceId,
        'execution_stage' => 'action',
        'sql_hash' => hash('sha256', 'SELECT 1'),
        'sql_normalized' => 'SELECT 1',
        'connection' => 'mysql',
        'connection_type' => 'mysql',
        'duration' => 1000,
        'recorded_at' => now()->utc()->format('Y-m-d H:i:s'),
    ]]);

    $ch->insert('extraction_mails', [[
        'environment_id' => $ctx['env']->id,
        'trace_id' => $traceId,
        'execution_stage' => 'action',
        'mailer' => 'smtp',
        'class' => 'App\\Mail\\WelcomeMail',
        'subject' => 'Hello',
        'to' => 1,
        'duration' => 200,
        'failed' => 0,
        'recorded_at' => now()->utc()->format('Y-m-d H:i:s'),
    ]]);

    $ch->insert('extraction_notifications', [[
        'environment_id' => $ctx['env']->id,
        'trace_id' => $traceId,
        'execution_stage' => 'action',
        'channel' => 'mail',
        'class' => 'App\\Notifications\\OrderShipped',
        'duration' => 150,
        'failed' => 0,
        'recorded_at' => now()->utc()->format('Y-m-d H:i:s'),
    ]]);

    $ch->insert('extraction_cache_events', [[
        'environment_id' => $ctx['env']->id,
        'trace_id' => $traceId,
        'execution_stage' => 'before_middleware',
        'store' => 'redis',
        'key' => 'some-key',
        'type' => 'hit',
        'duration' => 50,
        'recorded_at' => now()->utc()->format('Y-m-d H:i:s'),
    ]]);

    $ch->insert('extraction_outgoing_requests', [[
        'environment_id' => $ctx['env']->id,
        'trace_id' => $traceId,
        'execution_stage' => 'action',
        'host' => 'api.example.com',
        'method' => 'GET',
        'url' => 'https://api.example.com/data',
        'status_code' => 200,
        'duration' => 300,
        'recorded_at' => now()->utc()->format('Y-m-d H:i:s'),
    ]]);

    $url = "/environments/{$ctx['env']->slug}/analytics/requests/{$traceId}";

    $response = $this->actingAs($ctx['user'])->get($url);

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/requests/show')
        ->has('analytics.rows.executions', 1)
        ->has('analytics.rows.executions.0.stages', 2)
        ->where('analytics.rows.executions.0.stages.0.id', 'before_middleware')
        ->has('analytics.rows.executions.0.stages.0.spans', 1)
        ->where('analytics.rows.executions.0.stages.0.spans.0.span_type', 'cache')
        ->where('analytics.rows.executions.0.stages.1.id', 'action')
        ->has('analytics.rows.executions.0.stages.1.spans', 4)
    );
});

test('request show includes user name and email when user is linked', function () {
    $ctx = setupAnalyticsContext('req-user-'.uniqid());
    $traceId = 'trace-'.uniqid();
    $userId = 'user-'.uniqid();
    $ch = app(ClickHouseService::class);

    insertRequest($ctx, ['trace_id' => $traceId, 'id' => $traceId, 'user' => $userId]);

    $ch->insert('extraction_user_activities', [[
        'environment_id' => $ctx['env']->id,
        'user_id' => $userId,
        'name' => 'Jane Doe',
        'username' => 'jane@example.com',
        'recorded_at' => now()->utc()->format('Y-m-d H:i:s'),
    ]]);

    $url = "/environments/{$ctx['env']->slug}/analytics/requests/{$traceId}";

    $response = $this->actingAs($ctx['user'])->get($url);

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/requests/show')
        ->where('analytics.summary.user_name', 'Jane Doe')
        ->where('analytics.summary.user_email', 'jane@example.com')
    );
});

test('request show has null user name and email when no user is linked', function () {
    $ctx = setupAnalyticsContext('req-nouser-'.uniqid());
    $traceId = 'trace-'.uniqid();

    insertRequest($ctx, ['trace_id' => $traceId, 'id' => $traceId, 'user' => null]);

    $url = "/environments/{$ctx['env']->slug}/analytics/requests/{$traceId}";

    $response = $this->actingAs($ctx['user'])->get($url);

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/requests/show')
        ->where('analytics.summary.user_name', null)
        ->where('analytics.summary.user_email', null)
    );
});

test('request index returns correct total count in stats', function () {
    $ctx = setupAnalyticsContext('req-count-'.uniqid());

    insertRequest($ctx);
    insertRequest($ctx);
    insertRequest($ctx);

    $url = "/environments/{$ctx['env']->slug}/analytics/requests";

    $response = $this->actingAs($ctx['user'])
        ->withHeaders([
            'X-Inertia-Partial-Component' => 'analytics/requests/index',
            'X-Inertia-Partial-Data' => 'stats',
        ])
        ->get($url);

    $response->assertInertia(fn ($page) => $page
        ->where('stats.count', 3)
    );
});
