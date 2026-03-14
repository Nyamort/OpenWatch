<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\Query\BuildQueryDetailData;
use App\Actions\Analytics\Query\BuildQueryIndexData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class QueryController extends AnalyticsController
{
    public function __construct(
        private readonly BuildQueryIndexData $buildIndex,
        private readonly BuildQueryDetailData $buildDetail,
    ) {}

    /**
     * Display aggregated query analytics.
     */
    public function index(Request $request, string $organization, string $project, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $data = $this->buildIndex->handle($ctx, $period);

        return Inertia::render('analytics/queries/index', [
            'analytics' => $data,
            'period' => $request->query('period', '24h'),
        ]);
    }

    /**
     * Display detail for a specific sql_hash.
     */
    public function show(Request $request, string $organization, string $project, string $environment, string $query): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $data = $this->buildDetail->handle($ctx, $period, $query);

        return Inertia::render('analytics/queries/show', [
            'analytics' => $data,
            'period' => $request->query('period', '24h'),
        ]);
    }
}
