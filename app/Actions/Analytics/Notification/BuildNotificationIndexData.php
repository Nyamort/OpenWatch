<?php

namespace App\Actions\Analytics\Notification;

use App\Concerns\PaginatesAnalyticsQuery;
use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class BuildNotificationIndexData
{
    use PaginatesAnalyticsQuery;

    /**
     * Build graph buckets, global stats, and paginated notification table.
     *
     * @return array<string, mixed>
     */
    public function handle(
        AnalyticsContext $ctx,
        PeriodResult $period,
        string $sort = 'count',
        string $direction = 'desc',
        string $search = '',
        int $page = 1,
    ): array {
        $base = DB::table('extraction_notifications')
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
                'count' => (int) ($row?->count ?? 0),
                'avg' => $row?->avg,
                'p95' => $row?->p95,
            ];
        }

        $notifications = $this->fetchNotifications($base, $sort, $direction, $search, $page);

        return [
            'graph' => $graph,
            'stats' => [
                'count' => $totalCount,
                'avg' => $stats->avg ?? null,
                'min' => $stats->min ?? null,
                'max' => $stats->max ?? null,
                'p95' => $globalP95 ? (float) $globalP95 : null,
            ],
            'notifications' => $notifications['data'],
            'pagination' => $notifications['pagination'],
        ];
    }

    /**
     * Fetch per-class aggregates grouped by class.
     *
     * @return array<string, mixed>
     */
    private function fetchNotifications(Builder $base, string $sort, string $direction, string $search, int $page): array
    {
        if ($search !== '') {
            $base = (clone $base)->where('class', 'like', '%'.$search.'%');
        }

        $allowedSorts = [
            'notification' => 'notification',
            'count' => 'count',
            'avg' => 'avg',
            'p95' => 'p95',
        ];
        $orderCol = $this->resolveSort($sort, $allowedSorts, 'count');
        $orderDir = $direction === 'asc' ? 'asc' : 'desc';

        $totalNotifications = (clone $base)->distinct()->count('class');
        $offset = $this->pageOffset($page);

        $aggregates = '
            MIN(class) AS notification,
            MIN(id) AS sample_id,
            COUNT(*) AS count,
            CAST(ROUND(AVG(duration), 2) AS DOUBLE) AS avg
        ';

        if (DB::getDriverName() === 'sqlite') {
            $rows = (clone $base)
                ->selectRaw("class, {$aggregates}, NULL AS p95")
                ->groupBy('class')
                ->orderByRaw("{$orderCol} {$orderDir}")
                ->limit($this->analyticsPerPage)
                ->offset($offset)
                ->get();
        } else {
            $inner = (clone $base)->select([
                'id',
                'class',
                'duration',
                DB::raw('ROW_NUMBER() OVER (PARTITION BY class ORDER BY duration) AS row_num'),
                DB::raw('COUNT(*) OVER (PARTITION BY class) AS class_count'),
            ]);

            $rows = DB::query()
                ->fromSub($inner, 'ranked')
                ->selectRaw("class, {$aggregates}, CAST(MAX(CASE WHEN row_num >= CEIL(0.95 * class_count) THEN duration END) AS DOUBLE) AS p95")
                ->groupBy('class')
                ->orderByRaw("{$orderCol} {$orderDir}")
                ->limit($this->analyticsPerPage)
                ->offset($offset)
                ->get();
        }

        $data = $rows->map(fn ($row) => [
            'class' => $row->class,
            'sample_id' => (int) $row->sample_id,
            'count' => (int) $row->count,
            'avg' => $row->avg,
            'p95' => $row->p95,
        ])->all();

        return [
            'data' => $data,
            'pagination' => $this->buildPaginationMeta($totalNotifications, $page),
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
                ->selectRaw("{$slotExpr} AS bucket_slot, COUNT(*) AS count, CAST(ROUND(AVG(duration), 2) AS DOUBLE) AS avg, NULL AS p95")
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
            ->selectRaw('bucket_slot, COUNT(*) AS count, CAST(ROUND(AVG(duration), 2) AS DOUBLE) AS avg, CAST(MAX(CASE WHEN row_num >= CEIL(0.95 * bucket_count) THEN duration END) AS DOUBLE) AS p95')
            ->groupBy('bucket_slot')
            ->orderBy('bucket_slot')
            ->get();
    }
}
