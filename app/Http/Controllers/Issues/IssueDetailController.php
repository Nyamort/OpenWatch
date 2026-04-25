<?php

namespace App\Http\Controllers\Issues;

use App\Actions\Issues\BuildIssueDetailData;
use App\Http\Controllers\Controller;
use App\Models\Environment;
use App\Models\Issue;
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
    ): Response {
        $project = $environment->project;
        $organization = $project->organization;

        $data = $action->handle($issue);

        return Inertia::render('issues/show', [
            'organization' => $organization,
            'project' => $project,
            'environment' => $environment,
            'issue' => $data['issue'],
            'timeline' => $data['timeline'],
            'exceptionSummary' => $data['exception_summary'],
        ]);
    }
}
