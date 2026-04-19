<?php

use App\Actions\Issues\AddComment;
use App\Actions\Issues\AssignIssue;
use App\Actions\Issues\CreateIssue;
use App\Actions\Issues\DeleteComment;
use App\Actions\Issues\UpdateIssueStatus;
use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Enums\IssueStatus;
use App\Enums\TimelineEventKind;
use App\Models\IssueAssignmentEvent;
use App\Models\IssueComment;
use App\Models\IssueCreationEvent;
use App\Models\IssueStatusChangeEvent;
use App\Models\IssueTimelineEntry;
use App\Models\OrganizationMember;
use App\Models\User;

function timelineContext(string $suffix = ''): array
{
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Timeline Org '.$suffix, 'slug' => 'timeline-org-'.$suffix]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'timeline-app-'.$suffix]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production',
        'slug' => 'timeline-prod-'.$suffix,
        'type' => 'production',
    ])->environment;

    return compact('user', 'org', 'project', 'env');
}

test('creating an issue records a single creation timeline entry', function () {
    $ctx = timelineContext(uniqid());

    $issue = app(CreateIssue::class)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Timeline Test',
        'fingerprint' => hash('sha256', 'timeline-create-'.uniqid()),
    ]);

    $entries = IssueTimelineEntry::query()
        ->where('issue_id', $issue->id)
        ->with('eventable')
        ->get();

    expect($entries)->toHaveCount(1)
        ->and($entries->first()->eventable)->toBeInstanceOf(IssueCreationEvent::class)
        ->and($entries->first()->eventable->eventKind())->toBe(TimelineEventKind::IssueCreated);
});

test('posting a comment creates a single timeline entry backed by the comment', function () {
    $ctx = timelineContext(uniqid());

    $issue = app(CreateIssue::class)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Comment Timeline',
        'fingerprint' => hash('sha256', 'timeline-comment-'.uniqid()),
    ]);

    $comment = app(AddComment::class)->handle($issue, 'Hello world', $ctx['user']);

    $commentEntries = IssueTimelineEntry::query()
        ->where('issue_id', $issue->id)
        ->where('eventable_type', (new IssueComment)->getMorphClass())
        ->get();

    expect($commentEntries)->toHaveCount(1)
        ->and($commentEntries->first()->eventable_id)->toBe($comment->id);
});

test('status change is recorded as a status_change timeline entry', function () {
    $ctx = timelineContext(uniqid());

    $issue = app(CreateIssue::class)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Status Timeline',
        'fingerprint' => hash('sha256', 'timeline-status-'.uniqid()),
    ]);

    app(UpdateIssueStatus::class)->handle($issue, IssueStatus::Resolved, $ctx['user']);

    $entry = IssueTimelineEntry::query()
        ->where('issue_id', $issue->id)
        ->where('eventable_type', (new IssueStatusChangeEvent)->getMorphClass())
        ->with('eventable')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->eventable->from_status)->toBe(IssueStatus::Open)
        ->and($entry->eventable->to_status)->toBe(IssueStatus::Resolved);
});

test('assignment change records a timeline entry with from/to users', function () {
    $ctx = timelineContext(uniqid());

    $assignee = User::factory()->create();
    $role = $ctx['org']->roles()->where('slug', 'developer')->first();
    OrganizationMember::create([
        'organization_id' => $ctx['org']->id,
        'user_id' => $assignee->id,
        'organization_role_id' => $role->id,
    ]);

    $issue = app(CreateIssue::class)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Assign Timeline',
        'fingerprint' => hash('sha256', 'timeline-assign-'.uniqid()),
    ]);

    app(AssignIssue::class)->handle($issue, $assignee->id, $ctx['user']);

    $entry = IssueTimelineEntry::query()
        ->where('issue_id', $issue->id)
        ->where('eventable_type', (new IssueAssignmentEvent)->getMorphClass())
        ->with('eventable')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->eventable->from_user_id)->toBeNull()
        ->and($entry->eventable->to_user_id)->toBe($assignee->id);
});

test('deleted comments stay in the timeline and are marked as deleted', function () {
    $ctx = timelineContext(uniqid());

    $issue = app(CreateIssue::class)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Delete Timeline',
        'fingerprint' => hash('sha256', 'timeline-delete-'.uniqid()),
    ]);

    $comment = app(AddComment::class)->handle($issue, 'Sensitive info', $ctx['user']);
    $comment->load('issue');
    app(DeleteComment::class)->handle($comment, $ctx['user']);

    $entry = IssueTimelineEntry::query()
        ->where('issue_id', $issue->id)
        ->where('eventable_type', (new IssueComment)->getMorphClass())
        ->with('eventable')
        ->first();

    expect($entry)->not->toBeNull()
        ->and($entry->eventable)->not->toBeNull()
        ->and($entry->eventable->trashed())->toBeTrue();
});

test('issue show endpoint returns chronological timeline data', function () {
    $ctx = timelineContext(uniqid());

    $issue = app(CreateIssue::class)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Endpoint Timeline',
        'fingerprint' => hash('sha256', 'timeline-endpoint-'.uniqid()),
    ]);

    app(AddComment::class)->handle($issue, 'First', $ctx['user']);
    app(UpdateIssueStatus::class)->handle($issue, IssueStatus::Resolved, $ctx['user']);

    $baseUrl = "/environments/{$ctx['env']->slug}/issues";

    $this->actingAs($ctx['user'])
        ->get("{$baseUrl}/{$issue->id}")
        ->assertInertia(fn ($page) => $page
            ->component('issues/show')
            ->has('timeline.data', 3)
            ->where('timeline.data.0.kind', TimelineEventKind::IssueCreated->value)
            ->where('timeline.data.1.kind', TimelineEventKind::Commented->value)
            ->where('timeline.data.2.kind', TimelineEventKind::StatusChanged->value)
        );
});
