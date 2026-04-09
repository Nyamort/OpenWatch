<?php

namespace App\Listeners;

use App\Actions\Issues\CreateIssueFromAnalytics;
use App\Events\TelemetryBatchIngested;
use App\Models\Environment;
use App\Services\Ingestion\DTOs\ExceptionRecordDTO;
use Illuminate\Contracts\Queue\ShouldQueue;

class HandleExceptionTelemetry implements ShouldQueue
{
    public function __construct(private readonly CreateIssueFromAnalytics $createIssue) {}

    public function handle(TelemetryBatchIngested $event): void
    {
        $exceptionDtos = array_values(array_filter(
            $event->records,
            fn ($record) => $record instanceof ExceptionRecordDTO,
        ));

        if (empty($exceptionDtos)) {
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

        foreach ($exceptionDtos as $dto) {
            $this->createIssue->handle(
                $organization,
                $project,
                $environment,
                null,
                [
                    'source_type' => 'exception',
                    'class' => $dto->class,
                    'message' => $dto->message,
                    'file' => $dto->file,
                    'line' => $dto->line,
                    'group_key' => $dto->groupKey,
                    'trace_id' => $dto->traceId,
                    'execution_id' => $dto->executionId,
                ],
            );
        }
    }
}
