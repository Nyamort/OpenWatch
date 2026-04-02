<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessTelemetryBatch;
use App\Services\Ingestion\ConcurrencyLimiter;
use App\Services\Ingestion\SessionTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IngestController extends Controller
{
    public function __construct(
        private SessionTokenService $sessionTokenService,
        private ConcurrencyLimiter $concurrencyLimiter,
    ) {}

    /**
     * Ingest a batch of telemetry records.
     */
    public function store(Request $request): JsonResponse
    {
        $authHeader = $request->header('Authorization', '');

        if (! str_starts_with((string) $authHeader, 'Bearer ')) {
            return response()->json(['message' => 'Missing session token.'], 401);
        }

        $sessionToken = substr((string) $authHeader, 7);
        $environmentId = $this->sessionTokenService->validate($sessionToken);

        if ($environmentId === null) {
            return response()->json(['message' => 'Invalid or expired session token.'], 401);
        }

        if ($request->header('Content-Encoding') !== 'gzip') {
            return response()->json(['message' => 'Content-Encoding must be gzip.'], 415);
        }

        if (! $this->concurrencyLimiter->acquire($environmentId)) {
            return response()->json(['message' => 'Too many concurrent ingestion requests.'], 429);
        }

        try {
            $raw = gzdecode($request->getContent());

            if ($raw === false) {
                return response()->json(['message' => 'Failed to decompress payload.'], 400);
            }

            $data = json_decode($raw, true);

            if ($data === null) {
                return response()->json(['message' => 'Invalid JSON payload.'], 400);
            }

            $requestId = $request->header('X-Request-Id', '');
            ProcessTelemetryBatch::dispatch($environmentId, $data['records'], (string) $requestId);

            return response()->json([]);
        } finally {
            $this->concurrencyLimiter->release($environmentId);
        }
    }
}
