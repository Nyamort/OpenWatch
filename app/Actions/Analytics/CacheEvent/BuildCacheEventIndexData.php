<?php

namespace App\Actions\Analytics\CacheEvent;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BuildCacheEventIndexData
{
    use PaginatesAnalyticsQuery;

    /**
     * Build graph buckets, global stats, and paginated cache key table.
     *
     * @return array<string, mixed>
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        string $sort = 'total',
        string $direction = 'desc',
        string $search = '',
        int $page = 1,
    ): array {
        $base = DB::table('extraction_cache_events')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end]);

        // Global stats
        $stats = (clone $base)->selectRaw("
            COUNT(*) as total,
            CAST(SUM(CASE WHEN type = 'hit' THEN 1 ELSE 0 END) AS UNSIGNED) as hits,
            CAST(SUM(CASE WHEN type = 'miss' THEN 1 ELSE 0 END) AS UNSIGNED) as misses,
            CAST(SUM(CASE WHEN type = 'write' THEN 1 ELSE 0 END) AS UNSIGNED) as writes,
            CAST(SUM(CASE WHEN type = 'delete' THEN 1 ELSE 0 END) AS UNSIGNED) as deletes,
            CAST(SUM(CASE WHEN type IN ('write-failure','delete-failure') THEN 1 ELSE 0 END) AS UNSIGNED) as failures,
            CAST(SUM(CASE WHEN type = 'write-failure' THEN 1 ELSE 0 END) AS UNSIGNED) as write_failures,
            CAST(SUM(CASE WHEN type = 'delete-failure' THEN 1 ELSE 0 END) AS UNSIGNED) as delete_failures
        ")->first();

        // Time-bucketed graph data
        $bucketSeconds = $period->bucketSeconds;
        $slotExpr = $this->bucketSlotExpression($bucketSeconds);
        $bucketMap = $this->fetchBuckets($base, $slotExpr)->keyBy('bucket_slot');

        $eventsGraph = [];
        $failuresGraph = [];
        $startSlot = (int) floor(Carbon::parse($period->start)->utc()->timestamp / $bucketSeconds);
        $endSlot = (int) floor(Carbon::parse($period->end)->utc()->timestamp / $bucketSeconds);

        for ($slot = $startSlot; $slot <= $endSlot; $slot++) {
            $row = $bucketMap->get($slot);
            $bucket = Carbon::createFromTimestampUTC($slot * $bucketSeconds)->format('Y-m-d H:i:s');
            $eventsGraph[] = [
                'bucket' => $bucket,
                'hits' => (int) ($row?->hits ?? 0),
                'misses' => (int) ($row?->misses ?? 0),
                'writes' => (int) ($row?->writes ?? 0),
                'deletes' => (int) ($row?->deletes ?? 0),
            ];
            $failuresGraph[] = [
                'bucket' => $bucket,
                'write_failures' => (int) ($row?->write_failures ?? 0),
                'delete_failures' => (int) ($row?->delete_failures ?? 0),
            ];
        }

        $keys = $this->fetchKeys($base, $sort, $direction, $search, $page);

        return [
            'events_graph' => $eventsGraph,
            'failures_graph' => $failuresGraph,
            'stats' => [
                'total' => (int) ($stats->total ?? 0),
                'hits' => (int) ($stats->hits ?? 0),
                'misses' => (int) ($stats->misses ?? 0),
                'writes' => (int) ($stats->writes ?? 0),
                'deletes' => (int) ($stats->deletes ?? 0),
                'failures' => (int) ($stats->failures ?? 0),
                'write_failures' => (int) ($stats->write_failures ?? 0),
                'delete_failures' => (int) ($stats->delete_failures ?? 0),
            ],
            'keys' => $keys['data'],
            'pagination' => $keys['pagination'],
        ];
    }

    /**
     * Fetch per-key aggregates.
     *
     * @return array<string, mixed>
     */
    private function fetchKeys(Builder $base, string $sort, string $direction, string $search, int $page): array
    {
        if ($search !== '') {
            $base = (clone $base)->where('key', 'like', '%'.$search.'%');
        }

        $allowedSorts = [
            'key' => '`key`',
            'hit_pct' => 'hit_pct',
            'hits' => 'hits',
            'misses' => 'misses',
            'writes' => 'writes',
            'deletes' => 'deletes',
            'failures' => 'failures',
            'total' => 'total',
        ];
        $orderCol = $this->resolveSort($sort, $allowedSorts, 'total');
        $orderDir = $direction === 'asc' ? 'asc' : 'desc';

        $totalKeys = (clone $base)->selectRaw('COUNT(DISTINCT `key`) as agg')->value('agg') ?? 0;
        $offset = $this->pageOffset($page);

        $rows = (clone $base)
            ->selectRaw("
                `key`,
                COUNT(*) AS total,
                CAST(SUM(CASE WHEN type = 'hit' THEN 1 ELSE 0 END) AS UNSIGNED) AS hits,
                CAST(SUM(CASE WHEN type = 'miss' THEN 1 ELSE 0 END) AS UNSIGNED) AS misses,
                CAST(SUM(CASE WHEN type = 'write' THEN 1 ELSE 0 END) AS UNSIGNED) AS writes,
                CAST(SUM(CASE WHEN type = 'delete' THEN 1 ELSE 0 END) AS UNSIGNED) AS deletes,
                CAST(SUM(CASE WHEN type IN ('write-failure','delete-failure') THEN 1 ELSE 0 END) AS UNSIGNED) AS failures,
                CAST(ROUND(
                    SUM(CASE WHEN type = 'hit' THEN 1 ELSE 0 END) * 100.0
                    / NULLIF(SUM(CASE WHEN type IN ('hit','miss') THEN 1 ELSE 0 END), 0)
                , 1) AS DOUBLE) AS hit_pct
            ")
            ->groupByRaw('`key`')
            ->orderByRaw("{$orderCol} {$orderDir}")
            ->limit($this->analyticsPerPage)
            ->offset($offset)
            ->get();

        $data = $rows->map(fn ($row) => [
            'key' => $row->key,
            'hit_pct' => $row->hit_pct,
            'hits' => (int) $row->hits,
            'misses' => (int) $row->misses,
            'writes' => (int) $row->writes,
            'deletes' => (int) $row->deletes,
            'failures' => (int) $row->failures,
            'total' => (int) $row->total,
        ])->all();

        return [
            'data' => $data,
            'pagination' => $this->buildPaginationMeta($totalKeys, $page),
        ];
    }

    private function bucketSlotExpression(int $bucketSeconds): string
    {
        $epoch = match (DB::getDriverName()) {
            'pgsql' => 'EXTRACT(EPOCH FROM recorded_at)',
            'sqlite' => 'CAST(strftime(\'%s\', recorded_at) AS INTEGER)',
            default => 'UNIX_TIMESTAMP(recorded_at)',
        };

        return "FLOOR({$epoch} / {$bucketSeconds})";
    }

    private function fetchBuckets(Builder $base, string $slotExpr): \Illuminate\Support\Collection
    {
        return (clone $base)
            ->selectRaw("
                {$slotExpr} AS bucket_slot,
                CAST(SUM(CASE WHEN type = 'hit' THEN 1 ELSE 0 END) AS UNSIGNED) AS hits,
                CAST(SUM(CASE WHEN type = 'miss' THEN 1 ELSE 0 END) AS UNSIGNED) AS misses,
                CAST(SUM(CASE WHEN type = 'write' THEN 1 ELSE 0 END) AS UNSIGNED) AS writes,
                CAST(SUM(CASE WHEN type = 'delete' THEN 1 ELSE 0 END) AS UNSIGNED) AS deletes,
                CAST(SUM(CASE WHEN type = 'write-failure' THEN 1 ELSE 0 END) AS UNSIGNED) AS write_failures,
                CAST(SUM(CASE WHEN type = 'delete-failure' THEN 1 ELSE 0 END) AS UNSIGNED) AS delete_failures
            ")
            ->groupByRaw($slotExpr)
            ->orderByRaw($slotExpr)
            ->get();
    }
}
