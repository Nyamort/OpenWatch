import { ChevronLeft, ChevronRight } from 'lucide-react';
import type { Pagination } from '@/types/analytics';

interface TablePaginationProps {
    pagination: Pagination;
    onPage: (page: number) => void;
}

export function TablePagination({ pagination, onPage }: TablePaginationProps) {
    if (pagination.last_page <= 1) return null;

    const from = (pagination.current_page - 1) * pagination.per_page + 1;
    const to = Math.min(pagination.current_page * pagination.per_page, pagination.total);

    return (
        <div className="flex items-center justify-between text-sm text-muted-foreground">
            <span>{from}–{to} of {pagination.total}</span>
            <div className="flex items-center gap-1">
                <button
                    onClick={() => onPage(pagination.current_page - 1)}
                    disabled={pagination.current_page === 1}
                    className="flex size-7 items-center justify-center rounded border border-border transition-colors hover:bg-muted disabled:opacity-40"
                >
                    <ChevronLeft className="size-4" />
                </button>
                <span className="px-2 tabular-nums">{pagination.current_page} / {pagination.last_page}</span>
                <button
                    onClick={() => onPage(pagination.current_page + 1)}
                    disabled={pagination.current_page === pagination.last_page}
                    className="flex size-7 items-center justify-center rounded border border-border transition-colors hover:bg-muted disabled:opacity-40"
                >
                    <ChevronRight className="size-4" />
                </button>
            </div>
        </div>
    );
}
