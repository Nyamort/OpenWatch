<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\Job\BuildJobDetailData;
use App\Actions\Analytics\Job\BuildJobsIndexData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobsController extends AnalyticsController
{
    public function __construct(
        private readonly BuildJobsIndexData $buildIndex,
        private readonly BuildJobDetailData $buildDetail,
    ) {}

    /**
     * Display aggregated jobs analytics.
     */
    public function index(Request $request, string $organization, string $project, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $data = $this->buildIndex->handle($ctx, $period);

        return Inertia::render('analytics/jobs/index', [
            'analytics' => $data,
            'period' => $request->query('period', '24h'),
        ]);
    }

    /**
     * Display a single queued job with its attempts.
     */
    public function show(Request $request, string $organization, string $project, string $environment, int $job): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);

        $data = $this->buildDetail->handle($ctx, $job);

        return Inertia::render('analytics/jobs/show', [
            'analytics' => $data,
        ]);
    }
}
