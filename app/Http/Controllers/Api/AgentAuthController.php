<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Ingestion\SessionTokenService;
use App\Services\Ingestion\ValidateIngestToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentAuthController extends Controller
{
    public function __construct(
        private ValidateIngestToken $tokenValidator,
        private SessionTokenService $sessionTokenService,
    ) {}

    /**
     * Authenticate an agent and issue a session token.
     */
    public function store(Request $request): JsonResponse
    {
        $authHeader = $request->header('Authorization', '');

        if (! str_starts_with((string) $authHeader, 'Bearer ')) {
            return response()->json([
                'message' => 'Missing authorization token.',
                'refresh_in' => 60,
            ], 401);
        }

        $rawToken = substr((string) $authHeader, 7);

        $result = $this->tokenValidator->validate($rawToken);

        if ($result->isRevoked) {
            return response()->json([
                'message' => 'Token has been revoked.',
                'refresh_in' => 60,
            ], 403);
        }

        if ($result->environment === null) {
            return response()->json([
                'message' => 'Invalid authorization token.',
                'refresh_in' => 60,
            ], 401);
        }

        $session = $this->sessionTokenService->issue($result->environment->id);

        return response()->json($session, 200);
    }
}
