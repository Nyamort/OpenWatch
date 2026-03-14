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

        $data = $this->buildIndex->handle($ctx, $period);

        return Inertia::render('analytics/mail/index', [
            'analytics' => $data,
            'period' => $request->query('period', '24h'),
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
