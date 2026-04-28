<?php

namespace App\Http\Controllers;

use App\Actions\Analytics\Request\BuildRequestIndexData;
use App\Services\Analytics\AnalyticsContextResolver;
use App\Services\Analytics\PeriodService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly BuildRequestIndexData $buildRequestData,
        private readonly AnalyticsContextResolver $contextResolver,
        private readonly PeriodService $periodService,
    ) {}

    public function __invoke(Request $request): Response
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        $envSlug = $request->query('env');
        $periodStr = (string) $request->query('period', '24h');

        if (! $envSlug) {
            $activeOrg = $user->activeOrganization;
            $firstProject = $activeOrg?->projects()->first();
            $firstEnv = $firstProject?->environments()->first();

            if (! $firstEnv) {
                return Inertia::render('dashboard', [
                    'hasContext' => false,
                    'period' => $periodStr,
                ]);
            }

            $envSlug = $firstEnv->slug;
        }

        try {
            $ctx = $this->contextResolver->resolve($envSlug, $user);
            $period = $this->periodService->parse($periodStr);
        } catch (\Throwable) {
            return Inertia::render('dashboard', [
                'hasContext' => false,
                'period' => $periodStr,
            ]);
        }

        $data = null;
        $resolve = function () use (&$data, $ctx, $period): array {
            return $data ??= $this->buildRequestData->handle(ctx: $ctx, period: $period);
        };

        return Inertia::render('dashboard', [
            'hasContext' => true,
            'period' => $periodStr,
            'context' => [
                'org' => $ctx->organization->slug,
                'project' => $ctx->project->slug,
                'env' => $ctx->environment->slug,
            ],
            'graph' => Inertia::defer(fn () => $resolve()['graph']),
            'stats' => Inertia::defer(fn () => $resolve()['stats']),
        ]);
    }
}
