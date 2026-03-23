<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function setupExceptionContext(string $suffix = ''): array
{
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Exc Org '.$suffix, 'slug' => 'exc-org-'.$suffix]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'exc-app-'.$suffix]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production',
        'slug' => 'exc-prod-'.$suffix,
        'type' => 'production',
    ])->environment;

    return compact('user', 'org', 'project', 'env');
}

function insertException(array $ctx, array $overrides = []): void
{
    DB::table('extraction_exceptions')->insert(array_merge([
        'telemetry_record_id' => nextTelemetryId($ctx),
        'organization_id' => $ctx['org']->id,
        'project_id' => $ctx['project']->id,
        'environment_id' => $ctx['env']->id,
        'trace_id' => 'trace-'.uniqid(),
        'execution_id' => 'exec-'.uniqid(),
        'group_key' => hash('sha256', 'SomeException'),
        'user' => null,
        'class' => 'App\\Exceptions\\SomeException',
        'file' => '/app/src/Something.php',
        'line' => 42,
        'message' => 'Something went wrong',
        'handled' => false,
        'php_version' => '8.2.0',
        'laravel_version' => '12.0.0',
        'recorded_at' => now(),
    ], $overrides));
}

test('exceptions index groups by group_key', function () {
    $ctx = setupExceptionContext(uniqid());
    $groupKey = hash('sha256', 'MyException-'.uniqid());

    insertException($ctx, ['group_key' => $groupKey, 'class' => 'App\\Exceptions\\MyException', 'handled' => false]);
    insertException($ctx, ['group_key' => $groupKey, 'class' => 'App\\Exceptions\\MyException', 'handled' => true]);
    insertException($ctx, ['group_key' => hash('sha256', 'OtherException'), 'class' => 'App\\Exceptions\\OtherException', 'handled' => false]);

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/exceptions");

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/exceptions/index')
        ->has('analytics.rows', 2)
    );
});

test('exceptions index row for group shows correct handled/unhandled counts', function () {
    $ctx = setupExceptionContext(uniqid());
    $groupKey = hash('sha256', 'CountException-'.uniqid());

    insertException($ctx, ['group_key' => $groupKey, 'handled' => false]);
    insertException($ctx, ['group_key' => $groupKey, 'handled' => false]);
    insertException($ctx, ['group_key' => $groupKey, 'handled' => true]);

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/exceptions");

    $response->assertInertia(fn ($page) => $page
        ->where('analytics.rows.0.total', 3)
        ->where('analytics.rows.0.unhandled_count', 2)
        ->where('analytics.rows.0.handled_count', 1)
    );
});

test('exceptions index is blocked for non-members', function () {
    $ctx = setupExceptionContext(uniqid());
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/exceptions");

    $response->assertStatus(403);
});
