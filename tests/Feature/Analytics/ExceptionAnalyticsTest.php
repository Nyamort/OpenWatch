<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\User;
use App\Services\ClickHouse\ClickHouseService;

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
    $data = array_merge([
        'telemetry_record_id' => nextTelemetryId(),
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
        'handled' => 0,
        'php_version' => '8.2.0',
        'laravel_version' => '12.0.0',
        'recorded_at' => now()->utc()->format('Y-m-d H:i:s'),
    ], $overrides);

    // Normalize boolean to UInt8
    $data['handled'] = $data['handled'] ? 1 : 0;

    app(ClickHouseService::class)->insert('extraction_exceptions', [$data]);
}

test('exceptions index groups by group_key', function () {
    $ctx = setupExceptionContext(uniqid());
    $groupKey = hash('sha256', 'MyException-'.uniqid());

    insertException($ctx, ['group_key' => $groupKey, 'class' => 'App\\Exceptions\\MyException', 'handled' => 0]);
    insertException($ctx, ['group_key' => $groupKey, 'class' => 'App\\Exceptions\\MyException', 'handled' => 1]);
    insertException($ctx, ['group_key' => hash('sha256', 'OtherException'), 'class' => 'App\\Exceptions\\OtherException', 'handled' => 0]);

    $url = "/environments/{$ctx['env']->slug}/analytics/exceptions";

    $response = $this->actingAs($ctx['user'])
        ->withHeaders([
            'X-Inertia-Partial-Component' => 'analytics/exceptions/index',
            'X-Inertia-Partial-Data' => 'exceptions',
        ])
        ->get($url);

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/exceptions/index')
        ->has('exceptions', 2)
    );
});

test('exceptions index row for group shows correct count and global stats show handled breakdown', function () {
    $ctx = setupExceptionContext(uniqid());
    $groupKey = hash('sha256', 'CountException-'.uniqid());

    insertException($ctx, ['group_key' => $groupKey, 'handled' => 0]);
    insertException($ctx, ['group_key' => $groupKey, 'handled' => 0]);
    insertException($ctx, ['group_key' => $groupKey, 'handled' => 1]);

    $url = "/environments/{$ctx['env']->slug}/analytics/exceptions";

    $response = $this->actingAs($ctx['user'])
        ->withHeaders([
            'X-Inertia-Partial-Component' => 'analytics/exceptions/index',
            'X-Inertia-Partial-Data' => 'exceptions,stats',
        ])
        ->get($url);

    $response->assertInertia(fn ($page) => $page
        ->where('exceptions.0.count', 3)
        ->where('stats.unhandled', 2)
        ->where('stats.handled', 1)
    );
});

test('exceptions index is blocked for non-members', function () {
    $ctx = setupExceptionContext(uniqid());
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)
        ->get("/environments/{$ctx['env']->slug}/analytics/exceptions");

    $response->assertStatus(403);
});
