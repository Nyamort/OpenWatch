<?php

namespace App\Actions\Issues;

use App\Data\IssueListData;
use App\Models\Environment;
use App\Models\Issue;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\Request;

class BuildIssueListData
{
    /**
     * Build the paginated issue list data with filters applied.
     */
    public function handle(Organization $organization, Project $project, Environment $environment, Request $request): IssueListData
    {
        $filter = $request->input('filter');
        $type = $request->input('type');
        $search = $request->input('search');
        $priority = $request->input('priority');
        $sort = $request->input('sort', 'last_seen_at');
        $direction = $request->input('direction', 'desc');

        $currentUserId = auth()->id();

        // Base scope without status/assignee filter — shared for counts and list
        $baseQuery = Issue::query()
            ->where('organization_id', $organization->id)
            ->where('project_id', $project->id)
            ->where('environment_id', $environment->id);

        if ($type) {
            $baseQuery->where('type', $type);
        }

        if ($search) {
            $baseQuery->where('title', 'like', '%'.$search.'%');
        }

        if ($priority) {
            $baseQuery->where('priority', $priority);
        }

        // Single query to count all filter buckets at once
        $countsRow = (clone $baseQuery)->selectRaw(
            "SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
             SUM(CASE WHEN status = 'open' AND assignee_id IS NULL THEN 1 ELSE 0 END) as unassigned_count,
             SUM(CASE WHEN assignee_id = ? THEN 1 ELSE 0 END) as mine_count,
             SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
             SUM(CASE WHEN status = 'ignored' THEN 1 ELSE 0 END) as ignored_count",
            [$currentUserId]
        )->first();

        $filterCounts = [
            'open' => (int) ($countsRow->open_count ?? 0),
            'unassigned' => (int) ($countsRow->unassigned_count ?? 0),
            'mine' => (int) ($countsRow->mine_count ?? 0),
            'resolved' => (int) ($countsRow->resolved_count ?? 0),
            'ignored' => (int) ($countsRow->ignored_count ?? 0),
        ];

        // Main list query — clone base scope then apply status filter + eager loads
        $query = (clone $baseQuery)->with(['assignee:id,name,email', 'detail']);

        if ($filter !== null) {
            match ($filter) {
                'unassigned' => $query->where('status', 'open')->whereNull('assignee_id'),
                'mine' => $query->where('assignee_id', $currentUserId),
                'resolved' => $query->where('status', 'resolved'),
                'ignored' => $query->where('status', 'ignored'),
                default => $query->where('status', 'open'),
            };
            $activeFilter = $filter;
        } else {
            // Legacy support: status and assignee_id query params
            $status = $request->input('status', 'open');
            $assigneeId = $request->input('assignee_id');

            if ($status) {
                $query->where('status', $status);
            }

            if ($assigneeId) {
                $query->where('assignee_id', $assigneeId);
            }

            $activeFilter = match ($status) {
                'resolved' => 'resolved',
                'ignored' => 'ignored',
                default => 'open',
            };
        }

        $allowedSorts = ['id', 'priority', 'last_seen_at', 'occurrence_count', 'first_seen_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'last_seen_at';
        }

        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';

        $query->orderBy($sort, $direction);

        $paginator = $query->paginate(25);

        return new IssueListData(
            issues: $paginator->items(),
            pagination: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
            filters: [
                'filter' => $activeFilter,
                'type' => $type,
                'search' => $search,
                'priority' => $priority,
                'sort' => $sort,
                'direction' => $direction,
            ],
            filterCounts: $filterCounts,
        );
    }
}
