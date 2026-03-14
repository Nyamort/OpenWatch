<?php

namespace App\Services\Ingestion;

use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Str;

class SessionTokenService
{
    private const TTL = 3600; // 1 hour

    private const REFRESH_IN = 300; // 5 min before expiry

    public function __construct(private Repository $cache) {}

    /**
     * Issue a new session token for the given environment.
     *
     * @return array{token: string, expires_in: int, refresh_in: int, ingest_url: string}
     */
    public function issue(int $environmentId): array
    {
        $token = (string) Str::uuid();

        $this->cache->put("session_token:{$token}", [
            'environment_id' => $environmentId,
            'issued_at' => now()->toIso8601String(),
        ], self::TTL);

        return [
            'token' => $token,
            'expires_in' => self::TTL,
            'refresh_in' => self::REFRESH_IN,
            'ingest_url' => config('ingest.url'),
        ];
    }

    /**
     * Validate a session token and return the associated environment ID, or null if invalid.
     */
    public function validate(string $token): ?int
    {
        $data = $this->cache->get("session_token:{$token}");

        if ($data === null) {
            return null;
        }

        return (int) $data['environment_id'];
    }

    /**
     * Revoke a session token.
     */
    public function revoke(string $token): void
    {
        $this->cache->forget("session_token:{$token}");
    }
}
