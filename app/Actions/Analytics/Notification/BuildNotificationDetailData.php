<?php

namespace App\Actions\Analytics\Notification;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use Illuminate\Support\Facades\DB;

class BuildNotificationDetailData
{
    /**
     * Fetch a single notification record.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, int $notificationId): array
    {
        $notification = DB::table('extraction_notifications')
            ->where('id', $notificationId)
            ->where('organization_id', $ctx->organization->id)
            ->where('project_id', $ctx->project->id)
            ->where('environment_id', $ctx->environment->id)
            ->first();

        if ($notification === null) {
            abort(404, 'Notification record not found.');
        }

        return (new AnalyticsResponseBuilder)
            ->withSummary((array) $notification)
            ->build();
    }
}
