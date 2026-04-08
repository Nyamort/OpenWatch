<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\Log\BuildLogDetailData;
use App\Actions\Analytics\Log\BuildLogIndexData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends AnalyticsController
{
    public function __construct(
        private readonly BuildLogIndexData $buildIndex,
        private readonly BuildLogDetailData $buildDetail,
    ) {}

    /**
     * Display the log feed.
     */
    public function index(Request $request, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $environment);
        $period = $this->buildPeriod($request);

        $level = $request->query('level');
        $search = (string) $request->query('search', '');
        $page = max(1, (int) $request->query('page', 1));

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $level, $search, $page): array {
            return $data ??= $this->buildIndex->handle(ctx: $ctx, period: $period, level: $level, search: $search, page: $page);
        };

        return Inertia::render('analytics/logs/index', [
            'logs' => Inertia::defer(fn () => $resolve()['logs']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'period' => $request->query('period', '24h'),
            'search' => $search,
            'level' => $level,
        ]);
    }

    /**
     * Display a single log entry.
     */
    public function show(Request $request, string $environment, string $log): Response
    {
        $ctx = $this->resolveContext($request, $environment);

        $data = $this->buildDetail->handle($ctx, $log);

        return Inertia::render('analytics/logs/show', [
            'analytics' => $data,
        ]);
    }
}
