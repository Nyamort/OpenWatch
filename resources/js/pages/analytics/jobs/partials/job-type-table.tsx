import { router } from '@inertiajs/react';
import type { VariantProps } from 'class-variance-authority';
import { ArrowUpRight } from 'lucide-react';
import { SortableHead } from '@/components/analytics/table/sortable-head';
import { TablePagination } from '@/components/analytics/table/table-pagination';
import { Badge, type badgeVariants } from '@/components/ui/badge';
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
import { show as jobShow } from '@/routes/analytics/jobs';
import type {
    JobAttemptRow,
    JobTypeSortKey,
    Pagination,
    SortDir,
} from '../types';

interface JobTypeTableProps {
    attempts: JobAttemptRow[];
    pagination: Pagination;
    sort: JobTypeSortKey;
    direction: SortDir;
    environment: string;
}

const statusVariant: Record<
    string,
    VariantProps<typeof badgeVariants>['variant']
> = {
    processed: 'success',
    released: 'warning',
    failed: 'destructive',
};

export function JobTypeTable({
    attempts,
    pagination,
    sort,
    direction,
    environment,
}: JobTypeTableProps) {
    const { handlePage, handleSort } = useAnalyticsTable<JobTypeSortKey>({
        search: '',
        only: ['attempts', 'pagination', 'sort', 'direction'],
    });

    const onSort = (col: string) =>
        handleSort(col as JobTypeSortKey, sort, direction);

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
                            className="h-11 w-px px-5 text-xs font-medium whitespace-nowrap"
                        >
                            Date
                        </SortableHead>
                        <TableHead className="h-11 w-px px-4 text-xs font-medium tracking-wide whitespace-nowrap text-muted-foreground uppercase">
                            Connection
                        </TableHead>
                        <TableHead className="h-11 w-px px-4 text-xs font-medium tracking-wide whitespace-nowrap text-muted-foreground uppercase">
                            Queue
                        </TableHead>
                        <SortableHead
                            column="attempt"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="center"
                            className="h-11 w-px px-4 text-center text-xs font-medium whitespace-nowrap"
                        >
                            Attempt
                        </SortableHead>
                        <SortableHead
                            column="status"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            className="h-11 px-4 text-xs font-medium"
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
                    {attempts.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell
                                colSpan={7}
                                className="py-12 text-center text-sm text-muted-foreground"
                            >
                                No attempts recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        attempts.map((row) => (
                            <TableRow
                                key={row.id}
                                onClick={() =>
                                    router.visit(
                                        jobShow.url({
                                            environment,
                                            job: 0,
                                            attempt: row.attempt_id,
                                        }),
                                    )
                                }
                                className="group/row cursor-pointer border-0 bg-surface shadow-sm shadow-black/4 hover:bg-transparent [&_td]:border-y [&_td]:border-border [&_td]:bg-surface [&_td]:transition-colors [&_td]:duration-150 hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td:first-child]:rounded-l-lg [&_td:first-child]:border-l [&_td:last-child]:rounded-r-lg [&_td:last-child]:border-r"
                            >
                                <TableCell className="h-11 px-5 text-sm whitespace-nowrap text-muted-foreground tabular-nums">
                                    {row.recorded_at}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 whitespace-nowrap">
                                    <span className="font-mono text-xs">
                                        {row.connection}
                                    </span>
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 whitespace-nowrap">
                                    <span className="font-mono text-xs">
                                        {row.queue}
                                    </span>
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-center tabular-nums">
                                    {row.attempt}
                                </TableCell>
                                <TableCell className="h-11 px-4">
                                    <Badge
                                        variant={
                                            statusVariant[row.status] ??
                                            'secondary'
                                        }
                                    >
                                        {row.status}
                                    </Badge>
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
