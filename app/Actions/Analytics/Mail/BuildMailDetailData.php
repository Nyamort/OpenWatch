<?php

namespace App\Actions\Analytics\Mail;

use App\Services\Analytics\AnalyticsContext;
use App\Services\Analytics\AnalyticsResponseBuilder;
use App\Services\ClickHouse\ClickHouseService;

class BuildMailDetailData
{
    public function __construct(private readonly ClickHouseService $clickhouse) {}

    /**
     * Fetch a single mail record with all fields.
     *
     * @return array<string, mixed>
     */
    public function handle(AnalyticsContext $ctx, string $mailId): array
    {
        $envId = $ctx->environment->id;
        $escapedId = ClickHouseService::escape($mailId);

        $mail = $this->clickhouse->selectOne("
            SELECT *
            FROM extraction_mails
            WHERE id = {$escapedId}
              AND environment_id = {$envId}
            LIMIT 1
        ");

        if ($mail === null) {
            abort(404, 'Mail record not found.');
        }

        return (new AnalyticsResponseBuilder)
            ->withSummary((array) $mail)
            ->build();
    }
}
