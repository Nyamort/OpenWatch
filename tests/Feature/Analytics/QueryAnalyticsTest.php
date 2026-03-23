<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function setupQueryContext(string $suffix = ''): array
{
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Query Org '.$suffix, 'slug' => 'query-org-'.$suffix]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'query-app-'.$suffix]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production',
        'slug' => 'query-prod-'.$suffix,
        'type' => 'production',
    ])->environment;

    return compact('user', 'org', 'project', 'env');
}

function insertQuery(array $ctx, array $overrides = []): void
{
    DB::table('extraction_queries')->insert(array_merge([
        'telemetry_record_id' => nextTelemetryId($ctx),
        'organization_id' => $ctx['org']->id,
        'project_id' => $ctx['project']->id,
        'environment_id' => $ctx['env']->id,
        'trace_id' => 'trace-'.uniqid(),
        'execution_id' => 'exec-'.uniqid(),
        'user' => null,
        'sql_hash' => hash('sha256', 'SELECT * FROM users'),
        'sql_normalized' => 'SELECT * FROM users',
        'connection' => 'mysql',
        'connection_type' => 'mysql',
        'duration' => 1000,
        'recorded_at' => now(),
    ], $overrides));
}

test('queries index returns rows grouped by sql_hash', function () {
    $ctx = setupQueryContext(uniqid());
    $hash = hash('sha256', 'SELECT 1');

    insertQuery($ctx, ['sql_hash' => $hash, 'sql_normalized' => 'SELECT 1', 'duration' => 500]);
    insertQuery($ctx, ['sql_hash' => $hash, 'sql_normalized' => 'SELECT 1', 'duration' => 1500]);

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/queries");

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/queries/index')
        ->has('analytics.rows', 1)
        ->where('analytics.rows.0.sql_hash', $hash)
        ->where('analytics.rows.0.total', 2)
    );
});

test('queries index is blocked for non-members', function () {
    $ctx = setupQueryContext(uniqid());
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/queries");

    $response->assertStatus(403);
});
