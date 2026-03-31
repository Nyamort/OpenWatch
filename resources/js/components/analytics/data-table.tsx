import { router, usePage } from '@inertiajs/react';
import { EmptyState } from './empty-state';

interface Column<T> {
    key: string;
    label: string;
    sortable?: boolean;
    render?: (value: unknown, row: T) => React.ReactNode;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface DataTableProps<T extends Record<string, unknown>> {
    columns: Column<T>[];
    rows: T[];
    pagination?: Pagination | null;
    sortKey?: string;
    sortDirection?: string;
}

export function DataTable<T extends Record<string, unknown>>({
    columns,
    rows,
    pagination,
    sortKey,
    sortDirection,
}: DataTableProps<T>) {
    const { url } = usePage();

    function handleSort(key: string) {
        const urlObj = new URL(url, window.location.origin);
        const currentSort = urlObj.searchParams.get('sort');
        const currentDir = urlObj.searchParams.get('direction') ?? 'desc';

        if (currentSort === key) {
            urlObj.searchParams.set(
                'direction',
                currentDir === 'asc' ? 'desc' : 'asc',
            );
        } else {
            urlObj.searchParams.set('sort', key);
            urlObj.searchParams.set('direction', 'desc');
        }

        router.get(
            urlObj.pathname + urlObj.search,
            {},
            { preserveScroll: true },
        );
    }

    function handlePage(page: number) {
        const urlObj = new URL(url, window.location.origin);
        urlObj.searchParams.set('page', String(page));
        router.get(
            urlObj.pathname + urlObj.search,
            {},
            { preserveScroll: true },
        );
    }

    if (rows.length === 0) {
        return <EmptyState />;
    }

    return (
        <div className="space-y-4">
            <div className="overflow-x-auto rounded-lg border">
                <table className="w-full text-sm">
                    <thead className="border-b bg-muted/50">
                        <tr>
                            {columns.map((col) => (
                                <th
                                    key={col.key}
                                    className={`px-4 py-3 text-left font-medium text-muted-foreground ${col.sortable ? 'cursor-pointer select-none hover:text-foreground' : ''}`}
                                    onClick={
                                        col.sortable
                                            ? () => handleSort(col.key)
                                            : undefined
                                    }
                                >
                                    <span className="flex items-center gap-1">
                                        {col.label}
                                        {col.sortable &&
                                            sortKey === col.key && (
                                                <span className="text-xs">
                                                    {sortDirection === 'asc'
                                                        ? '↑'
                                                        : '↓'}
                                                </span>
                                            )}
                                    </span>
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y">
                        {rows.map((row, i) => (
                            <tr key={i} className="hover:bg-muted/30">
                                {columns.map((col) => (
                                    <td key={col.key} className="px-4 py-3">
                                        {col.render
                                            ? col.render(row[col.key], row)
                                            : String(row[col.key] ?? '')}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
            {pagination && pagination.last_page > 1 && (
                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <span>
                        Page {pagination.current_page} of {pagination.last_page}{' '}
                        ({pagination.total} total)
                    </span>
                    <div className="flex gap-2">
                        <button
                            onClick={() =>
                                handlePage(pagination.current_page - 1)
                            }
                            disabled={pagination.current_page <= 1}
                            className="rounded border px-3 py-1 disabled:opacity-50"
                        >
                            Previous
                        </button>
                        <button
                            onClick={() =>
                                handlePage(pagination.current_page + 1)
                            }
                            disabled={
                                pagination.current_page >= pagination.last_page
                            }
                            className="rounded border px-3 py-1 disabled:opacity-50"
                        >
                            Next
                        </button>
                    </div>
                </div>
            )}
        </div>
    );
}
