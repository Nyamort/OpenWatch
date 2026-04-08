<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\User;
use App\Services\ClickHouse\ClickHouseService;

function setupLogContext(string $suffix = ''): array
{
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Log Org '.$suffix, 'slug' => 'log-org-'.$suffix]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'log-app-'.$suffix]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production',
        'slug' => 'log-prod-'.$suffix,
        'type' => 'production',
    ])->environment;

    return compact('user', 'org', 'project', 'env');
}

function insertLog(array $ctx, array $overrides = []): void
{
    app(ClickHouseService::class)->insert('extraction_logs', [
        array_merge([
            'environment_id' => $ctx['env']->id,
            'level' => 'info',
            'message' => 'Test log message',
            'execution_id' => 'exec-'.uniqid(),
            'recorded_at' => now()->utc()->format('Y-m-d H:i:s'),
        ], $overrides),
    ]);
}

test('log feed is ordered newest-first', function () {
    $ctx = setupLogContext(uniqid());

    insertLog($ctx, ['message' => 'First log', 'recorded_at' => now()->subMinutes(5)->utc()->format('Y-m-d H:i:s')]);
    insertLog($ctx, ['message' => 'Second log', 'recorded_at' => now()->subMinutes(2)->utc()->format('Y-m-d H:i:s')]);
    insertLog($ctx, ['message' => 'Third log', 'recorded_at' => now()->utc()->format('Y-m-d H:i:s')]);

    $response = $this->actingAs($ctx['user'])
        ->withHeaders([
            'X-Inertia-Partial-Component' => 'analytics/logs/index',
            'X-Inertia-Partial-Data' => 'logs',
        ])
        ->get("/environments/{$ctx['env']->slug}/analytics/logs");

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/logs/index')
        ->where('logs.0.message', 'Third log')
        ->where('logs.1.message', 'Second log')
        ->where('logs.2.message', 'First log')
    );
});

test('log feed filters by level', function () {
    $ctx = setupLogContext(uniqid());

    insertLog($ctx, ['level' => 'info', 'message' => 'Info message']);
    insertLog($ctx, ['level' => 'error', 'message' => 'Error message']);
    insertLog($ctx, ['level' => 'debug', 'message' => 'Debug message']);

    $response = $this->actingAs($ctx['user'])
        ->withHeaders([
            'X-Inertia-Partial-Component' => 'analytics/logs/index',
            'X-Inertia-Partial-Data' => 'logs',
        ])
        ->get("/environments/{$ctx['env']->slug}/analytics/logs?level=error");

    $response->assertInertia(fn ($page) => $page
        ->has('logs', 1)
        ->where('logs.0.level', 'error')
    );
});

test('log feed with unknown level returns all logs', function () {
    $ctx = setupLogContext(uniqid());

    insertLog($ctx, ['level' => 'info']);
    insertLog($ctx, ['level' => 'error']);

    $response = $this->actingAs($ctx['user'])
        ->withHeaders([
            'X-Inertia-Partial-Component' => 'analytics/logs/index',
            'X-Inertia-Partial-Data' => 'logs',
        ])
        ->get("/environments/{$ctx['env']->slug}/analytics/logs?level=unknown");

    $response->assertInertia(fn ($page) => $page
        ->has('logs', 2)
    );
});
