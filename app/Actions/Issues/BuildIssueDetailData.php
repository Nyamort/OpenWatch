<?php

namespace App\Actions\Issues;

use App\Models\Issue;

class BuildIssueDetailData
{
    /**
     * Build the issue detail data including sources, activities, and comments.
     *
     * @return array<string, mixed>
     */
    public function handle(Issue $issue): array
    {
        $issue->load([
            'sources',
            'assignee:id,name,email',
            'activities' => fn ($q) => $q->with('actor:id,name,email')->latest('created_at')->limit(5),
        ]);

        $comments = $issue->comments()
            ->with('author:id,name,email')
            ->latest()
            ->limit(5)
            ->get();

        return [
            'issue' => $issue,
            'comments' => $comments,
        ];
    }
}
