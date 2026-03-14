<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\Exception\BuildExceptionDetailData;
use App\Actions\Analytics\Exception\BuildExceptionIndexData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExceptionController extends AnalyticsController
{
    public function __construct(
        private readonly BuildExceptionIndexData $buildIndex,
        private readonly BuildExceptionDetailData $buildDetail,
    ) {}

    /**
     * Display aggregated exception analytics grouped by group_key.
     */
    public function index(Request $request, string $organization, string $project, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $data = $this->buildIndex->handle(
            ctx: $ctx,
            period: $period,
            search: $request->query('search'),
        );

        return Inertia::render('analytics/exceptions/index', [
            'analytics' => $data,
            'period' => $request->query('period', '24h'),
        ]);
    }

    /**
     * Display detail for a specific exception group.
     */
    public function show(Request $request, string $organization, string $project, string $environment, string $group): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $data = $this->buildDetail->handle($ctx, $period, $group);

        return Inertia::render('analytics/exceptions/show', [
            'analytics' => $data,
            'period' => $request->query('period', '24h'),
        ]);
    }
}
