<?php

use App\Jobs\ProcessTelemetryBatch;
use App\Models\Environment;
use App\Services\ClickHouse\ClickHouseService;

test('ProcessTelemetryBatch inserts a telemetry_record for a request type', function () {
    $environment = Environment::factory()->create();

    $record = [
        'v' => 1,
        't' => 'request',
        'timestamp' => now()->timestamp,
        'deploy' => 'abc123',
        'server' => 'web-01',
        'trace_id' => fake()->uuid(),
        'user' => null,
        'method' => 'GET',
        'url' => 'https://example.com/test',
        'route_name' => 'test.route',
        'route_path' => '/test',
        'route_action' => 'TestController@index',
        'status_code' => 200,
        'duration' => 150,
        'ip' => '127.0.0.1',
        'request_size' => null,
        'response_size' => null,
        'peak_memory_usage' => null,
        'exceptions' => 0,
        'queries' => 0,
    ];

    $job = new ProcessTelemetryBatch(
        environmentId: $environment->id,
        records: [$record],
        requestId: fake()->uuid(),
    );

    app()->call([$job, 'handle']);

    $clickhouse = app(ClickHouseService::class);

    $telemetry = $clickhouse->selectOne("
        SELECT * FROM telemetry_records
        WHERE environment_id = {$environment->id}
        ORDER BY recorded_at DESC LIMIT 1
    ");

    expect($telemetry)->not->toBeNull();
    expect($telemetry->record_type)->toBe('request');

    $extraction = $clickhouse->selectOne("
        SELECT * FROM extraction_requests
        WHERE environment_id = {$environment->id}
        AND method = 'GET'
        AND status_code = 200
        LIMIT 1
    ");

    expect($extraction)->not->toBeNull();
    expect($extraction->telemetry_record_id)->toBe($telemetry->id);
});

test('ProcessTelemetryBatch fans out to extraction_requests table', function () {
    $environment = Environment::factory()->create();

    $clickhouse = app(ClickHouseService::class);
    $before = (int) ($clickhouse->selectValue("
        SELECT count() FROM extraction_requests WHERE environment_id = {$environment->id}
    ") ?? 0);

    $record = [
        'v' => 1,
        't' => 'request',
        'timestamp' => now()->timestamp,
        'deploy' => 'deploy-1',
        'server' => 'web-02',
        'trace_id' => fake()->uuid(),
        'user' => null,
        'method' => 'POST',
        'url' => 'https://example.com/api/store',
        'route_name' => 'api.store',
        'route_path' => '/api/store',
        'route_action' => null,
        'status_code' => 201,
        'duration' => 80,
        'ip' => '192.168.1.1',
    ];

    $job = new ProcessTelemetryBatch(
        environmentId: $environment->id,
        records: [$record],
        requestId: fake()->uuid(),
    );

    app()->call([$job, 'handle']);

    $after = (int) ($clickhouse->selectValue("
        SELECT count() FROM extraction_requests WHERE environment_id = {$environment->id}
    ") ?? 0);

    expect($after)->toBe($before + 1);
});
