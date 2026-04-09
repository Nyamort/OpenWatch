<?php

namespace App\Services\Ingestion\Handlers;

use App\Services\Ingestion\DTOs\RecordDTO;
use App\Services\Ingestion\DTOs\UserRecordDTO;

class UserRecordHandler extends BaseRecordHandler
{
    public function table(): string
    {
        return 'extraction_user_activities';
    }

    public function parse(array $raw): ?UserRecordDTO
    {
        foreach (['v', 't', 'timestamp'] as $field) {
            if (! array_key_exists($field, $raw)) {
                return null;
            }
        }

        if (! $this->hasFields($raw, ['id', 'name', 'username'])) {
            return null;
        }

        return new UserRecordDTO(
            timestamp: (float) $raw['timestamp'],
            userId: ($raw['id'] ?? '') !== '' ? (string) $raw['id'] : null,
            name: ($raw['name'] ?? '') !== '' ? (string) $raw['name'] : null,
            username: ($raw['username'] ?? '') !== '' ? (string) $raw['username'] : null,
        );
    }

    protected function typeFields(RecordDTO $dto): array
    {
        /** @var UserRecordDTO $dto */
        return [
            'user_id' => $dto->userId,
            'name' => $dto->name,
            'username' => $dto->username,
        ];
    }
}
