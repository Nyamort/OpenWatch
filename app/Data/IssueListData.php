<?php

namespace App\Data;

readonly class IssueListData
{
    /**
     * @param  list<array<string, mixed>>  $issues
     * @param  array{current_page: int, last_page: int, per_page: int, total: int}  $pagination
     * @param  array{filter: string, type: ?string, search: ?string, priority: ?string, sort: string, direction: string}  $filters
     * @param  array{open: int, unassigned: int, mine: int, resolved: int, ignored: int}  $filterCounts
     */
    public function __construct(
        public readonly array $issues,
        public readonly array $pagination,
        public readonly array $filters,
        public readonly array $filterCounts,
    ) {}
}
