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

        $sort = (string) $request->query('sort', 'total');
        $direction = (string) $request->query('direction', 'desc');
        $search = (string) $request->query('search', '');
        $page = max(1, (int) $request->query('page', 1));

        $data = $this->buildIndex->handle(
            ctx: $ctx,
            period: $period,
            sort: $sort,
            direction: $direction,
            search: $search,
            page: $page,
        );

        return Inertia::render('analytics/cache-events/index', [
            'events_graph' => $data['events_graph'],
            'failures_graph' => $data['failures_graph'],
            'stats' => $data['stats'],
            'keys' => $data['keys'],
            'pagination' => $data['pagination'],
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ]);
    }
}
