<?php

namespace App\Actions\Analytics\OutgoingRequest;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BuildOutgoingRequestIndexData
{
    use PaginatesAnalyticsQuery;

    /**
     * Build graph buckets, global stats, and paginated host table.
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
        $base = DB::table('extraction_outgoing_requests')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end]);

        // Global stats
        $stats = (clone $base)->selectRaw('
            COUNT(*) as total,
            CAST(SUM(CASE WHEN status_code IS NOT NULL AND status_code < 400 THEN 1 ELSE 0 END) AS UNSIGNED) as success,
            CAST(SUM(CASE WHEN status_code >= 400 AND status_code < 500 THEN 1 ELSE 0 END) AS UNSIGNED) as count_4xx,
            CAST(SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) AS UNSIGNED) as count_5xx,
            CAST(ROUND(AVG(duration), 2) AS DOUBLE) as avg,
            CAST(MIN(duration) AS DOUBLE) as min,
            CAST(MAX(duration) AS DOUBLE) as max
        ')->first();

        $totalCount = (int) ($stats->total ?? 0);
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
                'success' => (int) ($row?->success ?? 0),
                'count_4xx' => (int) ($row?->count_4xx ?? 0),
                'count_5xx' => (int) ($row?->count_5xx ?? 0),
                'avg' => $row?->avg,
                'p95' => $row?->p95,
            ];
        }

        $hosts = $this->fetchHosts($base, $sort, $direction, $search, $page);

        return [
            'graph' => $graph,
            'stats' => [
                'total' => $totalCount,
                'success' => (int) ($stats->success ?? 0),
                'count_4xx' => (int) ($stats->count_4xx ?? 0),
                'count_5xx' => (int) ($stats->count_5xx ?? 0),
                'avg' => $stats->avg ?? null,
                'min' => $stats->min ?? null,
                'max' => $stats->max ?? null,
                'p95' => $globalP95 ? (float) $globalP95 : null,
            ],
            'hosts' => $hosts['data'],
            'pagination' => $hosts['pagination'],
        ];
    }

    /**
     * Fetch per-host aggregates.
     *
     * @return array<string, mixed>
     */
    private function fetchHosts(Builder $base, string $sort, string $direction, string $search, int $page): array
    {
        if ($search !== '') {
            $base = (clone $base)->where('host', 'like', '%'.$search.'%');
        }

        $allowedSorts = [
            'host' => 'host',
            'success' => 'success',
            'count_4xx' => 'count_4xx',
            'count_5xx' => 'count_5xx',
            'total' => 'total',
            'avg' => 'avg',
            'p95' => 'p95',
        ];
        $orderCol = $this->resolveSort($sort, $allowedSorts, 'total');
        $orderDir = $direction === 'asc' ? 'asc' : 'desc';

        $totalHosts = (clone $base)->distinct()->count('host');
        $offset = $this->pageOffset($page);

        $aggregates = '
            COUNT(*) AS total,
            CAST(SUM(CASE WHEN status_code IS NOT NULL AND status_code < 400 THEN 1 ELSE 0 END) AS UNSIGNED) AS success,
            CAST(SUM(CASE WHEN status_code >= 400 AND status_code < 500 THEN 1 ELSE 0 END) AS UNSIGNED) AS count_4xx,
            CAST(SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) AS UNSIGNED) AS count_5xx,
            CAST(ROUND(AVG(duration), 2) AS DOUBLE) AS avg
        ';

        if (DB::getDriverName() === 'sqlite') {
            $rows = (clone $base)
                ->selectRaw("host, {$aggregates}, NULL AS p95")
                ->groupBy('host')
                ->orderByRaw("{$orderCol} {$orderDir}")
                ->limit($this->analyticsPerPage)
                ->offset($offset)
                ->get();
        } else {
            $inner = (clone $base)->select([
                'host',
                'duration',
                'status_code',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY host ORDER BY duration) AS row_num'),
                DB::raw('COUNT(*) OVER (PARTITION BY host) AS host_count'),
            ]);

            $rows = DB::query()
                ->fromSub($inner, 'ranked')
                ->selectRaw("host, {$aggregates}, CAST(MAX(CASE WHEN row_num >= CEIL(0.95 * host_count) THEN duration END) AS DOUBLE) AS p95")
                ->groupBy('host')
                ->orderByRaw("{$orderCol} {$orderDir}")
                ->limit($this->analyticsPerPage)
                ->offset($offset)
                ->get();
        }

        $data = $rows->map(fn ($row) => [
            'host' => $row->host,
            'success' => (int) $row->success,
            'count_4xx' => (int) $row->count_4xx,
            'count_5xx' => (int) $row->count_5xx,
            'total' => (int) $row->total,
            'avg' => $row->avg,
            'p95' => $row->p95,
        ])->all();

        return [
            'data' => $data,
            'pagination' => $this->buildPaginationMeta($totalHosts, $page),
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
                ->selectRaw("
                    {$slotExpr} AS bucket_slot,
                    CAST(SUM(CASE WHEN status_code IS NOT NULL AND status_code < 400 THEN 1 ELSE 0 END) AS UNSIGNED) AS success,
                    CAST(SUM(CASE WHEN status_code >= 400 AND status_code < 500 THEN 1 ELSE 0 END) AS UNSIGNED) AS count_4xx,
                    CAST(SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) AS UNSIGNED) AS count_5xx,
                    CAST(ROUND(AVG(duration), 2) AS DOUBLE) AS avg,
                    NULL AS p95
                ")
                ->groupByRaw($slotExpr)
                ->orderByRaw($slotExpr)
                ->get();
        }

        $inner = (clone $base)->select([
            'duration',
            'status_code',
            DB::raw("{$slotExpr} AS bucket_slot"),
            DB::raw('ROW_NUMBER() OVER (PARTITION BY '.$slotExpr.' ORDER BY duration) AS row_num'),
            DB::raw('COUNT(*) OVER (PARTITION BY '.$slotExpr.') AS bucket_count'),
        ]);

        return DB::query()
            ->fromSub($inner, 'ranked')
            ->selectRaw('
                bucket_slot,
                CAST(SUM(CASE WHEN status_code IS NOT NULL AND status_code < 400 THEN 1 ELSE 0 END) AS UNSIGNED) AS success,
                CAST(SUM(CASE WHEN status_code >= 400 AND status_code < 500 THEN 1 ELSE 0 END) AS UNSIGNED) AS count_4xx,
                CAST(SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) AS UNSIGNED) AS count_5xx,
                CAST(ROUND(AVG(duration), 2) AS DOUBLE) AS avg,
                CAST(MAX(CASE WHEN row_num >= CEIL(0.95 * bucket_count) THEN duration END) AS DOUBLE) AS p95
            ')
            ->groupBy('bucket_slot')
            ->orderBy('bucket_slot')
            ->get();
    }
}
