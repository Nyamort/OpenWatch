<?php

namespace App\Actions\Issues;

use App\Models\Environment;
use App\Models\Issue;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\Request;

class BuildIssueListData
{
    /**
     * Build the paginated issue list data with filters applied.
     *
     * @return array{issues: list<array<string, mixed>>, pagination: array<string, mixed>, filters: array<string, mixed>}
     */
    public function handle(Organization $organization, Project $project, Environment $environment, Request $request): array
    {
        $status = $request->input('status', 'open');
        $type = $request->input('type');
        $assigneeId = $request->input('assignee_id');
        $search = $request->input('search');
        $priority = $request->input('priority');
        $sort = $request->input('sort', 'last_seen_at');

        $query = Issue::query()
            ->where('organization_id', $organization->id)
            ->where('project_id', $project->id)
            ->where('environment_id', $environment->id)
            ->with('assignee:id,name,email');

        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
        }

        if ($assigneeId) {
            $query->where('assignee_id', $assigneeId);
        }

        if ($search) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        if ($priority) {
            $query->where('priority', $priority);
        }

        $allowedSorts = ['last_seen_at', 'occurrence_count', 'first_seen_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'last_seen_at';
        }

        $query->orderBy($sort, 'desc');

        $paginator = $query->paginate(25);

        $filters = [
            'status' => $status,
            'type' => $type,
            'assignee_id' => $assigneeId,
            'search' => $search,
            'priority' => $priority,
            'sort' => $sort,
        ];

        return [
            'issues' => $paginator->items(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            'filters' => $filters,
        ];
    }
}
