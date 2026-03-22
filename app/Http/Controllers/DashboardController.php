<?php

namespace App\Http\Controllers;

use App\Actions\Dashboard\BuildActiveAlertsSummary;
use App\Actions\Dashboard\BuildDashboardData;
use App\Actions\Dashboard\BuildRecentIssuesSummary;
use App\Services\Analytics\AnalyticsContextResolver;
use App\Services\Analytics\PeriodService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly BuildDashboardData $buildDashboardData,
        private readonly BuildActiveAlertsSummary $buildAlertsSummary,
        private readonly BuildRecentIssuesSummary $buildIssuesSummary,
        private readonly AnalyticsContextResolver $contextResolver,
        private readonly PeriodService $periodService,
    ) {}

    public function __invoke(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $orgSlug = $request->query('org');
        $projectSlug = $request->query('project');
        $envSlug = $request->query('env');
        $periodStr = $request->query('period', '24h');

        // If no context provided, try to resolve from user's active org
        if (! $orgSlug) {
            $activeOrg = $user->activeOrganization;
            if (! $activeOrg) {
                return Inertia::render('dashboard', [
                    'hasContext' => false,
                    'metrics' => null,
                    'alerts' => null,
                    'recentIssues' => null,
                    'period' => $periodStr,
                ]);
            }

            $firstProject = $activeOrg->projects()->first();
            if (! $firstProject) {
                return Inertia::render('dashboard', [
                    'hasContext' => false,
                    'metrics' => null,
                    'alerts' => null,
                    'recentIssues' => null,
                    'period' => $periodStr,
                ]);
            }

            $firstEnv = $firstProject->environments()->first();
            if (! $firstEnv) {
                return Inertia::render('dashboard', [
                    'hasContext' => false,
                    'metrics' => null,
                    'alerts' => null,
                    'recentIssues' => null,
                    'period' => $periodStr,
                ]);
            }

            $orgSlug = $activeOrg->slug;
            $projectSlug = $firstProject->slug;
            $envSlug = $firstEnv->slug;
        }

        try {
            $ctx = $this->contextResolver->resolve($orgSlug, $projectSlug, $envSlug, $user);
            $period = $this->periodService->parse($periodStr);
        } catch (\Throwable) {
            return Inertia::render('dashboard', [
                'hasContext' => false,
                'metrics' => null,
                'alerts' => null,
                'recentIssues' => null,
                'period' => $periodStr,
            ]);
        }

        return Inertia::render('dashboard', [
            'hasContext' => true,
            'period' => $periodStr,
            'context' => [
                'org' => $ctx->organization->slug,
                'project' => $ctx->project->slug,
                'env' => $ctx->environment->slug,
            ],
            'metrics' => Inertia::defer(fn () => $this->buildDashboardData->handle($ctx, $period)),
            'alerts' => Inertia::defer(fn () => $this->buildAlertsSummary->handle(
                $ctx->organization->id, $ctx->project->id, $ctx->environment->id
            )),
            'recentIssues' => Inertia::defer(fn () => $this->buildIssuesSummary->handle(
                $ctx->organization->id, $ctx->project->id, $ctx->environment->id
            )),
        ]);
    }
}
