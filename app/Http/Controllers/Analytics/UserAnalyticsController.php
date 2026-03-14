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

        $data = $this->buildIndex->handle($ctx, $period);

        return Inertia::render('analytics/users/index', [
            'analytics' => $data,
            'period' => $request->query('period', '24h'),
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
