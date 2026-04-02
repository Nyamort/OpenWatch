<?php

use App\Jobs\ProcessTelemetryBatch;
use App\Models\Environment;
use App\Services\ClickHouse\ClickHouseService;

test('ProcessTelemetryBatch inserts into extraction_requests for a request record', function () {
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
    ];

    $job = new ProcessTelemetryBatch(
        environmentId: $environment->id,
        records: [$record],
        requestId: fake()->uuid(),
    );

    app()->call([$job, 'handle']);

    $clickhouse = app(ClickHouseService::class);

    $row = $clickhouse->selectOne("
        SELECT * FROM extraction_requests
        WHERE environment_id = {$environment->id}
        LIMIT 1
    ");

    expect($row)->not->toBeNull();
    expect($row->method)->toBe('GET');
    expect((int) $row->status_code)->toBe(200);
    expect((int) $row->duration)->toBe(150);
});

test('ProcessTelemetryBatch inserts into the correct extraction table per record type', function () {
    $environment = Environment::factory()->create();
    $clickhouse = app(ClickHouseService::class);

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

    $count = (int) ($clickhouse->selectValue("
        SELECT count() FROM extraction_requests WHERE environment_id = {$environment->id}
    ") ?? 0);

    expect($count)->toBe(1);
});
