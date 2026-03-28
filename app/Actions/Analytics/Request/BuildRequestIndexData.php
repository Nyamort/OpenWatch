<?php

namespace App\Actions\Analytics\Request;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuildRequestIndexData
{
    /**
     * Build graph buckets and global stats for request analytics.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $sort = 'total', string $direction = 'desc', string $search = ''): array
    {
        $base = DB::table('extraction_requests')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end]);

        // Global stats
        $stats = (clone $base)->selectRaw('
            COUNT(*) as count,
            SUM(CASE WHEN status_code < 400 THEN 1 ELSE 0 END) as `2xx`,
            SUM(CASE WHEN status_code >= 400 AND status_code < 500 THEN 1 ELSE 0 END) as `4xx`,
            SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) as `5xx`,
            CAST(MIN(duration) AS DOUBLE) as min,
            CAST(MAX(duration) AS DOUBLE) as max,
            CAST(ROUND(AVG(duration), 2) AS DOUBLE) as avg
        ')->first();

        $totalCount = (int) ($stats->count ?? 0);
        $globalP95 = null;

        if ($totalCount > 0) {
            $p95Offset = max(0, (int) ceil($totalCount * 0.95) - 1);
            $globalP95 = (clone $base)->orderBy('duration')->skip($p95Offset)->limit(1)->value('duration');
        }

        // Time-bucketed graph data
        $bucketSeconds = $period->bucketSeconds;
        $slotExpr = $this->bucketSlotExpression($bucketSeconds);
        $bucketMap = $this->fetchBuckets($base, $slotExpr)->keyBy('bucket_slot');

        $graph = [];
        $startSlot = (int) floor(Carbon::parse($period->start)->utc()->timestamp / $bucketSeconds);
        $endSlot = (int) floor(Carbon::parse($period->end)->utc()->timestamp / $bucketSeconds);

        for ($slot = $startSlot; $slot <= $endSlot; $slot++) {
            $row = $bucketMap->get($slot);
            $graph[] = [
                'bucket' => Carbon::createFromTimestampUTC($slot * $bucketSeconds)->format('Y-m-d H:i:s'),
                'count' => (int) ($row?->count ?? 0),
                '2xx' => (int) ($row?->{'2xx'} ?? 0),
                '4xx' => (int) ($row?->{'4xx'} ?? 0),
                '5xx' => (int) ($row?->{'5xx'} ?? 0),
                'min' => $row?->min,
                'max' => $row?->max,
                'avg' => $row?->avg,
                'p95' => $row?->p95,
            ];
        }

        $paths = $this->fetchPaths($base, $sort, $direction, $search);

        return [
            'graph' => $graph,
            'paths' => $paths,
            'stats' => [
                'count' => $totalCount,
                '2xx' => (int) ($stats?->{'2xx'} ?? 0),
                '4xx' => (int) ($stats?->{'4xx'} ?? 0),
                '5xx' => (int) ($stats?->{'5xx'} ?? 0),
                'min' => $stats->min ?? null,
                'max' => $stats->max ?? null,
                'avg' => $stats->avg ?? null,
                'p95' => $globalP95 ? (float) $globalP95 : null,
            ],
        ];
    }

    /**
     * Fetch per-path aggregates grouped by route_path, ordered by total desc.
     *
     * @return array<int, array<string, mixed>>
     */
    private function fetchPaths(Builder $base, string $sort = 'total', string $direction = 'desc', string $search = ''): array
    {
        if ($search !== '') {
            $base = (clone $base)->where('route_path', 'like', '%'.$search.'%');
        }

        $allowedSorts = ['method' => 'methods', 'path' => 'route_path', 'total' => 'total', '2xx' => '2xx', '4xx' => '4xx', '5xx' => '5xx', 'avg' => 'avg', 'p95' => 'p95'];
        $orderCol = $allowedSorts[$sort] ?? 'total';
        $orderDir = $direction === 'asc' ? 'asc' : 'desc';

        $aggregates = '
            COUNT(*) AS total,
            SUM(CASE WHEN status_code < 400 THEN 1 ELSE 0 END) AS `2xx`,
            SUM(CASE WHEN status_code >= 400 AND status_code < 500 THEN 1 ELSE 0 END) AS `4xx`,
            SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) AS `5xx`,
            CAST(ROUND(AVG(duration), 2) AS DOUBLE) AS avg,
            MAX(route_methods) AS methods
        ';

        if (DB::getDriverName() === 'sqlite') {
            $rows = (clone $base)
                ->selectRaw("route_path, {$aggregates}, NULL AS p95")
                ->groupByRaw('route_path')
                ->orderByRaw("{$orderCol} {$orderDir}")
                ->get();
        } else {
            $inner = (clone $base)->select([
                'route_path',
                'route_methods',
                'status_code',
                'duration',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY route_path ORDER BY duration) AS row_num'),
                DB::raw('COUNT(*) OVER (PARTITION BY route_path) AS path_count'),
            ]);

            $rows = DB::query()
                ->fromSub($inner, 'ranked')
                ->selectRaw("route_path, {$aggregates}, CAST(MAX(CASE WHEN row_num >= CEIL(0.95 * path_count) THEN duration END) AS DOUBLE) AS p95")
                ->groupByRaw('route_path')
                ->orderByRaw("{$orderCol} {$orderDir}")
                ->get();
        }

        return $rows->map(fn ($row) => [
            'methods' => array_values(array_filter(explode('|', $row->methods ?? ''))),
            'path' => $row->route_path ?: null,
            '2xx' => (int) ($row->{'2xx'} ?? 0),
            '4xx' => (int) ($row->{'4xx'} ?? 0),
            '5xx' => (int) ($row->{'5xx'} ?? 0),
            'total' => (int) $row->total,
            'avg' => $row->avg,
            'p95' => $row->p95,
        ])->all();
    }

    /**
     * Driver-aware SQL expression for the integer bucket slot from recorded_at.
     */
    private function bucketSlotExpression(int $bucketSeconds): string
    {
        $epoch = match (DB::getDriverName()) {
            'pgsql' => 'EXTRACT(EPOCH FROM recorded_at)',
            'sqlite' => 'CAST(strftime(\'%s\', recorded_at) AS INTEGER)',
            default => 'UNIX_TIMESTAMP(recorded_at)',
        };

        return "FLOOR({$epoch} / {$bucketSeconds})";
    }

    /**
     * Fetch per-bucket aggregates.
     * Uses window functions for p95 on MySQL/PostgreSQL; returns NULL on SQLite.
     */
    private function fetchBuckets(Builder $base, string $slotExpr): Collection
    {
        $aggregates = '
            COUNT(*) AS count,
            SUM(CASE WHEN status_code < 400 THEN 1 ELSE 0 END) AS `2xx`,
            SUM(CASE WHEN status_code >= 400 AND status_code < 500 THEN 1 ELSE 0 END) AS `4xx`,
            SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) AS `5xx`,
            CAST(MIN(duration) AS DOUBLE) AS min,
            CAST(MAX(duration) AS DOUBLE) AS max,
            CAST(ROUND(AVG(duration), 2) AS DOUBLE) AS avg
        ';

        // SQLite does not support window functions (tests only) — p95 per bucket is skipped
        if (DB::getDriverName() === 'sqlite') {
            return (clone $base)
                ->selectRaw("{$slotExpr} AS bucket_slot, {$aggregates}, NULL AS p95")
                ->groupByRaw($slotExpr)
                ->orderByRaw($slotExpr)
                ->get();
        }

        // MySQL & PostgreSQL: compute p95 per bucket via window functions in a subquery
        $inner = (clone $base)->select([
            'status_code',
            'duration',
            DB::raw("{$slotExpr} AS bucket_slot"),
            DB::raw("ROW_NUMBER() OVER (PARTITION BY {$slotExpr} ORDER BY duration) AS row_num"),
            DB::raw("COUNT(*) OVER (PARTITION BY {$slotExpr}) AS bucket_count"),
        ]);

        return DB::query()
            ->fromSub($inner, 'ranked')
            ->selectRaw("bucket_slot, {$aggregates}, CAST(MAX(CASE WHEN row_num >= CEIL(0.95 * bucket_count) THEN duration END) AS DOUBLE) AS p95")
            ->groupBy('bucket_slot')
            ->orderBy('bucket_slot')
            ->get();
    }
}
