<?php

use App\Actions\Issues\AssignIssue;
use App\Actions\Issues\BulkUpdateIssues;
use App\Actions\Issues\CreateIssue;
use App\Actions\Issues\UpdateIssuePriority;
use App\Actions\Issues\UpdateIssueStatus;
use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Models\IssueActivity;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Validation\ValidationException;

function issueListContext(string $suffix = ''): array
{
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'List Org '.$suffix, 'slug' => 'list-org-'.$suffix]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'list-app-'.$suffix]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production',
        'slug' => 'list-prod-'.$suffix,
        'type' => 'production',
    ])->environment;

    return compact('user', 'org', 'project', 'env');
}

function addMemberWithRole(array $ctx, User $user, string $roleSlug): void
{
    $role = $ctx['org']->roles()->where('slug', $roleSlug)->first();
    OrganizationMember::create([
        'organization_id' => $ctx['org']->id,
        'user_id' => $user->id,
        'organization_role_id' => $role->id,
    ]);
}

test('issue list is filtered by status', function () {
    $ctx = issueListContext(uniqid());
    $createIssue = new CreateIssue;

    $openIssue = $createIssue->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Open Issue',
        'fingerprint' => hash('sha256', 'open-'.uniqid()),
    ]);

    $resolvedIssue = $createIssue->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Will be resolved',
        'fingerprint' => hash('sha256', 'resolved-'.uniqid()),
    ]);

    (new UpdateIssueStatus)->handle($resolvedIssue, IssueStatus::Resolved, $ctx['user']);

    $baseUrl = "/environments/{$ctx['env']->slug}/issues";

    $response = $this->actingAs($ctx['user'])->get("{$baseUrl}?status=open");
    $response->assertInertia(fn ($page) => $page
        ->component('issues/index')
        ->where('issues.0.id', $openIssue->id)
    );

    $response2 = $this->actingAs($ctx['user'])->get("{$baseUrl}?status=resolved");
    $response2->assertInertia(fn ($page) => $page
        ->component('issues/index')
        ->where('issues.0.id', $resolvedIssue->id)
    );
});

test('issue status transition open to resolved creates activity', function () {
    $ctx = issueListContext(uniqid());

    $issue = (new CreateIssue)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Status Transition Issue',
        'fingerprint' => hash('sha256', 'transition-'.uniqid()),
    ]);

    (new UpdateIssueStatus)->handle($issue, IssueStatus::Resolved, $ctx['user']);

    $activity = IssueActivity::where('issue_id', $issue->id)
        ->where('type', 'status_changed')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->metadata)->toEqual(['from' => 'open', 'to' => 'resolved']);
});

test('issue status transition resolved to ignored is allowed', function () {
    $ctx = issueListContext(uniqid());

    $issue = (new CreateIssue)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Resolved to Ignored Issue',
        'fingerprint' => hash('sha256', 'transition-resolved-ignored-'.uniqid()),
    ]);

    (new UpdateIssueStatus)->handle($issue, IssueStatus::Resolved, $ctx['user']);
    (new UpdateIssueStatus)->handle($issue->fresh(), IssueStatus::Ignored, $ctx['user']);

    expect($issue->fresh()->status)->toBe(IssueStatus::Ignored);
});

test('issue status transition ignored to resolved is allowed', function () {
    $ctx = issueListContext(uniqid());

    $issue = (new CreateIssue)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Ignored to Resolved Issue',
        'fingerprint' => hash('sha256', 'transition-ignored-resolved-'.uniqid()),
    ]);

    (new UpdateIssueStatus)->handle($issue, IssueStatus::Ignored, $ctx['user']);
    (new UpdateIssueStatus)->handle($issue->fresh(), IssueStatus::Resolved, $ctx['user']);

    expect($issue->fresh()->status)->toBe(IssueStatus::Resolved);
});

test('changing issue priority creates activity', function () {
    $ctx = issueListContext(uniqid());

    $issue = (new CreateIssue)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Priority Change Issue',
        'fingerprint' => hash('sha256', 'priority-'.uniqid()),
    ]);

    (new UpdateIssuePriority)->handle($issue, IssuePriority::High, $ctx['user']);

    $activity = IssueActivity::where('issue_id', $issue->id)
        ->where('type', 'priority_changed')
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->metadata['to'])->toBe('high')
        ->and($issue->fresh()->priority)->toBe(IssuePriority::High);
});

test('bulk update skips out-of-scope issues', function () {
    $ctxA = issueListContext(uniqid());
    $ctxB = issueListContext(uniqid());

    $issueA = (new CreateIssue)->handle($ctxA['org'], $ctxA['project'], $ctxA['env'], $ctxA['user'], [
        'title' => 'Issue in Org A',
        'fingerprint' => hash('sha256', 'bulk-a-'.uniqid()),
    ]);

    $issueB = (new CreateIssue)->handle($ctxB['org'], $ctxB['project'], $ctxB['env'], $ctxB['user'], [
        'title' => 'Issue in Org B',
        'fingerprint' => hash('sha256', 'bulk-b-'.uniqid()),
    ]);

    $result = (new BulkUpdateIssues(new UpdateIssueStatus))->handle(
        $ctxA['org'],
        $ctxA['project'],
        $ctxA['env'],
        [$issueA->id, $issueB->id],
        'resolve',
        $ctxA['user'],
    );

    expect($result['processed'])->toBe(1)
        ->and($result['skipped'])->toBe(1);

    $issueA->refresh();
    $issueB->refresh();
    expect($issueA->status)->toBe(IssueStatus::Resolved)
        ->and($issueB->status)->toBe(IssueStatus::Open);
});

test('viewer cannot bulk update issues', function () {
    $ctx = issueListContext(uniqid());
    $viewer = User::factory()->create();
    addMemberWithRole($ctx, $viewer, 'viewer');

    $baseUrl = "/environments/{$ctx['env']->slug}/issues";

    $issue = (new CreateIssue)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Viewer Test Issue',
        'fingerprint' => hash('sha256', 'viewer-'.uniqid()),
    ]);

    $response = $this->actingAs($viewer)->post("{$baseUrl}/bulk", [
        'issue_ids' => [$issue->id],
        'action' => 'resolve',
    ]);

    $response->assertStatus(403);
});

test('issue list exposes subtitle in response', function () {
    $ctx = issueListContext(uniqid());

    (new CreateIssue)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'App\\Exceptions\\SubtitleListTest',
        'subtitle' => 'Something went wrong in the list',
        'fingerprint' => hash('sha256', 'subtitle-list-'.uniqid()),
    ]);

    $baseUrl = "/environments/{$ctx['env']->slug}/issues";

    $this->actingAs($ctx['user'])->get("{$baseUrl}?status=open")
        ->assertInertia(fn ($page) => $page
            ->component('issues/index')
            ->where('issues.0.subtitle', 'Something went wrong in the list')
        );
});

test('assigning issue to non-member is rejected', function () {
    $ctx = issueListContext(uniqid());
    $nonMember = User::factory()->create();

    $issue = (new CreateIssue)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Assign Test',
        'fingerprint' => hash('sha256', 'assign-'.uniqid()),
    ]);

    expect(fn () => (new AssignIssue)->handle($issue, $nonMember->id, $ctx['user']))
        ->toThrow(ValidationException::class);
});
