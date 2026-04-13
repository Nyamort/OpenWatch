<?php

use App\Actions\Issues\CreateIssue;
use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Enums\IssueStatus;
use App\Events\IssueCreated;
use App\Models\Issue;
use App\Models\IssueSource;
use App\Models\User;
use Illuminate\Support\Facades\Event;

function issueContext(string $suffix = ''): array
{
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Issue Org '.$suffix, 'slug' => 'issue-org-'.$suffix]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'issue-app-'.$suffix]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production',
        'slug' => 'issue-prod-'.$suffix,
        'type' => 'production',
    ])->environment;

    return compact('user', 'org', 'project', 'env');
}

test('first occurrence creates a new issue', function () {
    $ctx = issueContext(uniqid());
    $fingerprint = hash('sha256', 'test-exception-'.uniqid());

    $action = new CreateIssue;
    $issue = $action->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Test Exception',
        'fingerprint' => $fingerprint,
        'type' => 'exception',
    ]);

    expect($issue)->toBeInstanceOf(Issue::class)
        ->and($issue->title)->toBe('Test Exception')
        ->and($issue->fingerprint)->toBe($fingerprint)
        ->and($issue->status)->toBe(IssueStatus::Open)
        ->and($issue->occurrence_count)->toBe(1);

    $this->assertDatabaseHas('issues', [
        'id' => $issue->id,
        'fingerprint' => $fingerprint,
        'status' => 'open',
    ]);
});

test('second occurrence increments count not creates duplicate', function () {
    $ctx = issueContext(uniqid());
    $fingerprint = hash('sha256', 'duplicate-'.uniqid());

    $action = new CreateIssue;
    $action->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Duplicate Exception',
        'fingerprint' => $fingerprint,
    ]);

    $action->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Duplicate Exception',
        'fingerprint' => $fingerprint,
    ]);

    $count = Issue::where('fingerprint', $fingerprint)->count();
    expect($count)->toBe(1);

    $issue = Issue::where('fingerprint', $fingerprint)->first();
    expect($issue->occurrence_count)->toBe(2);
});

test('issue creation emits IssueCreated event', function () {
    $ctx = issueContext(uniqid());

    Event::fake();

    $fingerprint = hash('sha256', 'event-'.uniqid());

    (new CreateIssue)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Event Test',
        'fingerprint' => $fingerprint,
    ]);

    Event::assertDispatched(IssueCreated::class);
});

test('issue creation stores source linkage', function () {
    $ctx = issueContext(uniqid());
    $fingerprint = hash('sha256', 'source-'.uniqid());
    $traceId = 'trace-'.uniqid();
    $groupKey = hash('sha256', 'group-'.uniqid());

    $issue = (new CreateIssue)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Source Test',
        'fingerprint' => $fingerprint,
        'source_type' => 'exception',
        'trace_id' => $traceId,
        'group_key' => $groupKey,
        'snapshot' => ['class' => 'App\\Exceptions\\Test', 'line' => 42],
    ]);

    $source = IssueSource::where('issue_id', $issue->id)->first();

    expect($source)->not->toBeNull()
        ->and($source->source_type)->toBe('exception')
        ->and($source->trace_id)->toBe($traceId)
        ->and($source->group_key)->toBe($groupKey)
        ->and($source->snapshot)->toEqual(['class' => 'App\\Exceptions\\Test', 'line' => 42]);
});
