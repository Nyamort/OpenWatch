<?php

use App\Events\TelemetryBatchIngested;
use App\Jobs\ProcessTelemetryBatch;
use App\Listeners\HandleExceptionTelemetry;
use App\Models\Environment;
use App\Models\Issue;
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

test('ProcessTelemetryBatch dispatches TelemetryBatchIngested event', function () {
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
            && count($event->records) === 1;
    });
});

test('ProcessTelemetryBatch does not dispatch event when all records are invalid', function () {
    Event::fake([TelemetryBatchIngested::class]);

    $environment = Environment::factory()->create();

    $job = new ProcessTelemetryBatch(
        environmentId: $environment->id,
        records: [['t' => 'exception', 'v' => 1]], // missing required fields
        requestId: fake()->uuid(),
    );

    app()->call([$job, 'handle']);

    Event::assertNotDispatched(TelemetryBatchIngested::class);
});

test('HandleExceptionTelemetry creates an issue for an exception record', function () {
    Queue::fake();

    $environment = Environment::factory()->create();
    $environment->load('project.organization');

    $record = exceptionRecord(['class' => 'App\\Exceptions\\UniqueException'.uniqid()]);

    $listener = app(HandleExceptionTelemetry::class);
    $listener->handle(new TelemetryBatchIngested($environment->id, [$record]));

    $this->assertDatabaseHas('issues', [
        'environment_id' => $environment->id,
        'title' => $record['class'],
        'type' => 'exception',
        'status' => 'open',
        'occurrence_count' => 1,
    ]);
});

test('HandleExceptionTelemetry increments occurrence count on repeated exception', function () {
    Queue::fake();

    $environment = Environment::factory()->create();
    $environment->load('project.organization');

    $record = exceptionRecord([
        'class' => 'App\\Exceptions\\RepeatedEx'.uniqid(),
        'message' => 'Same error',
        'file' => '/app/Foo.php',
        'line' => 10,
    ]);

    $listener = app(HandleExceptionTelemetry::class);
    $event = new TelemetryBatchIngested($environment->id, [$record]);

    $listener->handle($event);
    $listener->handle($event);

    expect(Issue::where('environment_id', $environment->id)->where('title', $record['class'])->count())->toBe(1);

    $issue = Issue::where('environment_id', $environment->id)->where('title', $record['class'])->first();
    expect($issue->occurrence_count)->toBe(2);
});

test('HandleExceptionTelemetry ignores non-exception records', function () {
    Queue::fake();

    $environment = Environment::factory()->create();

    $record = [
        'v' => 1,
        't' => 'request',
        'timestamp' => now()->timestamp,
        'deploy' => 'abc123',
        'server' => 'web-01',
        'method' => 'GET',
        'url' => 'https://example.com/test',
        'status_code' => 200,
        'duration' => 100,
    ];

    $before = Issue::where('environment_id', $environment->id)->count();

    $listener = app(HandleExceptionTelemetry::class);
    $listener->handle(new TelemetryBatchIngested($environment->id, [$record]));

    expect(Issue::where('environment_id', $environment->id)->count())->toBe($before);
});

test('HandleExceptionTelemetry does nothing when environment does not exist', function () {
    Queue::fake();

    $before = Issue::count();

    $listener = app(HandleExceptionTelemetry::class);
    $listener->handle(new TelemetryBatchIngested(999999, [exceptionRecord()]));

    expect(Issue::count())->toBe($before);
});
