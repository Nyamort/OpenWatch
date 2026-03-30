<?php

namespace App\Actions\Analytics\Exception;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BuildExceptionIndexData
{
    use PaginatesAnalyticsQuery;

    /**
     * Build graph buckets, global stats, and paginated exception table.
     *
     * @return array<string, mixed>
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        string $sort = 'last_seen',
        string $direction = 'desc',
        string $search = '',
        int $page = 1,
    ): array {
        $base = DB::table('extraction_exceptions')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end]);

        // Global stats
        $stats = (clone $base)->selectRaw('
            COUNT(*) as count,
            SUM(CASE WHEN handled = 1 THEN 1 ELSE 0 END) as handled,
            SUM(CASE WHEN handled = 0 THEN 1 ELSE 0 END) as unhandled
        ')->first();

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
                'handled' => (int) ($row?->handled ?? 0),
                'unhandled' => (int) ($row?->unhandled ?? 0),
            ];
        }

        $exceptions = $this->fetchExceptions($base, $sort, $direction, $search, $page);

        return [
            'graph' => $graph,
            'stats' => [
                'count' => (int) ($stats?->count ?? 0),
                'handled' => (int) ($stats?->handled ?? 0),
                'unhandled' => (int) ($stats?->unhandled ?? 0),
            ],
            'exceptions' => $exceptions['data'],
            'pagination' => $exceptions['pagination'],
        ];
    }

    /**
     * Fetch per-group exception aggregates.
     *
     * @return array<string, mixed>
     */
    private function fetchExceptions(Builder $base, string $sort, string $direction, string $search, int $page): array
    {
        if ($search !== '') {
            $base = (clone $base)->where('class', 'like', '%'.$search.'%');
        }

        $allowedSorts = [
            'last_seen' => 'last_seen',
            'class' => 'class',
            'count' => 'count',
            'users' => 'users',
        ];
        $orderCol = $this->resolveSort($sort, $allowedSorts, 'last_seen');
        $orderDir = $direction === 'asc' ? 'asc' : 'desc';

        $totalGroups = (clone $base)->distinct()->count('group_key');
        $offset = $this->pageOffset($page);

        $rows = (clone $base)
            ->selectRaw('
                group_key,
                MIN(class) as class,
                COUNT(*) as count,
                COUNT(DISTINCT user) as users,
                MAX(recorded_at) as last_seen,
                MIN(recorded_at) as first_seen
            ')
            ->groupBy('group_key')
            ->orderByRaw("{$orderCol} {$orderDir}")
            ->limit($this->analyticsPerPage)
            ->offset($offset)
            ->get();

        $data = $rows->map(fn ($row) => [
            'group_key' => $row->group_key,
            'class' => $row->class,
            'count' => (int) $row->count,
            'users' => (int) $row->users,
            'last_seen' => $row->last_seen,
            'first_seen' => $row->first_seen,
        ])->all();

        return [
            'data' => $data,
            'pagination' => $this->buildPaginationMeta($totalGroups, $page),
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
     * Fetch per-bucket handled/unhandled counts.
     */
    private function fetchBuckets(Builder $base, string $slotExpr): \Illuminate\Support\Collection
    {
        return (clone $base)
            ->selectRaw("
                {$slotExpr} AS bucket_slot,
                SUM(CASE WHEN handled = 1 THEN 1 ELSE 0 END) AS handled,
                SUM(CASE WHEN handled = 0 THEN 1 ELSE 0 END) AS unhandled
            ")
            ->groupByRaw($slotExpr)
            ->orderByRaw($slotExpr)
            ->get();
    }
}
