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
                FROM_UNIXTIME(bucket_slot * ?) AS bucket,
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

        // Build complete bucket series with zeros for missing buckets
        $graph = [];
        $cursor = Carbon::parse($period->start)->floorSeconds($bucketSeconds);
        $end = Carbon::parse($period->end);

        while ($cursor->lte($end)) {
            $key = $cursor->format('Y-m-d H:i:s');
            $row = $bucketMap->get($key);
            $graph[] = [
                'bucket' => $key,
                'count' => $row ? (int) $row->count : 0,
                '2xx' => $row ? (int) $row->{'2xx'} : 0,
                '4xx' => $row ? (int) $row->{'4xx'} : 0,
                '5xx' => $row ? (int) $row->{'5xx'} : 0,
                'min' => $row ? $row->min : null,
                'max' => $row ? $row->max : null,
                'avg' => $row ? $row->avg : null,
                'p95' => $row ? $row->p95 : null,
            ];
            $cursor->addSeconds($bucketSeconds);
        }

        return [
            'graph' => $graph,
            'stats' => [
                'count' => $totalCount,
                '2xx' => (int) ($stats->{'2xx'} ?? 0),
                '4xx' => (int) ($stats->{'4xx'} ?? 0),
                '5xx' => (int) ($stats->{'5xx'} ?? 0),
                'min' => $stats->min ?? null,
                'max' => $stats->max ?? null,
                'avg' => $stats->avg ?? null,
                'p95' => $globalP95 ? (float) $globalP95 : null,
            ],
        ];
    }
}
