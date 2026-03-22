<?php

use App\Services\Ingestion\ConcurrencyLimiter;
use Illuminate\Support\Facades\Cache;

test('two concurrent acquires succeed when max is 2', function () {
    Cache::flush();

    $limiter = app(ConcurrencyLimiter::class);
    $environmentId = 99901;

    $first = $limiter->acquire($environmentId);
    $second = $limiter->acquire($environmentId);

    expect($first)->toBeTrue()
        ->and($second)->toBeTrue();
});

test('third concurrent acquire returns false', function () {
    Cache::flush();

    $limiter = app(ConcurrencyLimiter::class);
    $environmentId = 99902;

    $limiter->acquire($environmentId);
    $limiter->acquire($environmentId);
    $third = $limiter->acquire($environmentId);

    expect($third)->toBeFalse();
});

test('release works correctly allowing a new acquire after release', function () {
    Cache::flush();

    $limiter = app(ConcurrencyLimiter::class);
    $environmentId = 99903;

    $limiter->acquire($environmentId);
    $limiter->acquire($environmentId);

    $blocked = $limiter->acquire($environmentId);
    expect($blocked)->toBeFalse();

    $limiter->release($environmentId);

    $afterRelease = $limiter->acquire($environmentId);
    expect($afterRelease)->toBeTrue();
});
