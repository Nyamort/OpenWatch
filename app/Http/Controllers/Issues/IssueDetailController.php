<?php

namespace App\Http\Controllers\Issues;

use App\Actions\Issues\BuildIssueDetailData;
use App\Http\Controllers\Controller;
use App\Models\Environment;
use App\Models\Issue;
use App\Services\Authorization\PermissionResolver;
use Inertia\Inertia;
use Inertia\Response;

class IssueDetailController extends Controller
{
    /**
     * Display the issue detail page.
     */
    public function show(
        Environment $environment,
        Issue $issue,
        BuildIssueDetailData $action,
        PermissionResolver $permissionResolver,
    ): Response {
        $project = $environment->project;
        $organization = $project->organization;

        $data = $action->handle($issue);
        $viewerRole = $permissionResolver->getRole(auth()->id(), $organization->id);

        return Inertia::render('issues/show', [
            'organization' => $organization,
            'project' => $project,
            'environment' => $environment,
            'issue' => $data['issue'],
            'timeline' => $data['timeline'],
            'viewerRole' => $viewerRole,
        ]);
    }
}
