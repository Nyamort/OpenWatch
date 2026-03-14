<?php

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsContextResolver;
use App\Services\Analytics\PeriodResult;
use App\Services\Analytics\PeriodService;
use Illuminate\Http\Request;

abstract class AnalyticsController extends Controller
{
    /**
     * Resolve the analytics context from the request route parameters.
     */
    protected function resolveContext(Request $request, string $org, string $project, string $env): AnalyticsContext
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        return app(AnalyticsContextResolver::class)->resolve($org, $project, $env, $user);
    }

    /**
     * Build the period from the request's `period` query parameter.
     */
    protected function buildPeriod(Request $request): PeriodResult
    {
        $period = $request->query('period', '24h');

        return app(PeriodService::class)->parse((string) $period);
    }
}
