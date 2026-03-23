<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard renders for authenticated user with no active org', function () {
    $user = User::factory()->create(['active_organization_id' => null]);

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->where('hasContext', false)
    );
});

test('dashboard resolves context from active org', function () {
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Dash Org', 'slug' => 'dash-org-'.uniqid()]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'dash-app-'.uniqid()]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Prod', 'slug' => 'dash-prod-'.uniqid(), 'type' => 'production',
    ])->environment;

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertInertia(fn ($page) => $page
        ->component('dashboard')
        ->where('hasContext', true)
        ->has('context.org')
        ->has('context.project')
        ->has('context.env')
    );
});

test('dashboard accepts explicit context via query params', function () {
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Qp Org', 'slug' => 'qp-org-'.uniqid()]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'qp-app-'.uniqid()]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Prod', 'slug' => 'qp-prod-'.uniqid(), 'type' => 'production',
    ])->environment;

    $url = "/dashboard?org={$org->slug}&project={$project->slug}&env={$env->slug}&period=7d";
    $response = $this->actingAs($user)->get($url);

    $response->assertInertia(fn ($page) => $page
        ->where('hasContext', true)
        ->where('period', '7d')
    );
});

test('dashboard data is scoped to org', function () {
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Scoped Org', 'slug' => 'scoped-org-'.uniqid()]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'scoped-app-'.uniqid()]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Prod', 'slug' => 'scoped-prod-'.uniqid(), 'type' => 'production',
    ])->environment;

    // Insert a request for this org
    DB::table('telemetry_records')->insert(['organization_id' => $org->id, 'project_id' => $project->id, 'environment_id' => $env->id, 'record_type' => 'request', 'recorded_at' => now()]);
    $trId = DB::getPdo()->lastInsertId();
    DB::table('extraction_requests')->insert([
        'telemetry_record_id' => $trId, 'organization_id' => $org->id, 'project_id' => $project->id,
        'environment_id' => $env->id, 'method' => 'GET', 'url' => 'http://example.com',
        'status_code' => 200, 'duration' => 100, 'exceptions' => 0, 'queries' => 0, 'recorded_at' => now(),
    ]);

    $action = new \App\Actions\Dashboard\BuildDashboardData;
    $ctx = new \App\Services\Analytics\AnalyticsContext($org, $project, $env);
    $period = (new \App\Services\Analytics\PeriodService)->parse('24h');

    $data = $action->handle($ctx, $period);

    expect($data['requests']['total'])->toBe(1);
});
