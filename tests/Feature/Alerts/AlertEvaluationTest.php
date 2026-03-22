<?php

use App\Actions\Alerts\CreateAlertRule;
use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Jobs\EvaluateAlertRules;
use App\Jobs\SendAlertTriggeredNotification;
use App\Models\AlertState;
use App\Models\User;
use App\Services\Alerts\AlertEvaluator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;

function setupEvalContext(string $suffix = ''): array
{
    $owner = User::factory()->create();
    $org = (new CreateOrganization)->handle($owner, ['name' => 'Eval Org '.$suffix, 'slug' => 'eval-org-'.$suffix]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'eval-app-'.$suffix]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production', 'slug' => 'eval-prod-'.$suffix, 'type' => 'production',
    ]);

    return compact('owner', 'org', 'project', 'env');
}

test('evaluator returns triggered when metric exceeds threshold', function () {
    $ctx = setupEvalContext(uniqid());

    // Insert 10 requests, 6 with 5xx errors = 60% error rate
    for ($i = 0; $i < 10; $i++) {
        $telemetryId = nextTelemetryId($ctx);
        DB::table('extraction_requests')->insert([
            'telemetry_record_id' => $telemetryId,
            'organization_id' => $ctx['org']->id,
            'project_id' => $ctx['project']->id,
            'environment_id' => $ctx['env']->id,
            'method' => 'GET',
            'url' => 'http://example.com',
            'status_code' => $i < 6 ? 500 : 200,
            'duration' => 100,
            'exceptions' => 0,
            'queries' => 0,
            'recorded_at' => now(),
        ]);
    }

    $rule = (new CreateAlertRule)->handle($ctx['org'], $ctx['project'], $ctx['env'], [
        'name' => 'Error Rate Test',
        'metric' => 'error_rate',
        'operator' => '>',
        'threshold' => 50.0,
        'window_minutes' => 60,
        'recipient_ids' => [$ctx['owner']->id],
    ]);

    $result = (new AlertEvaluator)->evaluate($rule);

    expect($result['triggered'])->toBeTrue()
        ->and($result['value'])->toBeGreaterThan(50);
});

test('evaluator returns not triggered when metric is below threshold', function () {
    $ctx = setupEvalContext(uniqid());

    // Insert 10 requests, only 1 with 5xx = 10% error rate
    for ($i = 0; $i < 10; $i++) {
        $telemetryId = nextTelemetryId($ctx);
        DB::table('extraction_requests')->insert([
            'telemetry_record_id' => $telemetryId,
            'organization_id' => $ctx['org']->id,
            'project_id' => $ctx['project']->id,
            'environment_id' => $ctx['env']->id,
            'method' => 'GET',
            'url' => 'http://example.com',
            'status_code' => $i === 0 ? 500 : 200,
            'duration' => 100,
            'exceptions' => 0,
            'queries' => 0,
            'recorded_at' => now(),
        ]);
    }

    $rule = (new CreateAlertRule)->handle($ctx['org'], $ctx['project'], $ctx['env'], [
        'name' => 'Low Error Rate',
        'metric' => 'error_rate',
        'operator' => '>',
        'threshold' => 50.0,
        'window_minutes' => 60,
        'recipient_ids' => [$ctx['owner']->id],
    ]);

    $result = (new AlertEvaluator)->evaluate($rule);

    expect($result['triggered'])->toBeFalse();
});

test('ok to triggered transition dispatches notification', function () {
    Bus::fake();
    $ctx = setupEvalContext(uniqid());

    $rule = (new CreateAlertRule)->handle($ctx['org'], $ctx['project'], $ctx['env'], [
        'name' => 'Test Alert',
        'metric' => 'exception_count',
        'operator' => '>=',
        'threshold' => 0.0,
        'window_minutes' => 60,
        'recipient_ids' => [$ctx['owner']->id],
    ]);

    // Insert an exception to trigger (exception_count >= 0 is true with any data)
    $telemetryId = nextTelemetryId($ctx);
    DB::table('extraction_exceptions')->insert([
        'telemetry_record_id' => $telemetryId,
        'organization_id' => $ctx['org']->id,
        'project_id' => $ctx['project']->id,
        'environment_id' => $ctx['env']->id,
        'class' => 'RuntimeException',
        'message' => 'Test',
        'handled' => 0,
        'recorded_at' => now(),
    ]);

    (new EvaluateAlertRules)->handle(new AlertEvaluator);

    Bus::assertDispatched(SendAlertTriggeredNotification::class);

    $state = AlertState::where('alert_rule_id', $rule->id)->first();
    expect($state?->status)->toBe('triggered');
});

test('no re-notification when already triggered', function () {
    Bus::fake();
    $ctx = setupEvalContext(uniqid());

    $rule = (new CreateAlertRule)->handle($ctx['org'], $ctx['project'], $ctx['env'], [
        'name' => 'Already Triggered',
        'metric' => 'exception_count',
        'operator' => '>=',
        'threshold' => 0.0,
        'window_minutes' => 60,
        'recipient_ids' => [$ctx['owner']->id],
    ]);

    // Set state to already triggered
    AlertState::create(['alert_rule_id' => $rule->id, 'status' => 'triggered']);

    // Insert data that would trigger
    $telemetryId = nextTelemetryId($ctx);
    DB::table('extraction_exceptions')->insert([
        'telemetry_record_id' => $telemetryId,
        'organization_id' => $ctx['org']->id,
        'project_id' => $ctx['project']->id,
        'environment_id' => $ctx['env']->id,
        'class' => 'RuntimeException',
        'message' => 'Test',
        'handled' => 0,
        'recorded_at' => now(),
    ]);

    (new EvaluateAlertRules)->handle(new AlertEvaluator);

    Bus::assertNotDispatched(SendAlertTriggeredNotification::class);
});

test('disabled rules are not evaluated', function () {
    Bus::fake();
    $ctx = setupEvalContext(uniqid());

    $rule = (new CreateAlertRule)->handle($ctx['org'], $ctx['project'], $ctx['env'], [
        'name' => 'Disabled Alert',
        'metric' => 'exception_count',
        'operator' => '>=',
        'threshold' => 0.0,
        'window_minutes' => 60,
        'recipient_ids' => [$ctx['owner']->id],
    ]);
    $rule->update(['enabled' => false]);

    (new EvaluateAlertRules)->handle(new AlertEvaluator);

    Bus::assertNotDispatched(SendAlertTriggeredNotification::class);
    $this->assertDatabaseEmpty('alert_histories');
});
