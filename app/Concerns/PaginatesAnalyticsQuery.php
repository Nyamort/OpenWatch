<?php

namespace App\Concerns;

trait PaginatesAnalyticsQuery
{
    protected int $analyticsPerPage = 25;

    /**
     * Resolve the SQL column name for a sort key against an allowlist.
     *
     * @param  array<string, string>  $allowed  Map of sort key → SQL column/alias
     */
    protected function resolveSort(string $sort, array $allowed, string $default): string
    {
        return $allowed[$sort] ?? $allowed[$default] ?? reset($allowed);
    }

    /**
     * Build pagination metadata to pass to the frontend.
     *
     * @return array{total: int, per_page: int, current_page: int, last_page: int}
     */
    protected function buildPaginationMeta(int $total, int $page): array
    {
        return [
            'total' => $total,
            'per_page' => $this->analyticsPerPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $this->analyticsPerPage),
        ];
    }

    /**
     * Offset for the current page.
     */
    protected function pageOffset(int $page): int
    {
        return ($page - 1) * $this->analyticsPerPage;
    }
}
