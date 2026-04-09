<?php

namespace App\Listeners;

use App\Actions\Issues\CreateIssueFromAnalytics;
use App\Events\TelemetryBatchIngested;
use App\Models\Environment;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleExceptionTelemetry implements ShouldQueue
{
    public function __construct(private readonly CreateIssueFromAnalytics $createIssue) {}

    public function handle(TelemetryBatchIngested $event): void
    {
        $exceptionRecords = array_values(array_filter(
            $event->records,
            fn (array $record) => ($record['t'] ?? null) === 'exception',
        ));

        if (empty($exceptionRecords)) {
            return;
        }

        $environment = Environment::query()
            ->with('project.organization')
            ->find($event->environmentId);

        if ($environment === null) {
            return;
        }

        $project = $environment->project;
        $organization = $project->organization;

        foreach ($exceptionRecords as $record) {
            $this->createIssue->handle(
                $organization,
                $project,
                $environment,
                null,
                [
                    'source_type' => 'exception',
                    'class' => $record['class'] ?? null,
                    'message' => $record['message'] ?? null,
                    'file' => $record['file'] ?? null,
                    'line' => isset($record['line']) ? (int) $record['line'] : null,
                    'group_key' => $record['_group'] ?? null,
                    'trace_id' => $record['trace_id'] ?? null,
                    'execution_id' => $record['execution_id'] ?? null,
                ],
            );
        }
    }
}
