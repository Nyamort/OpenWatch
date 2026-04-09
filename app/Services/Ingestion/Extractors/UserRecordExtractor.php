<?php

namespace App\Services\Ingestion\Extractors;

class UserRecordExtractor extends BaseRecordExtractor
{
    public function table(): string
    {
        return 'extraction_user_activities';
    }

    protected function typeFields(array $record): array
    {
        return [
            'user_id' => ($record['id'] ?? '') !== '' ? (string) $record['id'] : null,
            'name' => ($record['name'] ?? '') !== '' ? (string) $record['name'] : null,
            'username' => ($record['username'] ?? '') !== '' ? (string) $record['username'] : null,
        ];
    }
}
