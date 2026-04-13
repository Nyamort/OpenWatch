<?php

namespace App\Data;

readonly class IssueListData
{
    /**
     * @param  list<array<string, mixed>>  $issues
     * @param  array{current_page: int, last_page: int, per_page: int, total: int}  $pagination
     * @param  array{status: ?string, type: ?string, assignee_id: ?string, search: ?string, priority: ?string, sort: string, direction: string}  $filters
     */
    public function __construct(
        public readonly array $issues,
        public readonly array $pagination,
        public readonly array $filters,
    ) {}
}
