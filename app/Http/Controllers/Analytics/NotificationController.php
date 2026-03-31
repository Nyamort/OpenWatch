<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\Notification\BuildNotificationDetailData;
use App\Actions\Analytics\Notification\BuildNotificationIndexData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends AnalyticsController
{
    public function __construct(
        private readonly BuildNotificationIndexData $buildIndex,
        private readonly BuildNotificationDetailData $buildDetail,
    ) {}

    /**
     * Display aggregated notification analytics.
     */
    public function index(Request $request, string $organization, string $project, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $sort = (string) $request->query('sort', 'count');
        $direction = (string) $request->query('direction', 'desc');
        $search = (string) $request->query('search', '');
        $page = max(1, (int) $request->query('page', 1));

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $sort, $direction, $search, $page): array {
            return $data ??= $this->buildIndex->handle(ctx: $ctx, period: $period, sort: $sort, direction: $direction, search: $search, page: $page);
        };

        return Inertia::render('analytics/notifications/index', [
            'graph' => Inertia::defer(fn () => $resolve()['graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
            'notifications' => Inertia::defer(fn () => $resolve()['notifications']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ]);
    }

    /**
     * Display a single notification record.
     */
    public function show(Request $request, string $organization, string $project, string $environment, int $notification): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);

        $data = $this->buildDetail->handle($ctx, $notification);

        return Inertia::render('analytics/notifications/show', [
            'analytics' => $data,
        ]);
    }
}
