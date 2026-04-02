<?php

use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\User;
use App\Services\ClickHouse\ClickHouseService;

function setupMailContext(string $suffix = ''): array
{
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Mail Org '.$suffix, 'slug' => 'mail-org-'.$suffix]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'mail-app-'.$suffix]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production',
        'slug' => 'mail-prod-'.$suffix,
        'type' => 'production',
    ])->environment;

    return compact('user', 'org', 'project', 'env');
}

function insertMail(array $ctx, array $overrides = []): void
{
    $data = array_merge([
        'telemetry_record_id' => nextTelemetryId(),
        'organization_id' => $ctx['org']->id,
        'project_id' => $ctx['project']->id,
        'environment_id' => $ctx['env']->id,
        'mailer' => 'smtp',
        'class' => 'App\\Mail\\WelcomeMail',
        'subject' => 'Welcome!',
        'to' => json_encode(['user@example.com']),
        'duration' => 200,
        'failed' => 0,
        'recorded_at' => now()->utc()->format('Y-m-d H:i:s'),
    ], $overrides);

    // Normalize boolean to UInt8
    $data['failed'] = $data['failed'] ? 1 : 0;

    app(ClickHouseService::class)->insert('extraction_mails', [$data]);
}

test('mail index groups by class and mailer', function () {
    $ctx = setupMailContext(uniqid());

    insertMail($ctx, ['class' => 'App\\Mail\\WelcomeMail', 'mailer' => 'smtp', 'failed' => 0, 'duration' => 100]);
    insertMail($ctx, ['class' => 'App\\Mail\\WelcomeMail', 'mailer' => 'smtp', 'failed' => 0, 'duration' => 300]);
    insertMail($ctx, ['class' => 'App\\Mail\\ResetMail', 'mailer' => 'smtp', 'failed' => 0, 'duration' => 150]);

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/mail");

    $response->assertInertia(fn ($page) => $page
        ->component('analytics/mail/index')
        ->has('analytics.rows', 2)
    );
});

test('mail avg duration excludes failed mails', function () {
    $ctx = setupMailContext(uniqid());

    insertMail($ctx, ['class' => 'App\\Mail\\TestMail', 'mailer' => 'smtp', 'failed' => 0, 'duration' => 200]);
    insertMail($ctx, ['class' => 'App\\Mail\\TestMail', 'mailer' => 'smtp', 'failed' => 0, 'duration' => 400]);
    insertMail($ctx, ['class' => 'App\\Mail\\TestMail', 'mailer' => 'smtp', 'failed' => 1, 'duration' => 9999]);

    $response = $this->actingAs($ctx['user'])
        ->get("/organizations/{$ctx['org']->slug}/projects/{$ctx['project']->slug}/environments/{$ctx['env']->slug}/analytics/mail");

    $response->assertInertia(fn ($page) => $page
        ->where('analytics.rows.0.avg_duration', 300)
        ->where('analytics.rows.0.sent_count', 2)
        ->where('analytics.rows.0.failed_count', 1)
    );
});
