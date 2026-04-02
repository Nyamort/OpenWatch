<?php

use App\Actions\Projects\GenerateToken;
use App\Actions\Projects\RevokeToken;
use App\Actions\Projects\RotateToken;
use App\Models\Environment;
use App\Models\ProjectToken;
use App\Services\Ingestion\ValidateIngestToken;

test('it generates a token and returns raw value once', function () {
    $environment = Environment::factory()->create();

    $result = (new GenerateToken)->handle($environment);

    expect($result)->toHaveKeys(['token', 'model'])
        ->and($result['token'])->toBeString()->not->toBeEmpty()
        ->and($result['model'])->toBeInstanceOf(ProjectToken::class);
});

test('it stores only the sha256 hash not raw token', function () {
    $environment = Environment::factory()->create();

    $result = (new GenerateToken)->handle($environment);
    $rawToken = $result['token'];
    $model = $result['model'];

    expect($model->token_hash)->toBe(hash('sha256', $rawToken))
        ->and($model->token_hash)->not->toBe($rawToken);
});

test('it validates an active token', function () {
    $environment = Environment::factory()->create();
    $result = (new GenerateToken)->handle($environment);

    $validation = (new ValidateIngestToken)->validate($result['token']);

    expect($validation->environment)->not->toBeNull()
        ->and($validation->environment->id)->toBe($environment->id);
});

test('it rotates a token with grace window and old token still valid during grace', function () {
    $environment = Environment::factory()->create();
    $original = (new GenerateToken)->handle($environment);
    $originalToken = $original['model'];
    $originalRaw = $original['token'];

    $newResult = (new RotateToken(new GenerateToken))->handle($originalToken, 3);
    $newRaw = $newResult['token'];

    // New token is valid
    $validationByNew = (new ValidateIngestToken)->validate($newRaw);
    expect($validationByNew->environment)->not->toBeNull()
        ->and($validationByNew->environment->id)->toBe($environment->id);

    // Old token still valid within grace window
    $validationByOld = (new ValidateIngestToken)->validate($originalRaw);
    expect($validationByOld->environment)->not->toBeNull()
        ->and($validationByOld->environment->id)->toBe($environment->id);

    $originalToken->refresh();
    expect($originalToken->status)->toBe('deprecated')
        ->and($originalToken->grace_until)->not->toBeNull();
});

test('it rejects deprecated token past grace window', function () {
    $environment = Environment::factory()->create();
    $original = (new GenerateToken)->handle($environment);
    $originalToken = $original['model'];
    $originalRaw = $original['token'];

    // Rotate, then move grace_until to the past
    (new RotateToken(new GenerateToken))->handle($originalToken, 3);
    $originalToken->refresh();
    $originalToken->update(['grace_until' => now()->subMinute()]);

    $resolved = (new ValidateIngestToken)->validate($originalRaw);
    expect($resolved->environment)->toBeNull();
});

test('it rejects revoked token immediately', function () {
    $environment = Environment::factory()->create();
    $result = (new GenerateToken)->handle($environment);
    $rawToken = $result['token'];
    $token = $result['model'];

    (new RevokeToken)->handle($token);

    $resolved = (new ValidateIngestToken)->validate($rawToken);
    expect($resolved->environment)->toBeNull();
});
