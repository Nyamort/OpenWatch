<?php

namespace App\Actions\Analytics\Query;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BuildQueryIndexData
{
    use PaginatesAnalyticsQuery;

    /**
     * Build graph buckets, global stats, and paginated query table.
     *
     * @return array<string, mixed>
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        string $sort = 'calls',
        string $direction = 'desc',
        string $search = '',
        int $page = 1,
    ): array {
        $base = DB::table('extraction_queries')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end]);

        // Global stats
        $stats = (clone $base)->selectRaw('
            COUNT(*) as count,
            CAST(ROUND(AVG(duration), 2) AS DOUBLE) as avg,
            CAST(MIN(duration) AS DOUBLE) as min,
            CAST(MAX(duration) AS DOUBLE) as max
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
                'calls' => (int) ($row?->calls ?? 0),
                'avg' => $row?->avg,
                'p95' => $row?->p95,
            ];
        }

        $queries = $this->fetchQueries($base, $sort, $direction, $search, $page);

        return [
            'graph' => $graph,
            'stats' => [
                'count' => $totalCount,
                'avg' => $stats->avg ?? null,
                'min' => $stats->min ?? null,
                'max' => $stats->max ?? null,
                'p95' => $globalP95 ? (float) $globalP95 : null,
            ],
            'queries' => $queries['data'],
            'pagination' => $queries['pagination'],
        ];
    }

    /**
     * Fetch per-query aggregates grouped by sql_hash.
     *
     * @return array<string, mixed>
     */
    private function fetchQueries(Builder $base, string $sort, string $direction, string $search, int $page): array
    {
        if ($search !== '') {
            $base = (clone $base)->where('sql_normalized', 'like', '%'.$search.'%');
        }

        $allowedSorts = [
            'query' => 'query',
            'connection' => 'connection',
            'calls' => 'calls',
            'total' => 'total',
            'avg' => 'avg',
            'p95' => 'p95',
        ];
        $orderCol = $this->resolveSort($sort, $allowedSorts, 'calls');
        $orderDir = $direction === 'asc' ? 'asc' : 'desc';

        $totalQueries = (clone $base)->distinct()->count('sql_hash');
        $offset = $this->pageOffset($page);

        $aggregates = '
            MIN(sql_normalized) AS query,
            MIN(connection) AS connection,
            COUNT(*) AS calls,
            CAST(SUM(duration) AS DOUBLE) AS total,
            CAST(ROUND(AVG(duration), 2) AS DOUBLE) AS avg
        ';

        if (DB::getDriverName() === 'sqlite') {
            $rows = (clone $base)
                ->selectRaw("sql_hash, {$aggregates}, NULL AS p95")
                ->groupBy('sql_hash')
                ->orderByRaw("{$orderCol} {$orderDir}")
                ->limit($this->analyticsPerPage)
                ->offset($offset)
                ->get();
        } else {
            $inner = (clone $base)->select([
                'sql_hash',
                'sql_normalized',
                'connection',
                'duration',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY sql_hash ORDER BY duration) AS row_num'),
                DB::raw('COUNT(*) OVER (PARTITION BY sql_hash) AS hash_count'),
            ]);

            $rows = DB::query()
                ->fromSub($inner, 'ranked')
                ->selectRaw("sql_hash, {$aggregates}, CAST(MAX(CASE WHEN row_num >= CEIL(0.95 * hash_count) THEN duration END) AS DOUBLE) AS p95")
                ->groupBy('sql_hash')
                ->orderByRaw("{$orderCol} {$orderDir}")
                ->limit($this->analyticsPerPage)
                ->offset($offset)
                ->get();
        }

        $data = $rows->map(fn ($row) => [
            'sql_hash' => $row->sql_hash,
            'query' => $row->query,
            'connection' => $row->connection,
            'calls' => (int) $row->calls,
            'total' => $row->total,
            'avg' => $row->avg,
            'p95' => $row->p95,
        ])->all();

        return [
            'data' => $data,
            'pagination' => $this->buildPaginationMeta($totalQueries, $page),
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
        if (DB::getDriverName() === 'sqlite') {
            return (clone $base)
                ->selectRaw("{$slotExpr} AS bucket_slot, COUNT(*) AS calls, CAST(ROUND(AVG(duration), 2) AS DOUBLE) AS avg, NULL AS p95")
                ->groupByRaw($slotExpr)
                ->orderByRaw($slotExpr)
                ->get();
        }

        $inner = (clone $base)->select([
            'duration',
            DB::raw("{$slotExpr} AS bucket_slot"),
            DB::raw('ROW_NUMBER() OVER (PARTITION BY '.$slotExpr.' ORDER BY duration) AS row_num'),
            DB::raw('COUNT(*) OVER (PARTITION BY '.$slotExpr.') AS bucket_count'),
        ]);

        return DB::query()
            ->fromSub($inner, 'ranked')
            ->selectRaw('bucket_slot, COUNT(*) AS calls, CAST(ROUND(AVG(duration), 2) AS DOUBLE) AS avg, CAST(MAX(CASE WHEN row_num >= CEIL(0.95 * bucket_count) THEN duration END) AS DOUBLE) AS p95')
            ->groupBy('bucket_slot')
            ->orderBy('bucket_slot')
            ->get();
    }
}
