<?php

namespace App\Actions\Issues;

use App\Models\Environment;
use App\Models\Issue;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Services\Issues\FingerprintService;

class CreateIssueFromAnalytics
{
    public function __construct(
        private readonly CreateIssue $createIssue,
        private readonly FingerprintService $fingerprintService,
    ) {}

    /**
     * Create or increment an issue from an analytics event.
     *
     * @param  array{
     *   source_type: string,
     *   trace_id?: string|null,
     *   group_key?: string|null,
     *   execution_id?: string|null,
     *   snapshot?: array|null,
     *   class?: string|null,
     *   message?: string|null,
     *   file?: string|null,
     *   line?: int|null,
     *   method?: string|null,
     *   route_path?: string|null,
     *   status_code?: int|null,
     *   job_class?: string|null,
     *   queue?: string|null,
     * } $data
     */
    public function handle(
        Organization $organization,
        Project $project,
        Environment $environment,
        ?User $actor,
        array $data,
    ): Issue {
        $sourceType = $data['source_type'];

        $fingerprint = match ($sourceType) {
            'exception' => $data['group_key'] ?? $this->fingerprintService->forException(
                $data['class'] ?? 'UnknownException',
                $data['message'] ?? '',
                $data['file'] ?? null,
                $data['line'] ?? null,
            ),
            'request' => $this->fingerprintService->forRequest(
                $data['method'] ?? 'GET',
                $data['route_path'] ?? '/',
                $data['status_code'] ?? 500,
            ),
            'job' => $this->fingerprintService->forJob(
                $data['job_class'] ?? 'UnknownJob',
                $data['queue'] ?? 'default',
            ),
            default => hash('sha256', $sourceType.'|'.($data['group_key'] ?? uniqid())),
        };

        $title = match ($sourceType) {
            'exception' => $data['class'] ?? 'Exception',
            'request' => sprintf('%s %s returned %d', strtoupper($data['method'] ?? 'GET'), $data['route_path'] ?? '/', $data['status_code'] ?? 500),
            'job' => $data['job_class'] ?? 'Job Failure',
            default => "Issue from {$sourceType}",
        };

        $type = match ($sourceType) {
            'request' => 'performance',
            'job' => 'other',
            default => 'exception',
        };

        return $this->createIssue->handle($organization, $project, $environment, $actor, [
            'title' => $title,
            'fingerprint' => $fingerprint,
            'type' => $type,
            'source_type' => $sourceType,
            'trace_id' => $data['trace_id'] ?? null,
            'group_key' => $data['group_key'] ?? null,
            'execution_id' => $data['execution_id'] ?? null,
            'snapshot' => $data['snapshot'] ?? null,
        ]);
    }
}
