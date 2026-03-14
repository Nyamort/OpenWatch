<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\CacheEvent\BuildCacheEventIndexData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CacheEventController extends AnalyticsController
{
    public function __construct(
        private readonly BuildCacheEventIndexData $buildIndex,
    ) {}

    /**
     * Display aggregated cache event analytics.
     */
    public function index(Request $request, string $organization, string $project, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $data = $this->buildIndex->handle(
            ctx: $ctx,
            period: $period,
            store: $request->query('store'),
            keySearch: $request->query('key'),
        );

        return Inertia::render('analytics/cache-events/index', [
            'analytics' => $data,
            'period' => $request->query('period', '24h'),
        ]);
    }
}
