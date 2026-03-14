<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function setupLogContext(string $suffix = ''): array
{
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Log Org '.$suffix, 'slug' => 'log-org-'.$suffix]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'log-app-'.$suffix]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production',
        'slug' => 'log-prod-'.$suffix,
        'type' => 'production',
    ]);

    return compact('user', 'org', 'project', 'env');
}

function insertLog(array $ctx, array $overrides = []): void
{
    DB::table('extraction_logs')->insert(array_merge([
        'telemetry_record_id' => nextTelemetryId($ctx),
        'organization_id' => $ctx['org']->id,
        'project_id' => $ctx['project']->id,
        'environment_id' => $ctx['env']->id,
        'level' => 'info',
        'message' => 'Test log message',
        'execution_id' => 'exec-'.uniqid(),
        'recorded_at' => now(),
    ], $overrides));
}

test('log feed is ordered newest-first', function () {
    $ctx = setupLogContext(uniqid());

    insertLog($ctx, ['message' => 'First log', 'recorded_at' => now()->subMinutes(5)]);
    insertLog($ctx, ['message' => 'Second log', 'recorded_at' => now()->subMinutes(2)]);
    insertLog($ctx, ['message' => 'Third log', 'recorded_at' => now()]);

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/logs");

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/logs/index')
        ->where('analytics.rows.0.message', 'Third log')
        ->where('analytics.rows.1.message', 'Second log')
        ->where('analytics.rows.2.message', 'First log')
    );
});

test('log feed filters by level', function () {
    $ctx = setupLogContext(uniqid());

    insertLog($ctx, ['level' => 'info', 'message' => 'Info message']);
    insertLog($ctx, ['level' => 'error', 'message' => 'Error message']);
    insertLog($ctx, ['level' => 'debug', 'message' => 'Debug message']);

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/logs?level=error");

    $response->assertInertia(fn ($page) => $page
        ->has('analytics.rows', 1)
        ->where('analytics.rows.0.level', 'error')
    );
});

test('log feed with unknown level returns all logs', function () {
    $ctx = setupLogContext(uniqid());

    insertLog($ctx, ['level' => 'info']);
    insertLog($ctx, ['level' => 'error']);

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/logs?level=unknown");

    $response->assertInertia(fn ($page) => $page
        ->has('analytics.rows', 2)
    );
});
