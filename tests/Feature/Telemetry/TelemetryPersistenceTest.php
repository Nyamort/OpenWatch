<?php

use App\Jobs\ProcessTelemetryBatch;
use App\Models\Environment;
use App\Models\TelemetryRecord;
use Illuminate\Support\Facades\DB;

test('ProcessTelemetryBatch inserts a telemetry_record for a request type', function () {
    $environment = Environment::factory()->create();

    $record = [
        'v' => 1,
        't' => 'request',
        'timestamp' => now()->toIso8601String(),
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

    $job->handle(app(\App\Services\Ingestion\RecordValidatorRegistry::class));

    expect(TelemetryRecord::where('environment_id', $environment->id)->count())->toBe(1);

    $telemetry = TelemetryRecord::where('environment_id', $environment->id)->first();
    expect($telemetry->record_type)->toBe('request');

    $this->assertDatabaseHas('extraction_requests', [
        'telemetry_record_id' => $telemetry->id,
        'environment_id' => $environment->id,
        'method' => 'GET',
        'status_code' => 200,
    ]);
});

test('ProcessTelemetryBatch fans out to extraction_requests table', function () {
    $environment = Environment::factory()->create();

    $record = [
        'v' => 1,
        't' => 'request',
        'timestamp' => now()->toIso8601String(),
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

    $initialCount = DB::table('extraction_requests')->count();

    $job = new ProcessTelemetryBatch(
        environmentId: $environment->id,
        records: [$record],
        requestId: fake()->uuid(),
    );

    $job->handle(app(\App\Services\Ingestion\RecordValidatorRegistry::class));

    expect(DB::table('extraction_requests')->count())->toBe($initialCount + 1);
});
