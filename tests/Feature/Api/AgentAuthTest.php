<?php

use App\Models\Environment;
use App\Models\ProjectToken;

test('valid token returns 200 with session token data', function () {
    $rawToken = bin2hex(random_bytes(32));
    $environment = Environment::factory()->create();

    ProjectToken::factory()->create([
        'environment_id' => $environment->id,
        'token_hash' => hash('sha256', $rawToken),
        'status' => 'active',
    ]);

    $response = $this->postJson('/api/agent-auth', [], [
        'Authorization' => "Bearer {$rawToken}",
    ]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'expires_in', 'refresh_in', 'ingest_url']);
});

test('missing authorization header returns 401 with refresh_in', function () {
    $response = $this->postJson('/api/agent-auth');

    $response->assertUnauthorized()
        ->assertJsonFragment(['refresh_in' => 60]);
});

test('invalid token returns 401', function () {
    $response = $this->postJson('/api/agent-auth', [], [
        'Authorization' => 'Bearer invalid-token-xyz',
    ]);

    $response->assertUnauthorized();
});

test('revoked token returns 403', function () {
    $rawToken = bin2hex(random_bytes(32));
    $environment = Environment::factory()->create();

    ProjectToken::factory()->create([
        'environment_id' => $environment->id,
        'token_hash' => hash('sha256', $rawToken),
        'status' => 'revoked',
    ]);

    $response = $this->postJson('/api/agent-auth', [], [
        'Authorization' => "Bearer {$rawToken}",
    ]);

    $response->assertForbidden();
});
