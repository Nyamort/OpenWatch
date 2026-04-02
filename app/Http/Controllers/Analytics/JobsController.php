<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\Job\BuildJobDetailData;
use App\Actions\Analytics\Job\BuildJobsIndexData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class JobsController extends AnalyticsController
{
    public function __construct(
        private readonly BuildJobsIndexData $buildIndex,
        private readonly BuildJobDetailData $buildDetail,
    ) {}

    /**
     * Display aggregated jobs analytics.
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

        return Inertia::render('analytics/jobs/index', [
            'graph' => Inertia::defer(fn () => $resolve()['graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
            'jobs' => Inertia::defer(fn () => $resolve()['jobs']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ]);
    }

    /**
     * Display charts and attempts for a single job class.
     */
    public function show(Request $request, string $organization, string $project, string $environment, string $job): Response
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

        return Inertia::render('analytics/jobs/show', [
            'graph' => Inertia::defer(fn () => $resolve()['graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
            'attempts' => Inertia::defer(fn () => $resolve()['attempts']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'name' => $name,
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }
}
