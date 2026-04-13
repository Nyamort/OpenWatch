<?php

namespace App\Actions\Issues;

use App\Enums\IssuePriority;
use App\Enums\IssueStatus;
use App\Enums\IssueType;
use App\Events\IssueCreated;
use App\Models\Environment;
use App\Models\Issue;
use App\Models\IssueActivity;
use App\Models\IssueExceptionDetail;
use App\Models\IssueSource;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateIssue
{
    /**
     * Create or increment an issue for the given fingerprint.
     *
     * @param  array{
     *   title: string,
     *   subtitle?: string|null,
     *   fingerprint: string,
     *   type?: string,
     *   priority?: string,
     *   source_type?: string,
     *   trace_id?: string|null,
     *   group_key?: string|null,
     *   execution_id?: string|null,
     *   user_identifier?: string|null,
     *   snapshot?: array|null,
     * } $data
     */
    public function handle(
        Organization $organization,
        Project $project,
        Environment $environment,
        ?User $actor,
        array $data,
    ): Issue {
        return DB::transaction(function () use ($organization, $project, $environment, $actor, $data): Issue {
            $existing = Issue::query()
                ->where('organization_id', $organization->id)
                ->where('project_id', $project->id)
                ->where('environment_id', $environment->id)
                ->where('fingerprint', $data['fingerprint'])
                ->where('status', IssueStatus::Open)
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                $existing->increment('occurrence_count');
                $existing->update(['last_seen_at' => now()]);

                $userIdentifier = $data['user_identifier'] ?? null;

                if ($userIdentifier !== null && $existing->detail instanceof IssueExceptionDetail) {
                    $alreadySeen = IssueSource::query()
                        ->where('issue_id', $existing->id)
                        ->where('user_identifier', $userIdentifier)
                        ->exists();

                    if (! $alreadySeen) {
                        $existing->detail->increment('user_count');
                    }
                }

                $this->createSource($existing, $data);

                return $existing;
            }

            $type = IssueType::tryFrom($data['type'] ?? '') ?? IssueType::Exception;
            $priority = IssuePriority::tryFrom($data['priority'] ?? '') ?? IssuePriority::None;
            $userIdentifier = $data['user_identifier'] ?? null;

            $issue = Issue::create([
                'organization_id' => $organization->id,
                'project_id' => $project->id,
                'environment_id' => $environment->id,
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'fingerprint' => $data['fingerprint'],
                'type' => $type,
                'status' => IssueStatus::Open,
                'priority' => $priority,
                'occurrence_count' => 1,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]);

            IssueActivity::create([
                'issue_id' => $issue->id,
                'actor_id' => $actor?->id,
                'type' => 'created',
                'metadata' => null,
                'created_at' => now(),
            ]);

            $this->createSource($issue, $data);

            if ($type === IssueType::Exception) {
                $detail = IssueExceptionDetail::create([
                    'user_count' => $userIdentifier !== null ? 1 : 0,
                ]);

                $issue->update([
                    'detail_type' => IssueExceptionDetail::class,
                    'detail_id' => $detail->id,
                ]);
            }

            IssueCreated::dispatch($issue, $actor);

            return $issue;
        });
    }

    private function createSource(Issue $issue, array $data): void
    {
        if (empty($data['source_type'])) {
            return;
        }

        IssueSource::create([
            'issue_id' => $issue->id,
            'source_type' => $data['source_type'],
            'trace_id' => $data['trace_id'] ?? null,
            'group_key' => $data['group_key'] ?? null,
            'execution_id' => $data['execution_id'] ?? null,
            'user_identifier' => $data['user_identifier'] ?? null,
            'snapshot' => $data['snapshot'] ?? null,
            'created_at' => now(),
        ]);
    }
}
