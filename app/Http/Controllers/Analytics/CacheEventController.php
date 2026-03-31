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

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $sort, $direction, $search, $page): array {
            return $data ??= $this->buildIndex->handle(ctx: $ctx, period: $period, sort: $sort, direction: $direction, search: $search, page: $page);
        };

        return Inertia::render('analytics/cache-events/index', [
            'events_graph' => Inertia::defer(fn () => $resolve()['events_graph']),
            'failures_graph' => Inertia::defer(fn () => $resolve()['failures_graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
            'keys' => Inertia::defer(fn () => $resolve()['keys']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ]);
    }
}
