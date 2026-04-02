<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\Log\BuildLogDetailData;
use App\Actions\Analytics\Log\BuildLogIndexData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends AnalyticsController
{
    public function __construct(
        private readonly BuildLogIndexData $buildIndex,
        private readonly BuildLogDetailData $buildDetail,
    ) {}

    /**
     * Display the log feed.
     */
    public function index(Request $request, string $organization, string $project, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $data = $this->buildIndex->handle(
            ctx: $ctx,
            period: $period,
            level: $request->query('level'),
            search: $request->query('search'),
        );

        return Inertia::render('analytics/logs/index', [
            'analytics' => $data,
            'period' => $request->query('period', '24h'),
        ]);
    }

    /**
     * Display a single log entry.
     */
    public function show(Request $request, string $organization, string $project, string $environment, string $log): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);

        $data = $this->buildDetail->handle($ctx, $log);

        return Inertia::render('analytics/logs/show', [
            'analytics' => $data,
        ]);
    }
}
