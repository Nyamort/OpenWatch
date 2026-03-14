<?php

namespace App\Http\Controllers\Analytics;

use App\Actions\Analytics\OutgoingRequest\BuildOutgoingRequestHostData;
use App\Actions\Analytics\OutgoingRequest\BuildOutgoingRequestIndexData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OutgoingRequestController extends AnalyticsController
{
    public function __construct(
        private readonly BuildOutgoingRequestIndexData $buildIndex,
        private readonly BuildOutgoingRequestHostData $buildHost,
    ) {}

    /**
     * Display aggregated outgoing request analytics by host.
     */
    public function index(Request $request, string $organization, string $project, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $data = $this->buildIndex->handle($ctx, $period);

        return Inertia::render('analytics/outgoing-requests/index', [
            'analytics' => $data,
            'period' => $request->query('period', '24h'),
        ]);
    }

    /**
     * Display individual requests for a specific host.
     */
    public function host(Request $request, string $organization, string $project, string $environment): Response
    {
        $ctx = $this->resolveContext($request, $organization, $project, $environment);
        $period = $this->buildPeriod($request);

        $data = $this->buildHost->handle(
            ctx: $ctx,
            period: $period,
            host: (string) $request->query('host', ''),
        );

        return Inertia::render('analytics/outgoing-requests/host', [
            'analytics' => $data,
            'period' => $request->query('period', '24h'),
        ]);
    }
}
