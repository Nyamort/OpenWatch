<?php

namespace App\Http\Controllers\Issues;

use App\Actions\Issues\AssignIssue;
use App\Actions\Issues\BuildIssueListData;
use App\Actions\Issues\BulkUpdateIssues;
use App\Actions\Issues\CreateIssue;
use App\Actions\Issues\UpdateIssueStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Issues\BulkUpdateIssuesRequest;
use App\Http\Requests\Issues\StoreIssueRequest;
use App\Http\Requests\Issues\UpdateIssueRequest;
use App\Models\Environment;
use App\Models\Issue;
use App\Services\Authorization\PermissionResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class IssueController extends Controller
{
    /**
     * Display a listing of issues for the environment.
     */
    public function index(
        Environment $environment,
        BuildIssueListData $action,
    ): Response {
        $project = $environment->project;
        $organization = $project->organization;

        $data = $action->handle($organization, $project, $environment, request());

        $members = $organization->members()->with('user:id,name,email')->get()
            ->map(fn ($member) => $member->user)
            ->filter()
            ->values();

        return Inertia::render('issues/index', [
            'organization' => $organization,
            'project' => $project,
            'environment' => $environment,
            'issues' => $data['issues'],
            'pagination' => $data['pagination'],
            'filters' => $data['filters'],
            'sort' => $data['filters']['sort'],
            'direction' => $data['filters']['direction'],
            'members' => $members,
        ]);
    }

    /**
     * Store a newly created issue.
     */
    public function store(
        StoreIssueRequest $request,
        Environment $environment,
        CreateIssue $action,
    ): RedirectResponse {
        $project = $environment->project;
        $organization = $project->organization;

        $this->abortIfViewer($organization->id);

        $issue = $action->handle($organization, $project, $environment, auth()->user(), $request->validated());

        return to_route('issues.show', [$environment, $issue]);
    }

    /**
     * Update an issue's status or assignee.
     */
    public function update(
        UpdateIssueRequest $request,
        Environment $environment,
        Issue $issue,
        UpdateIssueStatus $updateStatus,
        AssignIssue $assignIssue,
    ): RedirectResponse {
        $organization = $environment->project->organization;

        $this->abortIfViewer($organization->id);

        $validated = $request->validated();

        if (array_key_exists('status', $validated)) {
            $updateStatus->handle($issue, $validated['status'], auth()->user());
        }

        if (array_key_exists('assignee_id', $validated)) {
            $assignIssue->handle($issue, $validated['assignee_id'], auth()->user());
        }

        if (array_key_exists('priority', $validated)) {
            $issue->update(['priority' => $validated['priority']]);
        }

        return back();
    }

    /**
     * Bulk update multiple issues.
     */
    public function bulkUpdate(
        BulkUpdateIssuesRequest $request,
        Environment $environment,
        BulkUpdateIssues $action,
    ): JsonResponse {
        $project = $environment->project;
        $organization = $project->organization;

        $this->abortIfViewer($organization->id);

        $validated = $request->validated();

        $result = $action->handle(
            $organization,
            $project,
            $environment,
            $validated['issue_ids'],
            $validated['action'],
            auth()->user(),
        );

        return response()->json($result);
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
