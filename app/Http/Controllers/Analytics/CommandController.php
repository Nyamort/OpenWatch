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

        $sort = (string) $request->query('sort', 'total');
        $direction = (string) $request->query('direction', 'desc');
        $search = (string) $request->query('search', '');
        $page = max(1, (int) $request->query('page', 1));

        $data = $this->buildIndex->handle(ctx: $ctx, period: $period, sort: $sort, direction: $direction, search: $search, page: $page);

        return Inertia::render('analytics/commands/index', [
            'graph' => $data['graph'],
            'stats' => $data['stats'],
            'commands' => $data['commands'],
            'pagination' => $data['pagination'],
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ]);
    }

    /**
     * Display a single command run.
     */
    public function show(Request $request, string $organization, string $project, string $environment, int $command): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);

        $data = $this->buildDetail->handle($ctx, $command);

        return Inertia::render('analytics/commands/show', [
            'analytics' => $data,
        ]);
    }
}
