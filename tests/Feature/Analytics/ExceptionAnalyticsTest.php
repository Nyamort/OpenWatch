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
        'environment_id' => $ctx['env']->id,
        'trace_id' => 'trace-'.uniqid(),
        'execution_id' => 'exec-'.uniqid(),
        'group_key' => hash('sha256', 'SomeException'),
        'user' => null,
        'server' => 'web-1',
        'deploy' => 'v1.0.0',
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

test('exception show returns flat summary with aggregate stats', function () {
    $ctx = setupExceptionContext(uniqid());
    $groupKey = hash('sha256', 'ShowException-'.uniqid());

    insertException($ctx, ['group_key' => $groupKey, 'handled' => 0, 'user' => 'u1', 'server' => 'web-1', 'deploy' => 'v1.0.0']);
    insertException($ctx, ['group_key' => $groupKey, 'handled' => 0, 'user' => 'u2', 'server' => 'web-2', 'deploy' => 'v1.0.0']);
    insertException($ctx, ['group_key' => $groupKey, 'handled' => 1, 'user' => 'u1', 'server' => 'web-1', 'deploy' => 'v1.0.0']);

    $url = "/environments/{$ctx['env']->slug}/analytics/exceptions/{$groupKey}";

    $response = $this->actingAs($ctx['user'])
        ->withHeaders([
            'X-Inertia-Partial-Component' => 'analytics/exceptions/show',
            'X-Inertia-Partial-Data' => 'summary,graph,stats',
        ])
        ->get($url);

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/exceptions/show')
        ->where('summary.occurrences', 3)
        ->where('summary.impacted_users', 2)
        ->where('summary.servers', 2)
        ->where('summary.first_reported_in', 'v1.0.0')
        ->where('stats.count', 3)
        ->where('stats.handled', 1)
        ->where('stats.unhandled', 2)
        ->has('graph')
    );
});

test('exception show summary stats are all-time and not filtered by the selected period', function () {
    $ctx = setupExceptionContext(uniqid());
    $groupKey = hash('sha256', 'AllTimeException-'.uniqid());

    // Insert an exception outside the default 24h period
    insertException($ctx, [
        'group_key' => $groupKey,
        'handled' => 0,
        'user' => 'u1',
        'server' => 'web-1',
        'deploy' => 'v1.0.0',
        'recorded_at' => now()->utc()->subDays(7)->format('Y-m-d H:i:s'),
    ]);

    // Insert one within the period
    insertException($ctx, [
        'group_key' => $groupKey,
        'handled' => 1,
        'user' => 'u2',
        'server' => 'web-2',
        'deploy' => 'v1.0.0',
        'recorded_at' => now()->utc()->subHours(1)->format('Y-m-d H:i:s'),
    ]);

    $url = "/environments/{$ctx['env']->slug}/analytics/exceptions/{$groupKey}?period=24h";

    $response = $this->actingAs($ctx['user'])
        ->withHeaders([
            'X-Inertia-Partial-Component' => 'analytics/exceptions/show',
            'X-Inertia-Partial-Data' => 'summary,stats',
        ])
        ->get($url);

    $response->assertInertia(fn ($page) => $page
        // Summary card must reflect all-time totals (both records)
        ->where('summary.occurrences', 2)
        ->where('summary.impacted_users', 2)
        ->where('summary.servers', 2)
        // Period stats must only count the one occurrence within 24h
        ->where('stats.count', 1)
        ->where('stats.handled', 1)
        ->where('stats.unhandled', 0)
    );
});

test('exceptions index is blocked for non-members', function () {
    $ctx = setupExceptionContext(uniqid());
    $outsider = User::factory()->create();

    $response = $this->actingAs($outsider)
        ->get("/environments/{$ctx['env']->slug}/analytics/exceptions");

    $response->assertStatus(403);
});
