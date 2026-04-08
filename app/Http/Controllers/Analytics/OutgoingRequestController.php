<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\OutgoingRequest\BuildOutgoingRequestHostData;
use App\Actions\Analytics\OutgoingRequest\BuildOutgoingRequestIndexData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OutgoingRequestController extends AnalyticsController
{
    public function __construct(
        private readonly BuildOutgoingRequestIndexData $buildIndex,
        private readonly BuildOutgoingRequestHostData $buildHost,
    ) {}

    /**
     * Display aggregated outgoing request analytics by host.
     */
    public function index(Request $request, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $environment);
        $period = $this->buildPeriod($request);

        $sort = (string) $request->query('sort', 'host');
        $direction = (string) $request->query('direction', 'asc');
        $search = (string) $request->query('search', '');
        $page = max(1, (int) $request->query('page', 1));

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $sort, $direction, $search, $page): array {
            return $data ??= $this->buildIndex->handle(ctx: $ctx, period: $period, sort: $sort, direction: $direction, search: $search, page: $page);
        };

        return Inertia::render('analytics/outgoing-requests/index', [
            'graph' => Inertia::defer(fn () => $resolve()['graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
            'hosts' => Inertia::defer(fn () => $resolve()['hosts']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ]);
    }

    /**
     * Display individual requests for a specific host.
     */
    public function host(Request $request, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $environment);
        $period = $this->buildPeriod($request);

        $host = (string) $request->query('host', '');
        $sort = (string) $request->query('sort', 'date');
        $direction = (string) $request->query('direction', 'desc');
        $page = max(1, (int) $request->query('page', 1));

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $host, $sort, $direction, $page): array {
            return $data ??= $this->buildHost->handle(ctx: $ctx, period: $period, host: $host, sort: $sort, direction: $direction, page: $page);
        };

        return Inertia::render('analytics/outgoing-requests/host', [
            'graph' => Inertia::defer(fn () => $resolve()['graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
            'runs' => Inertia::defer(fn () => $resolve()['runs']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'host' => $host,
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }
}
