<?php

namespace App\Contracts;

class QuotaStatus
{
    /**
     * Create a new QuotaStatus value object.
     */
    public function __construct(
        public readonly string $status,
        public readonly int $current,
        public readonly ?int $limit,
    ) {}

    /**
     * Whether the quota is within acceptable limits.
     */
    public function isOk(): bool
    {
        return $this->status === 'ok';
    }

    /**
     * Whether the quota is approaching the limit.
     */
    public function isWarning(): bool
    {
        return $this->status === 'warning';
    }

    /**
     * Whether the quota has been exceeded.
     */
    public function isExceeded(): bool
    {
        return $this->status === 'exceeded';
    }
}
