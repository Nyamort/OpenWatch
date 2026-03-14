<?php

use App\Services\Analytics\PeriodResult;
use App\Services\Analytics\PeriodService;

test('it parses 1h preset correctly', function () {
    $service = new PeriodService;
    $result = $service->parse('1h');

    expect($result)->toBeInstanceOf(PeriodResult::class)
        ->and($result->bucketSeconds)->toBe(30)
        ->and($result->label)->toBe('Last 1 hour')
        ->and((int) $result->start->diffInMinutes($result->end))->toBe(60);
});

test('it parses 24h preset correctly', function () {
    $service = new PeriodService;
    $result = $service->parse('24h');

    expect($result->bucketSeconds)->toBe(900)
        ->and($result->label)->toBe('Last 24 hours')
        ->and((int) $result->start->diffInHours($result->end))->toBe(24);
});

test('it parses 7d preset correctly', function () {
    $service = new PeriodService;
    $result = $service->parse('7d');

    expect($result->bucketSeconds)->toBe(7200)
        ->and($result->label)->toBe('Last 7 days')
        ->and((int) $result->start->diffInDays($result->end))->toBe(7);
});

test('it parses 14d preset correctly', function () {
    $service = new PeriodService;
    $result = $service->parse('14d');

    expect($result->bucketSeconds)->toBe(14400)
        ->and($result->label)->toBe('Last 14 days')
        ->and((int) $result->start->diffInDays($result->end))->toBe(14);
});

test('it parses 30d preset correctly', function () {
    $service = new PeriodService;
    $result = $service->parse('30d');

    expect($result->bucketSeconds)->toBe(21600)
        ->and($result->label)->toBe('Last 30 days')
        ->and((int) $result->start->diffInDays($result->end))->toBe(30);
});

test('it parses a custom period range', function () {
    $service = new PeriodService;
    $result = $service->parse('2026-01-01..2026-01-07');

    expect($result)->toBeInstanceOf(PeriodResult::class)
        ->and($result->start->toDateString())->toBe('2026-01-01')
        ->and($result->end->toDateString())->toBe('2026-01-07')
        ->and($result->bucketSeconds)->toBeGreaterThan(0);
});

test('custom period produces at most 300 buckets', function () {
    $service = new PeriodService;
    $result = $service->parse('2026-01-01..2026-03-31');

    $totalSeconds = $result->end->diffInSeconds($result->start);
    $buckets = (int) ceil($totalSeconds / $result->bucketSeconds);

    expect($buckets)->toBeLessThanOrEqual(300);
});

test('it rejects custom period exceeding 90 days', function () {
    $service = new PeriodService;

    expect(fn () => $service->parse('2026-01-01..2026-06-01'))
        ->toThrow(InvalidArgumentException::class, 'Custom period must not exceed 90 days.');
});

test('it rejects custom period where start is after end', function () {
    $service = new PeriodService;

    expect(fn () => $service->parse('2026-03-01..2026-01-01'))
        ->toThrow(InvalidArgumentException::class, 'Custom period start must be before end.');
});

test('it throws on invalid period string', function () {
    $service = new PeriodService;

    expect(fn () => $service->parse('invalid'))
        ->toThrow(InvalidArgumentException::class, 'Invalid period string: invalid');
});

test('it throws on unknown preset', function () {
    $service = new PeriodService;

    expect(fn () => $service->parse('2w'))
        ->toThrow(InvalidArgumentException::class);
});
