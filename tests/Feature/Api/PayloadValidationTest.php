<?php

use App\Services\Ingestion\DTOs\RequestRecordDTO;
use App\Services\Ingestion\RecordHandlerRegistry;

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
    $registry = app(RecordHandlerRegistry::class);

    expect($registry->for('request')->parse(validRequestRecord()))->toBeInstanceOf(RequestRecordDTO::class);
});

test('request record missing a required field is rejected', function () {
    $registry = app(RecordHandlerRegistry::class);

    $record = validRequestRecord();
    unset($record['method']);

    expect($registry->for('request')->parse($record))->toBeNull();
});

test('unknown record type throws InvalidArgumentException', function () {
    $registry = app(RecordHandlerRegistry::class);

    expect(fn () => $registry->for('unknown-type'))->toThrow(InvalidArgumentException::class);
});

test('base required field v is mandatory on every type', function () {
    $registry = app(RecordHandlerRegistry::class);

    $record = validRequestRecord();
    unset($record['v']);

    expect($registry->for('request')->parse($record))->toBeNull();
});

test('base required field timestamp is mandatory on every type', function () {
    $registry = app(RecordHandlerRegistry::class);

    $record = validRequestRecord();
    unset($record['timestamp']);

    expect($registry->for('request')->parse($record))->toBeNull();
});

test('record must have trace_id or _group', function () {
    $registry = app(RecordHandlerRegistry::class);

    $record = validRequestRecord();
    unset($record['trace_id']);

    expect($registry->for('request')->parse($record))->toBeNull();
});

test('record with _group and no trace_id is accepted', function () {
    $registry = app(RecordHandlerRegistry::class);

    $record = validRequestRecord();
    unset($record['trace_id']);
    $record['_group'] = 'group-key-abc';

    expect($registry->for('request')->parse($record))->toBeInstanceOf(RequestRecordDTO::class);
});
