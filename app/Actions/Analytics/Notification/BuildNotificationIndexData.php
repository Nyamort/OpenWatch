<?php

namespace App\Actions\Analytics\Notification;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\Analytics\PeriodResult;
use Illuminate\Support\Facades\DB;

class BuildNotificationIndexData
{
    /** @var list<string> */
    public const KNOWN_CHANNELS = ['database', 'mail', 'broadcast', 'slack', 'vonage', 'nexmo'];

    /**
     * Build notification analytics grouped by class + channel.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, PeriodResult $period): array
    {
        $rows = DB::table('extraction_notifications')
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->whereBetween('recorded_at', [$period->start, $period->end])
            ->select([
                'class',
                DB::raw("CASE WHEN channel IN ('database','mail','broadcast','slack','vonage','nexmo') THEN channel ELSE 'other' END as channel_group"),
                DB::raw('COUNT(*) as total'),
                DB::raw('CAST(SUM(CASE WHEN failed = 0 THEN 1 ELSE 0 END) AS UNSIGNED) as sent_count'),
                DB::raw('CAST(SUM(CASE WHEN failed = 1 THEN 1 ELSE 0 END) AS UNSIGNED) as failed_count'),
                DB::raw('ROUND((SUM(CASE WHEN failed = 1 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0)) , 2) as failed_rate'),
            ])
            ->groupBy('class', 'channel_group')
            ->orderBy('total', 'desc')
            ->paginate(50);

        return (new AnalyticsResponseBuilder)
            ->withSummary(['period_label' => $period->label])
            ->withRows($rows->items())
            ->withPagination([
                'current_page' => $rows->currentPage(),
                'last_page' => $rows->lastPage(),
                'per_page' => $rows->perPage(),
                'total' => $rows->total(),
            ])
            ->withConfig(['period' => $period->label])
            ->build();
    }
}
