<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\ScheduledTask\BuildScheduledTaskIndexData;
use App\Actions\Analytics\ScheduledTask\BuildScheduledTaskRunData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduledTaskController extends AnalyticsController
{
    public function __construct(
        private readonly BuildScheduledTaskIndexData $buildIndex,
        private readonly BuildScheduledTaskRunData $buildRuns,
    ) {}

    /**
     * Display aggregated scheduled task analytics.
     */
    public function index(Request $request, string $organization, string $project, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $data = $this->buildIndex->handle($ctx, $period);

        return Inertia::render('analytics/scheduled-tasks/index', [
            'analytics' => $data,
            'period' => $request->query('period', '24h'),
        ]);
    }

    /**
     * Display individual runs for a scheduled task.
     */
    public function show(Request $request, string $organization, string $project, string $environment, string $scheduledTask): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $data = $this->buildRuns->handle(
            ctx: $ctx,
            period: $period,
            name: (string) $request->query('name', $scheduledTask),
            cron: (string) $request->query('cron', ''),
        );

        return Inertia::render('analytics/scheduled-tasks/show', [
            'analytics' => $data,
            'period' => $request->query('period', '24h'),
        ]);
    }
}
