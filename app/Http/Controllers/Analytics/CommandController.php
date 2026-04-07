<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\Command\BuildCommandIndexData;
use App\Actions\Analytics\Command\BuildCommandShowData;
use App\Actions\Analytics\Command\BuildCommandTypeData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommandController extends AnalyticsController
{
    public function __construct(
        private readonly BuildCommandIndexData $buildIndex,
        private readonly BuildCommandTypeData $buildDetail,
        private readonly BuildCommandShowData $buildShow,
    ) {}

    /**
     * Display aggregated command analytics.
     */
    public function index(Request $request, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $environment);
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
     * Display runs for a specific command name.
     */
    public function type(Request $request, string $environment, string $command): Response
    {
        $ctx = $this->resolveContext($request, $environment);
        $period = $this->buildPeriod($request);

        $name = (string) $request->query('name', '');
        $sort = (string) $request->query('sort', 'date');
        $direction = (string) $request->query('direction', 'desc');
        $page = max(1, (int) $request->query('page', 1));

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $name, $sort, $direction, $page): array {
            return $data ??= $this->buildDetail->handle(ctx: $ctx, period: $period, name: $name, sort: $sort, direction: $direction, page: $page);
        };

        return Inertia::render('analytics/commands/type', [
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

    /**
     * Display detail for a single command run.
     */
    public function show(Request $request, string $environment, string $command, string $run): Response
    {
        $ctx = $this->resolveContext($request, $environment);
        $data = $this->buildShow->handle($ctx, $run);

        return Inertia::render('analytics/commands/show', [
            'analytics' => $data,
        ]);
    }
}
