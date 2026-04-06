<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\User;
use App\Services\ClickHouse\ClickHouseService;

function setupJobsContext(string $suffix = ''): array
{
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Jobs Org '.$suffix, 'slug' => 'jobs-org-'.$suffix]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'jobs-app-'.$suffix]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production',
        'slug' => 'jobs-prod-'.$suffix,
        'type' => 'production',
    ])->environment;

    return compact('user', 'org', 'project', 'env');
}

function insertJobAttempt(array $ctx, array $overrides = []): void
{
    app(ClickHouseService::class)->insert('extraction_job_attempts', [
        array_merge([
            'telemetry_record_id' => nextTelemetryId(),
            'organization_id' => $ctx['org']->id,
            'project_id' => $ctx['project']->id,
            'environment_id' => $ctx['env']->id,
            'job_id' => 'job-'.uniqid(),
            'attempt_id' => 'attempt-'.uniqid(),
            'attempt' => 1,
            'name' => 'App\\Jobs\\ProcessOrder',
            'connection' => 'redis',
            'queue' => 'default',
            'status' => 'processed',
            'duration' => 250,
            'recorded_at' => now()->utc()->format('Y-m-d H:i:s'),
        ], $overrides),
    ]);
}

test('jobs index shows job list', function () {
    $ctx = setupJobsContext(uniqid());

    insertJobAttempt($ctx, ['name' => 'App\\Jobs\\SendEmail', 'status' => 'processed']);
    insertJobAttempt($ctx, ['name' => 'App\\Jobs\\SendEmail', 'status' => 'failed']);
    insertJobAttempt($ctx, ['name' => 'App\\Jobs\\ProcessOrder', 'status' => 'processed']);

    $url = "/environments/{$ctx['env']->slug}/analytics/jobs";

    $response = $this->actingAs($ctx['user'])
        ->withHeaders([
            'X-Inertia-Partial-Component' => 'analytics/jobs/index',
            'X-Inertia-Partial-Data' => 'graph,stats,jobs,pagination',
        ])
        ->get($url);

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/jobs/index')
        ->has('jobs', 2)
        ->has('graph')
        ->has('stats')
        ->has('pagination')
    );
});

test('jobs show returns attempts with connection and queue', function () {
    $ctx = setupJobsContext(uniqid());

    insertJobAttempt($ctx, ['name' => 'App\\Jobs\\SendEmail', 'connection' => 'redis', 'queue' => 'emails']);

    $url = "/environments/{$ctx['env']->slug}/analytics/jobs/0?name=App%5CJobs%5CSendEmail";

    $response = $this->actingAs($ctx['user'])
        ->withHeaders([
            'X-Inertia-Partial-Component' => 'analytics/jobs/show',
            'X-Inertia-Partial-Data' => 'graph,stats,attempts,pagination',
        ])
        ->get($url);

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/jobs/show')
        ->has('attempts', 1)
        ->has('attempts.0', fn ($attempt) => $attempt
            ->where('connection', 'redis')
            ->where('queue', 'emails')
            ->etc()
        )
    );
});

test('jobs index is blocked for non-members', function () {
    $ctx = setupJobsContext(uniqid());
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)
        ->get("/environments/{$ctx['env']->slug}/analytics/jobs");

    $response->assertStatus(403);
});
