<?php

use App\Events\TelemetryBatchIngested;
use App\Jobs\ProcessTelemetryBatch;
use App\Listeners\HandleExceptionTelemetry;
use App\Models\Environment;
use App\Models\Issue;
use App\Services\Ingestion\DTOs\ExceptionRecordDTO;
use App\Services\Ingestion\DTOs\RequestRecordDTO;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

function exceptionRecord(array $overrides = []): array
{
    return array_merge([
        'v' => 1,
        't' => 'exception',
        'timestamp' => now()->timestamp,
        'deploy' => 'abc123',
        'server' => 'web-01',
        'trace_id' => fake()->uuid(),
        'execution_source' => 'http',
        'execution_id' => fake()->uuid(),
        'class' => 'App\\Exceptions\\TestException',
        'message' => 'Something went wrong',
        'file' => '/var/www/html/app/Http/Controllers/TestController.php',
        'line' => 42,
        'trace' => [],
        'handled' => 0,
    ], $overrides);
}

function exceptionDto(array $overrides = []): ExceptionRecordDTO
{
    return new ExceptionRecordDTO(
        timestamp: now()->timestamp,
        deploy: 'abc123',
        server: 'web-01',
        traceId: fake()->uuid(),
        executionId: fake()->uuid(),
        executionSource: 'http',
        executionStage: 'action',
        executionPreview: null,
        groupKey: null,
        user: null,
        class: $overrides['class'] ?? 'App\\Exceptions\\TestException',
        file: $overrides['file'] ?? '/app/TestController.php',
        line: $overrides['line'] ?? 42,
        message: $overrides['message'] ?? 'Something went wrong',
        code: null,
        trace: '[]',
        handled: 0,
        phpVersion: null,
        laravelVersion: null,
    );
}

test('ProcessTelemetryBatch dispatches TelemetryBatchIngested event with parsed DTOs', function () {
    Event::fake();

    $environment = Environment::factory()->create();

    $job = new ProcessTelemetryBatch(
        environmentId: $environment->id,
        records: [exceptionRecord()],
        requestId: fake()->uuid(),
    );

    app()->call([$job, 'handle']);

    Event::assertDispatched(TelemetryBatchIngested::class, function (TelemetryBatchIngested $event) use ($environment) {
        return $event->environmentId === $environment->id
            && count($event->records) === 1
            && $event->records[0] instanceof ExceptionRecordDTO;
    });
});

test('ProcessTelemetryBatch does not dispatch event when all records are invalid', function () {
    Event::fake();

    $environment = Environment::factory()->create();

    $job = new ProcessTelemetryBatch(
        environmentId: $environment->id,
        records: [['t' => 'exception', 'v' => 1]], // missing required fields
        requestId: fake()->uuid(),
    );

    app()->call([$job, 'handle']);

    Event::assertNotDispatched(TelemetryBatchIngested::class);
});

test('HandleExceptionTelemetry creates an issue for an exception DTO', function () {
    Queue::fake();

    $environment = Environment::factory()->create();
    $environment->load('project.organization');

    $dto = exceptionDto(['class' => 'App\\Exceptions\\UniqueException'.uniqid()]);

    $listener = app(HandleExceptionTelemetry::class);
    $listener->handle(new TelemetryBatchIngested($environment->id, [$dto]));

    $this->assertDatabaseHas('issues', [
        'environment_id' => $environment->id,
        'title' => $dto->class,
        'type' => 'exception',
        'status' => 'open',
        'occurrence_count' => 1,
    ]);
});

test('HandleExceptionTelemetry stores exception message as issue subtitle', function () {
    Queue::fake();

    $environment = Environment::factory()->create();
    $environment->load('project.organization');

    $dto = exceptionDto([
        'class' => 'App\\Exceptions\\SubtitleEx'.uniqid(),
        'message' => 'Division by zero',
    ]);

    $listener = app(HandleExceptionTelemetry::class);
    $listener->handle(new TelemetryBatchIngested($environment->id, [$dto]));

    $this->assertDatabaseHas('issues', [
        'environment_id' => $environment->id,
        'title' => $dto->class,
        'subtitle' => 'Division by zero',
    ]);
});

test('HandleExceptionTelemetry increments occurrence count on repeated exception', function () {
    Queue::fake();

    $environment = Environment::factory()->create();
    $environment->load('project.organization');

    $dto = exceptionDto([
        'class' => 'App\\Exceptions\\RepeatedEx'.uniqid(),
        'message' => 'Same error',
        'file' => '/app/Foo.php',
        'line' => 10,
    ]);

    $listener = app(HandleExceptionTelemetry::class);
    $event = new TelemetryBatchIngested($environment->id, [$dto]);

    $listener->handle($event);
    $listener->handle($event);

    expect(Issue::where('environment_id', $environment->id)->where('title', $dto->class)->count())->toBe(1);

    $issue = Issue::where('environment_id', $environment->id)->where('title', $dto->class)->first();
    expect($issue->occurrence_count)->toBe(2);
});

test('HandleExceptionTelemetry ignores non-exception DTOs', function () {
    Queue::fake();

    $environment = Environment::factory()->create();

    $dto = new RequestRecordDTO(
        timestamp: now()->timestamp,
        deploy: 'abc123',
        server: 'web-01',
        traceId: fake()->uuid(),
        user: null,
        ip: null,
        method: 'GET',
        url: 'https://example.com/test',
        routeName: null,
        routePath: '/test',
        routeMethods: null,
        routeAction: null,
        routeDomain: null,
        statusCode: 200,
        duration: 100,
        bootstrap: null,
        beforeMiddleware: null,
        action: null,
        render: null,
        afterMiddleware: null,
        terminating: null,
        sending: null,
        requestSize: null,
        responseSize: null,
        peakMemoryUsage: null,
        exceptions: 0,
        queries: 0,
        logs: 0,
        cacheEvents: 0,
        jobsQueued: 0,
        notifications: 0,
        outgoingRequests: 0,
        lazyLoads: 0,
        hydratedModels: 0,
        filesRead: 0,
        filesWritten: 0,
        exceptionPreview: null,
        headers: null,
    );

    $before = Issue::where('environment_id', $environment->id)->count();

    $listener = app(HandleExceptionTelemetry::class);
    $listener->handle(new TelemetryBatchIngested($environment->id, [$dto]));

    expect(Issue::where('environment_id', $environment->id)->count())->toBe($before);
});

test('HandleExceptionTelemetry does nothing when environment does not exist', function () {
    Queue::fake();

    $before = Issue::count();

    $listener = app(HandleExceptionTelemetry::class);
    $listener->handle(new TelemetryBatchIngested(999999, [exceptionDto()]));

    expect(Issue::count())->toBe($before);
});
