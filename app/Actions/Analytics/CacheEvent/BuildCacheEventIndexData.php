<?php

namespace App\Actions\Analytics\CacheEvent;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\DB;

class BuildCacheEventIndexData
{
    /**
     * Build cache event analytics grouped by (store, key).
     *
     * @return array<string, mixed>
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        ?string $store = null,
        ?string $keySearch = null,
    ): array {
        $query = DB::table('extraction_cache_events')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->select([
                'store',
                'key',
                DB::raw('COUNT(*) as total_ops'),
                DB::raw("CAST(SUM(CASE WHEN type = 'hit' THEN 1 ELSE 0 END) AS UNSIGNED) as hit_count"),
                DB::raw("CAST(SUM(CASE WHEN type = 'miss' THEN 1 ELSE 0 END) AS UNSIGNED) as miss_count"),
                DB::raw("CAST(SUM(CASE WHEN type = 'write' THEN 1 ELSE 0 END) AS UNSIGNED) as write_count"),
                DB::raw("CAST(SUM(CASE WHEN type = 'forget' THEN 1 ELSE 0 END) AS UNSIGNED) as forget_count"),
                DB::raw("CAST(SUM(CASE WHEN type = 'flush' THEN 1 ELSE 0 END) AS UNSIGNED) as flush_count"),
                DB::raw("ROUND((SUM(CASE WHEN type = 'hit' THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0)) , 2) as hit_rate_pct"),
            ])
            ->groupBy('store', 'key')
            ->orderBy('total_ops', 'desc');

        if ($store !== null && $store !== '') {
            $query->where('store', $store);
        }

        if ($keySearch !== null && $keySearch !== '') {
            $query->where('key', 'like', '%'.$keySearch.'%');
        }

        $rows = $query->paginate(50);

        $enriched = array_map(function (object $row): array {
            $hitRate = (float) $row->hit_rate_pct;
            $color = match (true) {
                $hitRate >= 80 => 'green',
                $hitRate >= 50 => 'yellow',
                default => 'red',
            };

            return array_merge((array) $row, ['hit_rate_pct' => $hitRate, 'hit_rate_color' => $color]);
        }, $rows->items());

        return (new AnalyticsResponseBuilder)
            ->withSummary(['period_label' => $period->label])
            ->withRows($enriched)
            ->withPagination([
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ])
            ->withFiltersApplied([
                'store' => $store,
                'key_search' => $keySearch,
            ])
            ->withConfig(['period' => $period->label])
            ->build();
    }
}
