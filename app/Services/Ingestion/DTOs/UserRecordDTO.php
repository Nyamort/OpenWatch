<?php

namespace App\Services\Ingestion\DTOs;

class UserRecordDTO extends RecordDTO
{
    public function __construct(
        float $timestamp,
        public readonly ?string $userId,
        public readonly ?string $name,
        public readonly ?string $username,
    ) {
        parent::__construct($timestamp, deploy: '', server: '');
    }
}
