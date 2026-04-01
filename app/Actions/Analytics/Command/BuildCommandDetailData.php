<?php

namespace App\Actions\Analytics\Command;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BuildCommandDetailData
{
    use PaginatesAnalyticsQuery;

    /**
     * Build graph, stats and paginated runs for a single command name.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period, string $name, string $sort = 'date', string $direction = 'desc', int $page = 1): array
    {
        $base = DB::table('extraction_commands')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->where('name', $name)
            ->whereBetween('recorded_at', [$period->start, $period->end]);

        // Global stats
        $stats = (clone $base)->selectRaw('
            COUNT(*) as count,
            SUM(CASE WHEN exit_code = 0 THEN 1 ELSE 0 END) as successful,
            SUM(CASE WHEN exit_code != 0 THEN 1 ELSE 0 END) as failed,
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

        // Time-bucketed graph
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
                'successful' => (int) ($row?->successful ?? 0),
                'failed' => (int) ($row?->failed ?? 0),
                'avg' => $row?->avg,
                'p95' => $row?->p95,
            ];
        }

        $runs = $this->fetchRuns($base, $sort, $direction, $page);

        return [
            'graph' => $graph,
            'runs' => $runs['data'],
            'pagination' => $runs['pagination'],
            'stats' => [
                'count' => $totalCount,
                'successful' => (int) ($stats?->successful ?? 0),
                'failed' => (int) ($stats?->failed ?? 0),
                'min' => $stats->min ?? null,
                'max' => $stats->max ?? null,
                'avg' => $stats->avg ?? null,
                'p95' => $globalP95 ? (float) $globalP95 : null,
            ],
        ];
    }

    /**
     * Fetch paginated individual runs.
     *
     * @return array<string, mixed>
     */
    private function fetchRuns(Builder $base, string $sort = 'date', string $direction = 'desc', int $page = 1): array
    {
        $allowedSorts = ['date' => 'recorded_at', 'exit_code' => 'exit_code', 'duration' => 'duration'];
        $orderCol = $this->resolveSort($sort, $allowedSorts, 'recorded_at');
        $orderDir = $direction === 'asc' ? 'asc' : 'desc';

        $total = (clone $base)->count();
        $offset = $this->pageOffset($page);

        $rows = (clone $base)
            ->select(['id', 'recorded_at', 'name', 'exit_code', 'duration'])
            ->orderByRaw("{$orderCol} {$orderDir}")
            ->limit($this->analyticsPerPage)
            ->offset($offset)
            ->get();

        $data = $rows->map(fn ($row) => [
            'id' => $row->id,
            'recorded_at' => Carbon::parse($row->recorded_at)->format('Y-m-d H:i:s'),
            'name' => $row->name,
            'exit_code' => $row->exit_code,
            'duration' => $row->duration,
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
            'sqlite' => 'CAST(strftime(\'%s\', recorded_at) AS INTEGER)',
            default => 'UNIX_TIMESTAMP(recorded_at)',
        };

        return "FLOOR({$epoch} / {$bucketSeconds})";
    }

    /**
     * Fetch per-bucket aggregates.
     */
    private function fetchBuckets(Builder $base, string $slotExpr): Collection
    {
        $aggregates = '
            SUM(CASE WHEN exit_code = 0 THEN 1 ELSE 0 END) AS successful,
            SUM(CASE WHEN exit_code != 0 THEN 1 ELSE 0 END) AS failed,
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
            'exit_code',
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
