<?php

use App\Actions\Issues\AddComment;
use App\Actions\Issues\CreateIssue;
use App\Actions\Issues\DeleteComment;
use App\Actions\Issues\EditComment;
use App\Actions\Organization\CreateOrganization;
use App\Actions\Projects\CreateEnvironment;
use App\Actions\Projects\CreateProject;
use App\Actions\Projects\GenerateToken;
use App\Models\OrganizationMember;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

function issueDetailContext(string $suffix = ''): array
{
    $user = User::factory()->create();
    $org = (new CreateOrganization)->handle($user, ['name' => 'Detail Org '.$suffix, 'slug' => 'detail-org-'.$suffix]);
    $project = (new CreateProject)->handle($org, ['name' => 'App', 'slug' => 'detail-app-'.$suffix]);
    $env = (new CreateEnvironment(new GenerateToken))->handle($project, [
        'name' => 'Production',
        'slug' => 'detail-prod-'.$suffix,
        'type' => 'production',
    ])->environment;

    return compact('user', 'org', 'project', 'env');
}

function addDetailMemberWithRole(array $ctx, User $user, string $roleSlug): void
{
    $role = $ctx['org']->roles()->where('slug', $roleSlug)->first();
    OrganizationMember::create([
        'organization_id' => $ctx['org']->id,
        'user_id' => $user->id,
        'organization_role_id' => $role->id,
    ]);
}

test('issue detail loads all sections', function () {
    $ctx = issueDetailContext(uniqid());

    $issue = (new CreateIssue)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Detail Test Issue',
        'fingerprint' => hash('sha256', 'detail-'.uniqid()),
        'source_type' => 'exception',
        'trace_id' => 'trace-abc123',
    ]);

    $baseUrl = "/environments/{$ctx['env']->slug}/issues";

    $response = $this->actingAs($ctx['user'])->get("{$baseUrl}/{$issue->id}");

    $response->assertInertia(fn ($page) => $page
        ->component('issues/show')
        ->has('issue')
        ->has('timeline')
        ->where('issue.id', $issue->id)
    );
});

test('viewer cannot add comment', function () {
    $ctx = issueDetailContext(uniqid());
    $viewer = User::factory()->create();
    addDetailMemberWithRole($ctx, $viewer, 'viewer');

    $issue = (new CreateIssue)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Comment Viewer Test',
        'fingerprint' => hash('sha256', 'viewer-comment-'.uniqid()),
    ]);

    $baseUrl = "/environments/{$ctx['env']->slug}/issues";

    $response = $this->actingAs($viewer)->post("{$baseUrl}/{$issue->id}/comments", [
        'body' => 'This should be blocked.',
    ]);

    $response->assertStatus(403);
});

test('author can edit own comment', function () {
    $ctx = issueDetailContext(uniqid());

    $issue = (new CreateIssue)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Edit Comment Test',
        'fingerprint' => hash('sha256', 'edit-comment-'.uniqid()),
    ]);

    $comment = (new AddComment)->handle($issue, 'Original body', $ctx['user']);

    (new EditComment)->handle($comment, 'Updated body', $ctx['user']);

    $comment->refresh();
    expect($comment->body)->toBe('Updated body')
        ->and($comment->edited_at)->not->toBeNull();
});

test('admin can delete any comment', function () {
    $ctx = issueDetailContext(uniqid());
    $admin = User::factory()->create();
    addDetailMemberWithRole($ctx, $admin, 'admin');

    $commenter = User::factory()->create();
    addDetailMemberWithRole($ctx, $commenter, 'developer');

    $issue = (new CreateIssue)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Admin Delete Test',
        'fingerprint' => hash('sha256', 'admin-delete-'.uniqid()),
    ]);

    $comment = (new AddComment)->handle($issue, 'Comment by commenter', $commenter);

    $comment->load('issue');
    (new DeleteComment(app(\App\Services\Authorization\PermissionResolver::class)))->handle($comment, $admin);

    $this->assertDatabaseMissing('issue_comments', ['id' => $comment->id]);
});

test('non-author non-admin cannot delete comment', function () {
    $ctx = issueDetailContext(uniqid());
    $developer = User::factory()->create();
    addDetailMemberWithRole($ctx, $developer, 'developer');

    $issue = (new CreateIssue)->handle($ctx['org'], $ctx['project'], $ctx['env'], $ctx['user'], [
        'title' => 'Delete Permission Test',
        'fingerprint' => hash('sha256', 'delete-perm-'.uniqid()),
    ]);

    $comment = (new AddComment)->handle($issue, 'Owner comment', $ctx['user']);
    $comment->load('issue');

    expect(fn () => (new DeleteComment(app(\App\Services\Authorization\PermissionResolver::class)))->handle($comment, $developer))
        ->toThrow(AuthorizationException::class);
});
