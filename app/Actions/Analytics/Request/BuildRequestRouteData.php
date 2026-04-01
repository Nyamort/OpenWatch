<?php

namespace App\Actions\Analytics\Request;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BuildRequestRouteData
{
    use PaginatesAnalyticsQuery;

    /**
     * Build graph buckets, stats and paginated request list for a single route.
     *
     * @return array<string, mixed>
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        string $routePath,
        string $method,
        string $sort = 'date',
        string $direction = 'desc',
        int $page = 1,
    ): array {
        $base = DB::table('extraction_requests')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end]);

        $base->where('route_path', $routePath);

        if ($method !== '') {
            $base->where('method', strtoupper($method));
        }

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
                'avg' => $row?->avg,
                'p95' => $row?->p95,
            ];
        }

        $requests = $this->fetchRequests($base, $sort, $direction, $page);

        return [
            'graph' => $graph,
            'requests' => $requests['data'],
            'pagination' => $requests['pagination'],
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
            'route_path' => $routePath,
            'method' => $method !== '' ? strtoupper($method) : null,
        ];
    }

    /**
     * Fetch paginated individual requests.
     *
     * @return array<string, mixed>
     */
    private function fetchRequests(Builder $base, string $sort = 'date', string $direction = 'desc', int $page = 1): array
    {
        $allowedSorts = [
            'date' => 'recorded_at',
            'status' => 'status_code',
            'duration' => 'duration',
        ];
        $orderCol = $this->resolveSort($sort, $allowedSorts, 'recorded_at');
        $orderDir = $direction === 'asc' ? 'asc' : 'desc';

        $total = (clone $base)->count();
        $offset = $this->pageOffset($page);

        $rows = (clone $base)
            ->select(['id', 'method', 'url', 'status_code', 'duration', 'exceptions', 'queries', 'recorded_at'])
            ->orderByRaw("{$orderCol} {$orderDir}")
            ->limit($this->analyticsPerPage)
            ->offset($offset)
            ->get();

        $data = $rows->map(fn ($row) => [
            'id' => $row->id,
            'recorded_at' => $row->recorded_at,
            'method' => $row->method,
            'url' => $row->url,
            'status_code' => (int) $row->status_code,
            'duration' => $row->duration,
            'exceptions' => (int) ($row->exceptions ?? 0),
            'queries' => (int) ($row->queries ?? 0),
        ])->all();

        return [
            'data' => $data,
            'pagination' => $this->buildPaginationMeta($total, $page),
        ];
    }

    /**
     * Driver-aware SQL expression for the integer bucket slot from recorded_at.
     */
    private function bucketSlotExpression(int $bucketSeconds): string
    {
        $epoch = match (DB::getDriverName()) {
            'pgsql' => 'EXTRACT(EPOCH FROM recorded_at)',
            'sqlite' => "CAST(strftime('%s', recorded_at) AS INTEGER)",
            default => 'UNIX_TIMESTAMP(recorded_at)',
        };

        return "FLOOR({$epoch} / {$bucketSeconds})";
    }

    /**
     * Fetch per-bucket aggregates (2xx/4xx/5xx + avg + p95).
     */
    private function fetchBuckets(Builder $base, string $slotExpr): \Illuminate\Support\Collection
    {
        $aggregates = '
            COUNT(*) AS count,
            SUM(CASE WHEN status_code < 400 THEN 1 ELSE 0 END) AS `2xx`,
            SUM(CASE WHEN status_code >= 400 AND status_code < 500 THEN 1 ELSE 0 END) AS `4xx`,
            SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) AS `5xx`,
            CAST(ROUND(AVG(duration), 2) AS DOUBLE) AS avg
        ';

        if (DB::getDriverName() === 'sqlite') {
            return (clone $base)
                ->selectRaw("{$slotExpr} AS bucket_slot, {$aggregates}, NULL AS p95")
                ->groupByRaw($slotExpr)
                ->orderByRaw($slotExpr)
                ->get();
        }

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
