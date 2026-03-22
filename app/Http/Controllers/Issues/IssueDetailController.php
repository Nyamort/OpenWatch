<?php

namespace App\Http\Controllers\Issues;

use App\Actions\Issues\BuildIssueDetailData;
use App\Http\Controllers\Controller;
use App\Models\Environment;
use App\Models\Issue;
use App\Models\Organization;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class IssueDetailController extends Controller
{
    /**
     * Display the issue detail page.
     */
    public function show(
        Organization $organization,
        Project $project,
        Environment $environment,
        Issue $issue,
        BuildIssueDetailData $action,
    ): Response {
        $data = $action->handle($issue);

        return Inertia::render('issues/show', [
            'organization' => $organization,
            'project' => $project,
            'environment' => $environment,
            'issue' => $data['issue'],
            'comments' => $data['comments'],
        ]);
    }
}
