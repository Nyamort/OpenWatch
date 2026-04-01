<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\Command\BuildCommandDetailData;
use App\Actions\Analytics\Command\BuildCommandIndexData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommandController extends AnalyticsController
{
    public function __construct(
        private readonly BuildCommandIndexData $buildIndex,
        private readonly BuildCommandDetailData $buildDetail,
    ) {}

    /**
     * Display aggregated command analytics.
     */
    public function index(Request $request, string $organization, string $project, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $sort = (string) $request->query('sort', 'name');
        $direction = (string) $request->query('direction', 'asc');
        $search = (string) $request->query('search', '');
        $page = max(1, (int) $request->query('page', 1));

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $sort, $direction, $search, $page): array {
            return $data ??= $this->buildIndex->handle(ctx: $ctx, period: $period, sort: $sort, direction: $direction, search: $search, page: $page);
        };

        return Inertia::render('analytics/commands/index', [
            'graph' => Inertia::defer(fn () => $resolve()['graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
            'commands' => Inertia::defer(fn () => $resolve()['commands']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ]);
    }

    /**
     * Display detail analytics for a command name.
     */
    public function show(Request $request, string $organization, string $project, string $environment, int $command): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $name = (string) $request->query('name', '');
        $sort = (string) $request->query('sort', 'date');
        $direction = (string) $request->query('direction', 'desc');
        $page = max(1, (int) $request->query('page', 1));

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $name, $sort, $direction, $page): array {
            return $data ??= $this->buildDetail->handle(ctx: $ctx, period: $period, name: $name, sort: $sort, direction: $direction, page: $page);
        };

        return Inertia::render('analytics/commands/show', [
            'graph' => Inertia::defer(fn () => $resolve()['graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
            'runs' => Inertia::defer(fn () => $resolve()['runs']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'name' => $name,
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }
}
