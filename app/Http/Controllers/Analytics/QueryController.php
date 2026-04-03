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
    public function index(Request $request, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $environment);
        $period = $this->buildPeriod($request);

        $sort = (string) $request->query('sort', 'query');
        $direction = (string) $request->query('direction', 'asc');
        $search = (string) $request->query('search', '');
        $page = max(1, (int) $request->query('page', 1));

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $sort, $direction, $search, $page): array {
            return $data ??= $this->buildIndex->handle(
                ctx: $ctx,
                period: $period,
                sort: $sort,
                direction: $direction,
                search: $search,
                page: $page,
            );
        };

        return Inertia::render('analytics/queries/index', [
            'graph' => Inertia::defer(fn () => $resolve()['graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
            'queries' => Inertia::defer(fn () => $resolve()['queries']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ]);
    }

    /**
     * Display detail for a specific sql_hash.
     */
    public function show(Request $request, string $environment, string $query): Response
    {
        $ctx = $this->resolveContext($request, $environment);
        $period = $this->buildPeriod($request);

        $data = $this->buildDetail->handle($ctx, $period, $query);

        return Inertia::render('analytics/queries/show', [
            'analytics' => $data,
            'period' => $request->query('period', '24h'),
        ]);
    }
}
