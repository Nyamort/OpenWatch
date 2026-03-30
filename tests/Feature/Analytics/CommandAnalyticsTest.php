<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function setupCommandContext(string $suffix = ''): array
{
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Cmd Org '.$suffix, 'slug' => 'cmd-org-'.$suffix]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'cmd-app-'.$suffix]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production',
        'slug' => 'cmd-prod-'.$suffix,
        'type' => 'production',
    ])->environment;

    return compact('user', 'org', 'project', 'env');
}

function insertCommand(array $ctx, array $overrides = []): void
{
    DB::table('extraction_commands')->insert(array_merge([
        'telemetry_record_id' => nextTelemetryId($ctx),
        'organization_id' => $ctx['org']->id,
        'project_id' => $ctx['project']->id,
        'environment_id' => $ctx['env']->id,
        'name' => 'app:process',
        'class' => 'App\\Console\\Commands\\ProcessCommand',
        'exit_code' => 0,
        'duration' => 500,
        'status' => 'success',
        'recorded_at' => now(),
    ], $overrides));
}

test('commands index groups by name with status counters', function () {
    $ctx = setupCommandContext(uniqid());

    insertCommand($ctx, ['name' => 'app:sync', 'status' => 'success', 'duration' => 100]);
    insertCommand($ctx, ['name' => 'app:sync', 'status' => 'success', 'duration' => 200]);
    insertCommand($ctx, ['name' => 'app:sync', 'status' => 'failed', 'duration' => 50]);
    insertCommand($ctx, ['name' => 'app:import', 'status' => 'success', 'duration' => 1000]);

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/commands");

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/commands/index')
        ->has('commands', 2)
        ->has('graph')
        ->has('stats')
        ->has('pagination')
        ->where('commands.0.name', 'app:sync')
        ->where('commands.0.total', 3)
        ->where('commands.0.successful', 2)
        ->where('commands.0.failed', 1)
    );
});

test('commands index is blocked for non-members', function () {
    $ctx = setupCommandContext(uniqid());
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/commands");

    $response->assertStatus(403);
});
