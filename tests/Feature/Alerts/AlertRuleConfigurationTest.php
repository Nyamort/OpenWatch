<?php

use App\Actions\Alerts\CreateAlertRule;
use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\OrganizationMember;
use App\Models\User;

function setupAlertsContext(string $suffix = ''): array
{
    $owner = User::factory()->create();
    $org = (new CreateOrganization)->handle($owner, ['name' => 'Alert Org '.$suffix, 'slug' => 'alert-org-'.$suffix]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'alert-app-'.$suffix]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production', 'slug' => 'alert-prod-'.$suffix, 'type' => 'production',
    ])->environment;

    return compact('owner', 'org', 'project', 'env');
}

function validRulePayload(array $ctx): array
{
    return [
        'name' => 'High Error Rate',
        'metric' => 'error_rate',
        'operator' => '>',
        'threshold' => 5.0,
        'window_minutes' => 60,
        'recipient_ids' => [$ctx['owner']->id],
    ];
}

function alertRuleUrl(array $ctx, string $suffix = ''): string
{
    return "/environments/{$ctx['env']->slug}/alert-rules{$suffix}";
}

test('owner can create alert rule', function () {
    $ctx = setupAlertsContext(uniqid());

    $response = $this->actingAs($ctx['owner'])->post(alertRuleUrl($ctx), validRulePayload($ctx));
    $response->assertRedirect();

    $this->assertDatabaseHas('alert_rules', ['name' => 'High Error Rate', 'organization_id' => $ctx['org']->id]);
});

test('cannot create rule with no recipients', function () {
    $ctx = setupAlertsContext(uniqid());

    $payload = validRulePayload($ctx);
    $payload['recipient_ids'] = [];

    $response = $this->actingAs($ctx['owner'])->post(alertRuleUrl($ctx), $payload);
    $response->assertSessionHasErrors('recipient_ids');
});

test('viewer cannot create alert rule', function () {
    $ctx = setupAlertsContext(uniqid());
    $viewer = User::factory()->create();
    $viewerRole = $ctx['org']->roles()->where('slug', 'viewer')->first();
    OrganizationMember::create([
        'organization_id' => $ctx['org']->id,
        'user_id' => $viewer->id,
        'organization_role_id' => $viewerRole->id,
    ]);

    $response = $this->actingAs($viewer)->post(alertRuleUrl($ctx), validRulePayload($ctx));
    $response->assertStatus(403);
});

test('delete requires confirmation matching rule name', function () {
    $ctx = setupAlertsContext(uniqid());
    $rule = (new CreateAlertRule)->handle($ctx['org'], $ctx['project'], $ctx['env'], validRulePayload($ctx));

    // Wrong confirmation
    $response = $this->actingAs($ctx['owner'])->delete(alertRuleUrl($ctx, "/{$rule->id}"), ['confirmation' => 'wrong name']);
    $response->assertSessionHasErrors('confirmation');

    // Correct confirmation
    $response = $this->actingAs($ctx['owner'])->delete(alertRuleUrl($ctx, "/{$rule->id}"), ['confirmation' => $rule->name]);
    $response->assertRedirect();
    $this->assertDatabaseMissing('alert_rules', ['id' => $rule->id]);
});
