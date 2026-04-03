<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\User\BuildUserDetailData;
use App\Actions\Analytics\User\BuildUserIndexData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserAnalyticsController extends AnalyticsController
{
    public function __construct(
        private readonly BuildUserIndexData $buildIndex,
        private readonly BuildUserDetailData $buildDetail,
    ) {}

    /**
     * Display aggregated user analytics.
     */
    public function index(Request $request, string $organization, string $project, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $sort = (string) $request->query('sort', 'request_count');
        $direction = (string) $request->query('direction', 'desc');
        $search = (string) $request->query('search', '');
        $page = max(1, (int) $request->query('page', 1));

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $sort, $direction, $search, $page): array {
            return $data ??= $this->buildIndex->handle(ctx: $ctx, period: $period, sort: $sort, direction: $direction, search: $search, page: $page);
        };

        return Inertia::render('analytics/users/index', [
            'graph' => Inertia::defer(fn () => $resolve()['graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
            'users' => Inertia::defer(fn () => $resolve()['users']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ]);
    }

    /**
     * Display detail view for a specific user.
     */
    public function show(Request $request, string $organization, string $project, string $environment, string $user): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $data = $this->buildDetail->handle($ctx, $period, $user);

        return Inertia::render('analytics/users/show', [
            'analytics' => $data,
            'user_value' => $user,
            'period' => $request->query('period', '24h'),
        ]);
    }
}
