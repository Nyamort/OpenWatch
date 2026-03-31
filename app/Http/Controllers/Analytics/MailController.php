<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\Mail\BuildMailDetailData;
use App\Actions\Analytics\Mail\BuildMailIndexData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MailController extends AnalyticsController
{
    public function __construct(
        private readonly BuildMailIndexData $buildIndex,
        private readonly BuildMailDetailData $buildDetail,
    ) {}

    /**
     * Display aggregated mail analytics.
     */
    public function index(Request $request, string $organization, string $project, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $sort = (string) $request->query('sort', 'mail');
        $direction = (string) $request->query('direction', 'asc');
        $search = (string) $request->query('search', '');
        $page = max(1, (int) $request->query('page', 1));

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $sort, $direction, $search, $page): array {
            return $data ??= $this->buildIndex->handle(ctx: $ctx, period: $period, sort: $sort, direction: $direction, search: $search, page: $page);
        };

        return Inertia::render('analytics/mail/index', [
            'graph' => Inertia::defer(fn () => $resolve()['graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
            'mails' => Inertia::defer(fn () => $resolve()['mails']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
            'search' => $search,
        ]);
    }

    /**
     * Display a single mail record.
     */
    public function show(Request $request, string $organization, string $project, string $environment, int $mail): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);

        $data = $this->buildDetail->handle($ctx, $mail);

        return Inertia::render('analytics/mail/show', [
            'analytics' => $data,
        ]);
    }
}
