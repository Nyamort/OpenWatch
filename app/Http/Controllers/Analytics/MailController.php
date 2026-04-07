<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\Mail\BuildMailIndexData;
use App\Actions\Analytics\Mail\BuildMailTypeData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MailController extends AnalyticsController
{
    public function __construct(
        private readonly BuildMailIndexData $buildIndex,
        private readonly BuildMailTypeData $buildType,
    ) {}

    /**
     * Display aggregated mail analytics.
     */
    public function index(Request $request, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $environment);
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
     * Display aggregated analytics for a specific mail class.
     */
    public function type(Request $request, string $environment, string $mail): Response
    {
        $ctx = $this->resolveContext($request, $environment);
        $period = $this->buildPeriod($request);

        $mailClass = (string) $request->query('class', '');
        $sort = (string) $request->query('sort', 'date');
        $direction = (string) $request->query('direction', 'desc');
        $page = max(1, (int) $request->query('page', 1));

        $data = null;
        $resolve = function () use (&$data, $ctx, $period, $mailClass, $sort, $direction, $page): array {
            return $data ??= $this->buildType->handle($ctx, $period, $mailClass, $sort, $direction, $page);
        };

        return Inertia::render('analytics/mail/type', [
            'graph' => Inertia::defer(fn () => $resolve()['graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
            'runs' => Inertia::defer(fn () => $resolve()['runs']),
            'pagination' => Inertia::defer(fn () => $resolve()['pagination']),
            'mailClass' => $mailClass,
            'period' => $request->query('period', '24h'),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }
}
