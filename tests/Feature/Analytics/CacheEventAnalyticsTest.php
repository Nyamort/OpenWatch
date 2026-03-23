<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;

function setupCacheContext(string $suffix = ''): array
{
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Cache Org '.$suffix, 'slug' => 'cache-org-'.$suffix]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'cache-app-'.$suffix]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production',
        'slug' => 'cache-prod-'.$suffix,
        'type' => 'production',
    ])->environment;

    return compact('user', 'org', 'project', 'env');
}

function insertCacheEvent(array $ctx, array $overrides = []): void
{
    DB::table('extraction_cache_events')->insert(array_merge([
        'telemetry_record_id' => nextTelemetryId($ctx),
        'organization_id' => $ctx['org']->id,
        'project_id' => $ctx['project']->id,
        'environment_id' => $ctx['env']->id,
        'store' => 'redis',
        'key' => 'user:1',
        'type' => 'hit',
        'duration' => 1,
        'ttl' => 3600,
        'recorded_at' => now(),
    ], $overrides));
}

test('cache events index computes hit rate correctly', function () {
    $ctx = setupCacheContext(uniqid());

    insertCacheEvent($ctx, ['type' => 'hit', 'key' => 'test:key']);
    insertCacheEvent($ctx, ['type' => 'hit', 'key' => 'test:key']);
    insertCacheEvent($ctx, ['type' => 'miss', 'key' => 'test:key']);
    insertCacheEvent($ctx, ['type' => 'miss', 'key' => 'test:key']);

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/cache-events");

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/cache-events/index')
        ->where('analytics.rows.0.hit_rate_pct', 50)
        ->where('analytics.rows.0.hit_rate_color', 'yellow')
    );
});

test('cache hit rate >= 80 gets green color', function () {
    $ctx = setupCacheContext(uniqid());

    foreach (range(1, 8) as $_) {
        insertCacheEvent($ctx, ['type' => 'hit', 'key' => 'green:key']);
    }
    insertCacheEvent($ctx, ['type' => 'miss', 'key' => 'green:key']);
    insertCacheEvent($ctx, ['type' => 'miss', 'key' => 'green:key']);

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/cache-events");

    $response->assertInertia(fn ($page) => $page
        ->where('analytics.rows.0.hit_rate_color', 'green')
    );
});

test('cache hit rate < 50 gets red color', function () {
    $ctx = setupCacheContext(uniqid());

    insertCacheEvent($ctx, ['type' => 'hit', 'key' => 'red:key']);
    foreach (range(1, 4) as $_) {
        insertCacheEvent($ctx, ['type' => 'miss', 'key' => 'red:key']);
    }

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/cache-events");

    $response->assertInertia(fn ($page) => $page
        ->where('analytics.rows.0.hit_rate_color', 'red')
    );
});
