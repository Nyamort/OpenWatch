<?php

use App\Services\Ingestion\RecordValidatorRegistry;

function validRequestRecord(): array
{
    return [
        'v' => 1,
        't' => 'request',
        'timestamp' => '2026-01-01T00:00:00Z',
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

test('request record with all required fields is accepted', function () {
    $registry = app(RecordValidatorRegistry::class);

    expect($registry->validate(validRequestRecord()))->toBeTrue();
});

test('request record missing a required field is rejected', function () {
    $registry = app(RecordValidatorRegistry::class);

    $record = validRequestRecord();
    unset($record['method']);

    expect($registry->validate($record))->toBeFalse();
});

test('unknown record type throws InvalidArgumentException', function () {
    $registry = app(RecordValidatorRegistry::class);

    expect(fn () => $registry->validate([
        'v' => 1,
        't' => 'unknown-type',
        'timestamp' => '2026-01-01T00:00:00Z',
        'deploy' => 'abc',
        'server' => 'web-01',
        'trace_id' => 'trace-abc',
    ]))->toThrow(InvalidArgumentException::class);
});

test('base required field v is mandatory on every type', function () {
    $registry = app(RecordValidatorRegistry::class);

    $record = validRequestRecord();
    unset($record['v']);

    expect($registry->validate($record))->toBeFalse();
});

test('base required field timestamp is mandatory on every type', function () {
    $registry = app(RecordValidatorRegistry::class);

    $record = validRequestRecord();
    unset($record['timestamp']);

    expect($registry->validate($record))->toBeFalse();
});

test('record must have trace_id or _group', function () {
    $registry = app(RecordValidatorRegistry::class);

    $record = validRequestRecord();
    unset($record['trace_id']);

    expect($registry->validate($record))->toBeFalse();
});

test('record with _group and no trace_id is accepted', function () {
    $registry = app(RecordValidatorRegistry::class);

    $record = validRequestRecord();
    unset($record['trace_id']);
    $record['_group'] = 'group-key-abc';

    expect($registry->validate($record))->toBeTrue();
});
