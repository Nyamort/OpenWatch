<?php

namespace App\Actions\Issues;

use App\Events\IssueCreated;
use App\Models\Environment;
use App\Models\Issue;
use App\Models\IssueActivity;
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
     *   fingerprint: string,
     *   type?: string,
     *   priority?: string,
     *   source_type?: string,
     *   trace_id?: string|null,
     *   group_key?: string|null,
     *   execution_id?: string|null,
     *   snapshot?: array|null,
     * } $data
     */
    public function handle(
        Organization $organization,
        Project $project,
        Environment $environment,
        User $actor,
        array $data,
    ): Issue {
        return DB::transaction(function () use ($organization, $project, $environment, $actor, $data): Issue {
            $existing = Issue::query()
                ->where('organization_id', $organization->id)
                ->where('project_id', $project->id)
                ->where('environment_id', $environment->id)
                ->where('fingerprint', $data['fingerprint'])
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();

            if ($existing !== null) {
                $existing->increment('occurrence_count');
                $existing->update(['last_seen_at' => now()]);

                if (! empty($data['source_type'])) {
                    IssueSource::create([
                        'issue_id' => $existing->id,
                        'source_type' => $data['source_type'],
                        'trace_id' => $data['trace_id'] ?? null,
                        'group_key' => $data['group_key'] ?? null,
                        'execution_id' => $data['execution_id'] ?? null,
                        'snapshot' => $data['snapshot'] ?? null,
                        'created_at' => now(),
                    ]);
                }

                return $existing;
            }

            $issue = Issue::create([
                'organization_id' => $organization->id,
                'project_id' => $project->id,
                'environment_id' => $environment->id,
                'title' => $data['title'],
                'fingerprint' => $data['fingerprint'],
                'type' => $data['type'] ?? 'exception',
                'status' => 'open',
                'priority' => $data['priority'] ?? 'medium',
                'occurrence_count' => 1,
                'first_seen_at' => now(),
                'last_seen_at' => now(),
            ]);

            IssueActivity::create([
                'issue_id' => $issue->id,
                'actor_id' => $actor->id,
                'type' => 'created',
                'metadata' => null,
                'created_at' => now(),
            ]);

            if (! empty($data['source_type'])) {
                IssueSource::create([
                    'issue_id' => $issue->id,
                    'source_type' => $data['source_type'],
                    'trace_id' => $data['trace_id'] ?? null,
                    'group_key' => $data['group_key'] ?? null,
                    'execution_id' => $data['execution_id'] ?? null,
                    'snapshot' => $data['snapshot'] ?? null,
                    'created_at' => now(),
                ]);
            }

            IssueCreated::dispatch($issue, $actor);

            return $issue;
        });
    }
}
