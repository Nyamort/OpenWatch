<?php

namespace App\Actions\Analytics\Log;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\DB;

class BuildLogIndexData
{
    /** @var list<string> RFC 5424 log levels in severity order */
    public const LEVELS = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];

    /**
     * Build a cursor-based log feed ordered newest-first with optional filters.
     *
     * @return array<string, mixed>
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        ?string $level = null,
        ?string $search = null,
    ): array {
        $query = DB::table('extraction_logs')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->orderBy('recorded_at', 'desc');

        if ($level !== null && in_array($level, self::LEVELS, true)) {
            $query->where('level', $level);
        }

        if ($search !== null && $search !== '') {
            $query->where('message', 'like', '%'.$search.'%');
        }

        $rows = $query->paginate(100);

        return (new AnalyticsResponseBuilder)
            ->withSummary(['period_label' => $period->label])
            ->withRows($rows->items())
            ->withPagination([
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ])
            ->withFiltersApplied([
                'level' => $level,
                'search' => $search,
            ])
            ->withConfig([
                'levels' => self::LEVELS,
                'period' => $period->label,
            ])
            ->build();
    }
}
