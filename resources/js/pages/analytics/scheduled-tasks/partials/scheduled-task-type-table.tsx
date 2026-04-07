import { router } from '@inertiajs/react';
import { ArrowUpRight, OctagonAlert, TriangleAlert } from 'lucide-react';
import { SortableHead } from '@/components/analytics/table/sortable-head';
import { TablePagination } from '@/components/analytics/table/table-pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useAnalyticsTable } from '@/hooks/use-analytics-table';
import { formatDuration } from '@/lib/utils';
import { show as scheduledTaskShow } from '@/routes/analytics/scheduled-tasks';
import type {
    Pagination,
    ScheduledTaskTypeSortKey,
    ScheduledTaskRunRow,
    SortDir,
} from '../types';

interface ScheduledTaskTypeTableProps {
    runs: ScheduledTaskRunRow[];
    pagination: Pagination;
    sort: ScheduledTaskTypeSortKey;
    direction: SortDir;
    environment: string;
}

function statusColor(status: string): string {
    if (status === 'failed') {
        return 'text-red-500';
    }

    if (status === 'skipped') {
        return 'text-amber-500';
    }

    return '';
}

function StatusIcon({ status }: { status: string }) {
    if (status === 'failed') {
        return <OctagonAlert className="size-3 shrink-0" />;
    }

    if (status === 'skipped') {
        return <TriangleAlert className="size-3 shrink-0" />;
    }

    return null;
}

export function ScheduledTaskTypeTable({
    runs,
    pagination,
    sort,
    direction,
    environment,
}: ScheduledTaskTypeTableProps) {
    const { handlePage, handleSort } =
        useAnalyticsTable<ScheduledTaskTypeSortKey>({
            search: '',
            only: ['runs', 'pagination', 'sort', 'direction'],
        });

    const onSort = (col: string) =>
        handleSort(col as ScheduledTaskTypeSortKey, sort, direction);

    return (
        <div className="flex flex-col gap-3">
            <Table
                className="border-separate border-spacing-y-1.5"
                containerClassName="overflow-x-visible"
            >
                <TableHeader className="sticky top-16 z-10 backdrop-blur-sm [&_tr]:border-0">
                    <TableRow className="border-0 shadow-sm shadow-black/4 hover:bg-transparent [&_th]:border-y [&_th]:border-border [&_th]:bg-muted/50 [&_th:first-child]:rounded-l-lg [&_th:first-child]:border-l [&_th:last-child]:rounded-r-lg [&_th:last-child]:border-r">
                        <SortableHead
                            column="date"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            className="h-11 px-5 text-xs font-medium"
                        >
                            Date
                        </SortableHead>
                        <SortableHead
                            column="status"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            className="h-11 w-px px-4 text-xs font-medium whitespace-nowrap"
                        >
                            Status
                        </SortableHead>
                        <SortableHead
                            column="duration"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            Duration
                        </SortableHead>
                        <TableHead className="h-11 w-px rounded-r-lg border-y border-r border-border bg-muted/50 pr-5 whitespace-nowrap" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {runs.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell
                                colSpan={4}
                                className="py-12 text-center text-sm text-muted-foreground"
                            >
                                No runs recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        runs.map((row) => (
                            <TableRow
                                key={row.id}
                                className="group/row cursor-pointer border-0 bg-surface shadow-sm shadow-black/4 hover:bg-transparent [&_td]:border-y [&_td]:border-border [&_td]:bg-surface [&_td]:transition-colors [&_td]:duration-150 hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td:first-child]:rounded-l-lg [&_td:first-child]:border-l [&_td:last-child]:rounded-r-lg [&_td:last-child]:border-r"
                                onClick={() => router.visit(scheduledTaskShow.url({ environment, scheduledTask: 0, run: row.id }))}
                            >
                                <TableCell className="h-11 px-5 text-sm whitespace-nowrap text-muted-foreground tabular-nums">
                                    {row.recorded_at}
                                </TableCell>
                                <TableCell
                                    className={`h-11 w-px px-4 whitespace-nowrap ${statusColor(row.status)}`}
                                >
                                    <div className="flex items-center gap-1 capitalize">
                                        <StatusIcon status={row.status} />
                                        {row.status}
                                    </div>
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right whitespace-nowrap tabular-nums">
                                    {formatDuration(row.duration)}
                                </TableCell>
                                <TableCell className="h-11 w-px pr-5">
                                    <div className="flex items-center justify-end">
                                        <div className="flex items-center rounded-sm border border-border/20 bg-muted/30 text-foreground/10 transition-colors group-hover/row:border-border/60 group-hover/row:text-emerald-500 dark:border-white/7 dark:bg-white/1 dark:text-white/10 dark:group-hover/row:border-white/15 dark:group-hover/row:text-emerald-500">
                                            <span className="flex size-6 items-center justify-center">
                                                <ArrowUpRight className="size-4" />
                                            </span>
                                        </div>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))
                    )}
                </TableBody>
            </Table>
            <TablePagination pagination={pagination} onPage={handlePage} />
        </div>
    );
}
