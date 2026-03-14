<?php

namespace App\Services\Ingestion;

use Illuminate\Contracts\Cache\Repository;

class ConcurrencyLimiter
{
    private const MAX_CONCURRENT = 2;

    private const TTL = 30; // seconds

    public function __construct(private Repository $cache) {}

    /**
     * Attempt to acquire a concurrency slot for the given environment.
     */
    public function acquire(int $environmentId): bool
    {
        $key = "ingest:concurrency:{$environmentId}";

        // Initialize the key if it doesn't exist
        $this->cache->add($key, 0, self::TTL);

        $count = $this->cache->increment($key);

        if ($count <= self::MAX_CONCURRENT) {
            return true;
        }

        $this->cache->decrement($key);

        return false;
    }

    /**
     * Release a concurrency slot for the given environment.
     */
    public function release(int $environmentId): void
    {
        $key = "ingest:concurrency:{$environmentId}";

        $current = (int) $this->cache->get($key, 0);

        if ($current > 0) {
            $this->cache->decrement($key);
        }
    }
}
