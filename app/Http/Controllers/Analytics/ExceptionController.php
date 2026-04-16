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
    public function index(Request $request, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $environment);
        $period = $this->buildPeriod($request);

        $sort = (string) $request->query('sort', 'last_seen');
        $direction = (string) $request->query('direction', 'desc');
        $search = (string) $request->query('search', '');
        $page = max(1, (int) $request->query('page', 1));

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $sort, $direction, $search, $page): array {
            return $data ??= $this->buildIndex->handle(ctx: $ctx, period: $period, sort: $sort, direction: $direction, search: $search, page: $page);
        };

        return Inertia::render('analytics/exceptions/index', [
            'graph' => Inertia::defer(fn () => $resolve()['graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
            'exceptions' => Inertia::defer(fn () => $resolve()['exceptions']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ]);
    }

    /**
     * Display detail for a specific exception group.
     */
    public function show(Request $request, string $environment, string $group): Response
    {
        $ctx = $this->resolveContext($request, $environment);
        $period = $this->buildPeriod($request);

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $group): array {
            return $data ??= $this->buildDetail->handle($ctx, $period, $group);
        };

        return Inertia::render('analytics/exceptions/show', [
            'summary' => Inertia::defer(fn () => $resolve()['summary']),
            'rows' => Inertia::defer(fn () => $resolve()['rows']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'graph' => Inertia::defer(fn () => $resolve()['graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
            'period' => $request->query('period', '24h'),
        ]);
    }
}
