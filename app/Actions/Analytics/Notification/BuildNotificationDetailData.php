<?php

namespace App\Actions\Analytics\Notification;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\ClickHouse\ClickHouseService;

class BuildNotificationDetailData
{
    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Fetch a single notification record.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, string $notificationId): array
    {
        $orgId = $ctx->organization->id;
        $escapedId = ClickHouseService::escape($notificationId);

        $notification = $this->clickhouse->selectOne("
            SELECT *
            FROM extraction_notifications
            WHERE id = {$escapedId}
              AND organization_id = {$orgId}
            LIMIT 1
        ");

        if ($notification === null) {
            abort(404, 'Notification record not found.');
        }

        return (new AnalyticsResponseBuilder)
            ->withSummary((array) $notification)
            ->build();
    }
}
