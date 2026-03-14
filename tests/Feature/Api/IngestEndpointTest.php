<?php

use App\Models\Environment;
use App\Services\Ingestion\SessionTokenService;
use Illuminate\Support\Facades\Bus;

function makeValidRecord(): array
{
    return [
        'v' => 1,
        't' => 'request',
        'timestamp' => now()->toIso8601String(),
        'deploy' => 'abc123',
        'server' => 'web-01',
        'trace_id' => 'trace-abc',
        'user' => null,
        'method' => 'GET',
        'url' => 'https://example.com/',
        'route_name' => 'home',
        'status_code' => 200,
        'duration' => 120,
        'ip' => '127.0.0.1',
    ];
}

function issueSessionToken(int $environmentId): string
{
    $service = app(SessionTokenService::class);
    $result = $service->issue($environmentId);

    return $result['token'];
}

test('valid gzip JSON returns 200 with empty object', function () {
    Bus::fake();

    $environment = Environment::factory()->create();
    $sessionToken = issueSessionToken($environment->id);

    $payload = gzencode(json_encode([makeValidRecord()]));

    $response = $this->call(
        'POST',
        '/api/ingest',
        [],
        [],
        [],
        [
            'HTTP_Authorization' => "Bearer {$sessionToken}",
            'HTTP_Content-Encoding' => 'gzip',
            'CONTENT_TYPE' => 'application/octet-stream',
        ],
        $payload,
    );

    $response->assertOk()->assertExactJson([]);
});

test('missing Content-Encoding gzip returns 415', function () {
    $environment = Environment::factory()->create();
    $sessionToken = issueSessionToken($environment->id);

    $payload = gzencode(json_encode([makeValidRecord()]));

    $response = $this->call(
        'POST',
        '/api/ingest',
        [],
        [],
        [],
        [
            'HTTP_Authorization' => "Bearer {$sessionToken}",
            'CONTENT_TYPE' => 'application/octet-stream',
        ],
        $payload,
    );

    $response->assertStatus(415);
});

test('invalid JSON after decompress returns 400', function () {
    $environment = Environment::factory()->create();
    $sessionToken = issueSessionToken($environment->id);

    $payload = gzencode('not-valid-json{{{');

    $response = $this->call(
        'POST',
        '/api/ingest',
        [],
        [],
        [],
        [
            'HTTP_Authorization' => "Bearer {$sessionToken}",
            'HTTP_Content-Encoding' => 'gzip',
            'CONTENT_TYPE' => 'application/octet-stream',
        ],
        $payload,
    );

    $response->assertStatus(400);
});

test('missing session token returns 401', function () {
    $payload = gzencode(json_encode([makeValidRecord()]));

    $response = $this->call(
        'POST',
        '/api/ingest',
        [],
        [],
        [],
        [
            'HTTP_Content-Encoding' => 'gzip',
            'CONTENT_TYPE' => 'application/octet-stream',
        ],
        $payload,
    );

    $response->assertUnauthorized();
});

test('invalid session token returns 401', function () {
    $payload = gzencode(json_encode([makeValidRecord()]));

    $response = $this->call(
        'POST',
        '/api/ingest',
        [],
        [],
        [],
        [
            'HTTP_Authorization' => 'Bearer invalid-session-token',
            'HTTP_Content-Encoding' => 'gzip',
            'CONTENT_TYPE' => 'application/octet-stream',
        ],
        $payload,
    );

    $response->assertUnauthorized();
});
