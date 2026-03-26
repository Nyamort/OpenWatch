<?php

namespace App\Actions\Analytics\Request;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\PeriodResult;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BuildRequestIndexData
{
    /**
     * Build graph buckets and global stats for request analytics.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period): array
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

        // Time-bucketed graph data — p95 via window functions
        $bucketSeconds = $period->bucketSeconds;

        $rawBuckets = DB::select('
            SELECT
                DATE_ADD(\'1970-01-01\', INTERVAL (bucket_slot * ?) SECOND) AS bucket,
                COUNT(*) AS count,
                SUM(CASE WHEN status_code < 400 THEN 1 ELSE 0 END) AS `2xx`,
                SUM(CASE WHEN status_code >= 400 AND status_code < 500 THEN 1 ELSE 0 END) AS `4xx`,
                SUM(CASE WHEN status_code >= 500 THEN 1 ELSE 0 END) AS `5xx`,
                CAST(MIN(duration) AS DOUBLE) AS min,
                CAST(MAX(duration) AS DOUBLE) AS max,
                CAST(ROUND(AVG(duration), 2) AS DOUBLE) AS avg,
                CAST(
                    MAX(CASE WHEN row_num >= CEIL(0.95 * bucket_count) THEN duration END)
                AS DOUBLE) AS p95
            FROM (
                SELECT
                    status_code,
                    duration,
                    FLOOR(UNIX_TIMESTAMP(recorded_at) / ?) AS bucket_slot,
                    ROW_NUMBER() OVER (
                        PARTITION BY FLOOR(UNIX_TIMESTAMP(recorded_at) / ?)
                        ORDER BY duration
                    ) AS row_num,
                    COUNT(*) OVER (
                        PARTITION BY FLOOR(UNIX_TIMESTAMP(recorded_at) / ?)
                    ) AS bucket_count
                FROM extraction_requests
                WHERE organization_id = ?
                  AND project_id = ?
                  AND environment_id = ?
                  AND recorded_at BETWEEN ? AND ?
            ) AS ranked
            GROUP BY bucket_slot
            ORDER BY bucket_slot
        ', [
            $bucketSeconds,
            $bucketSeconds, $bucketSeconds, $bucketSeconds,
            $ctx->organization->id, $ctx->project->id, $ctx->environment->id,
            $period->start, $period->end,
        ]);

        $bucketMap = collect($rawBuckets)->keyBy('bucket');

        // Build complete bucket series with zeros for missing buckets.
        // Use UTC-aligned unix slots to match DATE_ADD('1970-01-01', ...) in SQL.
        $graph = [];
        $startSlot = (int) floor(Carbon::parse($period->start)->utc()->timestamp / $bucketSeconds);
        $endSlot = (int) floor(Carbon::parse($period->end)->utc()->timestamp / $bucketSeconds);

        for ($slot = $startSlot; $slot <= $endSlot; $slot++) {
            $key = Carbon::createFromTimestampUTC($slot * $bucketSeconds)->format('Y-m-d H:i:s');
            $row = $bucketMap->get($key);
            $graph[] = [
                'bucket' => $key,
                'count' => $row?->count,
                '2xx' => $row?->{'2xx'},
                '4xx' => $row?->{'4xx'},
                '5xx' => $row?->{'5xx'},
                'min' => $row?->min,
                'max' => $row?->max,
                'avg' => $row?->avg,
                'p95' => $row?->p95,
            ];
        }

        return [
            'graph' => $graph,
            'stats' => [
                'count' => $totalCount,
                '2xx' => $stats?->{'2xx'},
                '4xx' => $stats?->{'4xx'},
                '5xx' => $stats?->{'5xx'},
                'min' => $stats->min ?? null,
                'max' => $stats->max ?? null,
                'avg' => $stats->avg ?? null,
                'p95' => $globalP95 ? (float) $globalP95 : null,
            ],
        ];
    }
}
