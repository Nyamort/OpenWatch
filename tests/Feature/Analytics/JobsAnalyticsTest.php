<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
    DB::table('extraction_job_attempts')->insert(array_merge([
        'telemetry_record_id' => nextTelemetryId($ctx),
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
        'recorded_at' => now(),
    ], $overrides));
}

test('jobs index shows job list', function () {
    $ctx = setupJobsContext(uniqid());

    insertJobAttempt($ctx, ['name' => 'App\\Jobs\\SendEmail', 'status' => 'processed']);
    insertJobAttempt($ctx, ['name' => 'App\\Jobs\\SendEmail', 'status' => 'failed']);
    insertJobAttempt($ctx, ['name' => 'App\\Jobs\\ProcessOrder', 'status' => 'processed']);

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/jobs");

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/jobs/index')
        ->has('analytics.rows', 2)
    );
});

test('jobs index is blocked for non-members', function () {
    $ctx = setupJobsContext(uniqid());
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/jobs");

    $response->assertStatus(403);
});
