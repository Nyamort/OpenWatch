<?php

namespace App\Concerns;

use App\Services\ClickHouse\ClickHouseService;

trait FetchesUserDetails
{
    private function fetchUserDetails(int $envId, ?string $userId): ?object
    {
        if ($userId === null || $userId === '') {
            return null;
        }

        $escapedUserId = ClickHouseService::escape($userId);

        return $this->clickhouse->selectOne("
            SELECT any(name) AS name, username
            FROM extraction_user_activities
            WHERE environment_id = {$envId}
              AND user_id = {$escapedUserId}
              AND username != ''
            GROUP BY username
            LIMIT 1
        ");
    }
}
