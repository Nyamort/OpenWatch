<?php

namespace App\Actions\Issues;

use App\Models\AlertRule;
use App\Models\Issue;
use App\Models\User;

class CreateIssueFromAlert
{
    public function __construct(
        private readonly CreateIssue $createIssue,
    ) {}

    /**
     * Create an issue of type 'performance' from an alert rule trigger.
     */
    public function handle(AlertRule $alertRule, User $actor): Issue
    {
        $fingerprint = hash('sha256', 'alert:'.$alertRule->id);

        $alertRule->load(['organization', 'project', 'environment']);

        return $this->createIssue->handle(
            $alertRule->organization,
            $alertRule->project,
            $alertRule->environment,
            $actor,
            [
                'title' => 'Alert: '.$alertRule->name,
                'fingerprint' => $fingerprint,
                'type' => 'performance',
            ],
        );
    }
}
