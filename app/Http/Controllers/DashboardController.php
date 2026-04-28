<?php

namespace App\Http\Controllers;

use App\Actions\Analytics\Job\BuildJobsIndexData;
use App\Actions\Analytics\Request\BuildRequestIndexData;
use App\Actions\Analytics\User\BuildUserIndexData;
use App\Services\Analytics\AnalyticsContextResolver;
use App\Services\Analytics\PeriodService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly BuildRequestIndexData $buildRequestData,
        private readonly BuildJobsIndexData $buildJobsData,
        private readonly BuildUserIndexData $buildUserData,
        private readonly AnalyticsContextResolver $contextResolver,
        private readonly PeriodService $periodService,
    ) {}

    public function __invoke(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $envSlug = $request->query('env');
        $periodStr = (string) $request->query('period', '24h');

        if (! $envSlug) {
            $activeOrg = $user->activeOrganization;
            $firstProject = $activeOrg?->projects()->first();
            $firstEnv = $firstProject?->environments()->first();

            if (! $firstEnv) {
                return Inertia::render('dashboard', [
                    'hasContext' => false,
                    'period' => $periodStr,
                ]);
            }

            $envSlug = $firstEnv->slug;
        }

        try {
            $ctx = $this->contextResolver->resolve($envSlug, $user);
            $period = $this->periodService->parse($periodStr);
        } catch (\Throwable) {
            return Inertia::render('dashboard', [
                'hasContext' => false,
                'period' => $periodStr,
            ]);
        }

        $requestData = null;
        $resolveRequest = function () use (&$requestData, $ctx, $period): array {
            return $requestData ??= $this->buildRequestData->handle(ctx: $ctx, period: $period);
        };

        $jobsData = null;
        $resolveJobs = function () use (&$jobsData, $ctx, $period): array {
            return $jobsData ??= $this->buildJobsData->handle(ctx: $ctx, period: $period);
        };

        $userData = null;
        $resolveUser = function () use (&$userData, $ctx, $period): array {
            return $userData ??= $this->buildUserData->handle(ctx: $ctx, period: $period);
        };

        return Inertia::render('dashboard', [
            'hasContext' => true,
            'period' => $periodStr,
            'context' => [
                'org' => $ctx->organization->slug,
                'project' => $ctx->project->slug,
                'env' => $ctx->environment->slug,
            ],
            'requestGraph' => Inertia::defer(fn () => $resolveRequest()['graph']),
            'requestStats' => Inertia::defer(fn () => $resolveRequest()['stats']),
            'jobGraph' => Inertia::defer(fn () => $resolveJobs()['graph']),
            'jobStats' => Inertia::defer(fn () => $resolveJobs()['stats']),
            'userGraph' => Inertia::defer(fn () => $resolveUser()['graph']),
            'userStats' => Inertia::defer(fn () => $resolveUser()['stats']),
        ]);
    }
}
