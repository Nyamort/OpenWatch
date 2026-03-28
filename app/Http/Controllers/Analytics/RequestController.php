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
        $direction = (string) $request->query('direction', 'desc');

        $data = $this->buildIndex->handle(ctx: $ctx, period: $period, sort: $sort, direction: $direction);

        return Inertia::render('analytics/requests/index', [
            'graph' => $data['graph'],
            'stats' => $data['stats'],
            'paths' => $data['paths'],
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    /**
     * Display analytics for a single route.
     */
    public function route(Request $request, string $organization, string $project, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $data = $this->buildRoute->handle(
            ctx: $ctx,
            period: $period,
            routePath: (string) $request->query('route_path', ''),
            method: (string) $request->query('method', 'GET'),
        );

        return Inertia::render('analytics/requests/route', [
            'analytics' => $data,
            'period' => $request->query('period', '24h'),
        ]);
    }

    /**
     * Display detail for a single request.
     */
    public function show(Request $request, string $organization, string $project, string $environment, int $requestRecord): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);

        $data = $this->buildDetail->handle($ctx, $requestRecord);

        return Inertia::render('analytics/requests/show', [
            'analytics' => $data,
        ]);
    }
}
