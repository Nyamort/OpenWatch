<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\Request\BuildRequestDetailData;
use App\Actions\Analytics\Request\BuildRequestIndexData;
use App\Actions\Analytics\Request\BuildRequestRouteData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RequestController extends AnalyticsController
{
    public function __construct(
        private readonly BuildRequestIndexData $buildIndex,
        private readonly BuildRequestRouteData $buildRoute,
        private readonly BuildRequestDetailData $buildDetail,
    ) {}

    /**
     * Display aggregated request analytics.
     */
    public function index(Request $request, string $organization, string $project, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $sort = (string) $request->query('sort', 'path');
        $direction = (string) $request->query('direction', 'asc');
        $search = (string) $request->query('search', '');
        $page = max(1, (int) $request->query('page', 1));

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $sort, $direction, $search, $page): array {
            return $data ??= $this->buildIndex->handle(ctx: $ctx, period: $period, sort: $sort, direction: $direction, search: $search, page: $page);
        };

        return Inertia::render('analytics/requests/index', [
            'graph' => Inertia::defer(fn () => $resolve()['graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
            'paths' => Inertia::defer(fn () => $resolve()['paths']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ]);
    }

    /**
     * Display analytics for a single route.
     */
    public function route(Request $request, string $organization, string $project, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $routePath = (string) $request->query('route_path', '');
        $method = (string) $request->query('method', '');
        $sort = (string) $request->query('sort', 'date');
        $direction = (string) $request->query('direction', 'desc');
        $page = max(1, (int) $request->query('page', 1));

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $routePath, $method, $sort, $direction, $page): array {
            return $data ??= $this->buildRoute->handle(ctx: $ctx, period: $period, routePath: $routePath, method: $method, sort: $sort, direction: $direction, page: $page);
        };

        return Inertia::render('analytics/requests/route', [
            'graph' => Inertia::defer(fn () => $resolve()['graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
            'requests' => Inertia::defer(fn () => $resolve()['requests']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'route_path' => $routePath,
            'method' => $method !== '' ? strtoupper($method) : null,
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Display detail for a single request.
     */
    public function show(Request $request, string $organization, string $project, string $environment, string $requestRecord): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);

        $data = $this->buildDetail->handle($ctx, $requestRecord);

        return Inertia::render('analytics/requests/show', [
            'analytics' => $data,
        ]);
    }
}
