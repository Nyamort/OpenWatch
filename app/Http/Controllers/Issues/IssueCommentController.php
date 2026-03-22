<?php

namespace App\Http\Controllers\Issues;

use App\Actions\Issues\AddComment;
use App\Actions\Issues\DeleteComment;
use App\Actions\Issues\EditComment;
use App\Http\Controllers\Controller;
use App\Http\Requests\Issues\StoreCommentRequest;
use App\Http\Requests\Issues\UpdateCommentRequest;
use App\Models\Environment;
use App\Models\Issue;
use App\Models\IssueComment;
use App\Models\Organization;
use App\Models\Project;
use App\Services\Authorization\PermissionResolver;
use Illuminate\Http\RedirectResponse;

class IssueCommentController extends Controller
{
    /**
     * Store a new comment on the issue.
     */
    public function store(
        StoreCommentRequest $request,
        Organization $organization,
        Project $project,
        Environment $environment,
        Issue $issue,
        AddComment $action,
    ): RedirectResponse {
        $this->abortIfViewer($organization->id);

        $action->handle($issue, $request->validated('body'), auth()->user());

        return back();
    }

    /**
     * Update an existing comment (author only).
     */
    public function update(
        UpdateCommentRequest $request,
        Organization $organization,
        Project $project,
        Environment $environment,
        Issue $issue,
        IssueComment $comment,
        EditComment $action,
    ): RedirectResponse {
        $action->handle($comment, $request->validated('body'), auth()->user());

        return back();
    }

    /**
     * Delete a comment (author or admin/owner).
     */
    public function destroy(
        Organization $organization,
        Project $project,
        Environment $environment,
        Issue $issue,
        IssueComment $comment,
        DeleteComment $action,
    ): RedirectResponse {
        $comment->load('issue');
        $action->handle($comment, auth()->user());

        return back();
    }

    /**
     * Abort if the authenticated user is a viewer in the organization.
     */
    private function abortIfViewer(int $organizationId): void
    {
        $role = app(PermissionResolver::class)->getRole(auth()->id(), $organizationId);

        if ($role === 'viewer') {
            abort(403, 'Viewers cannot perform this action.');
        }
    }
}
