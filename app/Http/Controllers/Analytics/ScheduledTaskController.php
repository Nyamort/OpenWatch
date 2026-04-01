<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\ScheduledTask\BuildScheduledTaskIndexData;
use App\Actions\Analytics\ScheduledTask\BuildScheduledTaskRunData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduledTaskController extends AnalyticsController
{
    public function __construct(
        private readonly BuildScheduledTaskIndexData $buildIndex,
        private readonly BuildScheduledTaskRunData $buildRuns,
    ) {}

    /**
     * Display aggregated scheduled task analytics.
     */
    public function index(Request $request, string $organization, string $project, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $sort = (string) $request->query('sort', 'task');
        $direction = (string) $request->query('direction', 'asc');
        $search = (string) $request->query('search', '');
        $page = max(1, (int) $request->query('page', 1));

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $sort, $direction, $search, $page): array {
            return $data ??= $this->buildIndex->handle(ctx: $ctx, period: $period, sort: $sort, direction: $direction, search: $search, page: $page);
        };

        return Inertia::render('analytics/scheduled-tasks/index', [
            'graph' => Inertia::defer(fn () => $resolve()['graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
            'tasks' => Inertia::defer(fn () => $resolve()['tasks']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ]);
    }

    /**
     * Display detail analytics for a scheduled task (name + cron).
     */
    public function show(Request $request, string $organization, string $project, string $environment, string $scheduledTask): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $name = (string) $request->query('name', '');
        $cron = (string) $request->query('cron', '');
        $sort = (string) $request->query('sort', 'date');
        $direction = (string) $request->query('direction', 'desc');
        $page = max(1, (int) $request->query('page', 1));

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $name, $cron, $sort, $direction, $page): array {
            return $data ??= $this->buildRuns->handle(ctx: $ctx, period: $period, name: $name, cron: $cron, sort: $sort, direction: $direction, page: $page);
        };

        return Inertia::render('analytics/scheduled-tasks/show', [
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
