<?php

use App\Actions\Issues\CreateIssue;
use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Enums\IssueStatus;
use App\Events\IssueCreated;
use App\Models\Issue;
use App\Models\IssueExceptionDetail;
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

test('exception issue tracks unique user count across occurrences', function () {
    $ctx = issueContext(uniqid());
    $fingerprint = hash('sha256', 'user-count-'.uniqid());
    $action = new CreateIssue;

    $action->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'User Count Test',
        'fingerprint' => $fingerprint,
        'type' => 'exception',
        'source_type' => 'exception',
        'user_identifier' => 'user-1',
    ]);

    $issue = Issue::where('fingerprint', $fingerprint)->first();
    $detail = IssueExceptionDetail::find($issue->detail_id);
    expect($detail->user_count)->toBe(1);

    // Same user — count must not change
    $action->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'User Count Test',
        'fingerprint' => $fingerprint,
        'type' => 'exception',
        'source_type' => 'exception',
        'user_identifier' => 'user-1',
    ]);

    $detail->refresh();
    expect($detail->user_count)->toBe(1);

    // New user — count must increment
    $action->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'User Count Test',
        'fingerprint' => $fingerprint,
        'type' => 'exception',
        'source_type' => 'exception',
        'user_identifier' => 'user-2',
    ]);

    $detail->refresh();
    expect($detail->user_count)->toBe(2);
});

test('subtitle is stored when provided', function () {
    $ctx = issueContext(uniqid());
    $fingerprint = hash('sha256', 'subtitle-'.uniqid());

    $issue = (new CreateIssue)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'App\\Exceptions\\PaymentException',
        'subtitle' => 'Card declined for user 42',
        'fingerprint' => $fingerprint,
    ]);

    expect($issue->subtitle)->toBe('Card declined for user 42');
    $this->assertDatabaseHas('issues', ['id' => $issue->id, 'subtitle' => 'Card declined for user 42']);
});

test('subtitle defaults to null when not provided', function () {
    $ctx = issueContext(uniqid());
    $fingerprint = hash('sha256', 'subtitle-null-'.uniqid());

    $issue = (new CreateIssue)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Some Issue',
        'fingerprint' => $fingerprint,
    ]);

    expect($issue->subtitle)->toBeNull();
});

test('non-exception issue does not create exception detail', function () {
    $ctx = issueContext(uniqid());
    $fingerprint = hash('sha256', 'perf-'.uniqid());

    $issue = (new CreateIssue)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Slow endpoint',
        'fingerprint' => $fingerprint,
        'type' => 'performance',
    ]);

    expect($issue->detail_id)->toBeNull();
    $this->assertDatabaseMissing('issue_exception_details', ['id' => $issue->detail_id]);
});
