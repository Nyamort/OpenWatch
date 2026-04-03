import { router, usePage } from '@inertiajs/react';
import cronstrue from 'cronstrue';
import { ArrowUpRight, CalendarClock, OctagonAlert } from 'lucide-react';
import { AnalyticsTableHeader } from '@/components/analytics/table/analytics-table-header';
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
import { useAnalyticsHref } from '@/hooks/use-analytics-href';
import { useAnalyticsTable } from '@/hooks/use-analytics-table';
import { formatDuration } from '@/lib/utils';
import { show as scheduledTaskShow } from '@/routes/analytics/scheduled-tasks';
import type {
    Pagination,
    ScheduledTaskRow,
    ScheduledTaskSortKey,
    SortDir,
} from '../types';

interface ScheduledTaskTableProps {
    tasks: ScheduledTaskRow[];
    pagination: Pagination;
    sort: ScheduledTaskSortKey;
    direction: SortDir;
    search: string;
}

export function ScheduledTaskTable({
    tasks,
    pagination,
    sort,
    direction,
    search,
}: ScheduledTaskTableProps) {
    const { searchValue, handleSearch, handlePage, handleSort } =
        useAnalyticsTable<ScheduledTaskSortKey>({
            search,
            only: ['tasks', 'pagination', 'sort', 'direction'],
        });

    const onSort = (col: string) =>
        handleSort(col as ScheduledTaskSortKey, sort, direction);

    const { props } = usePage();
    const { activeOrganization, activeProject, activeEnvironment } = props as {
        activeOrganization?: { slug: string } | null;
        activeProject?: { slug: string } | null;
        activeEnvironment?: { slug: string } | null;
    };
    const analyticsHref = useAnalyticsHref();

    const showHref = (row: ScheduledTaskRow) => {
        if (!activeOrganization || !activeProject || !activeEnvironment) {
            return '#';
        }

        return analyticsHref(
            scheduledTaskShow.url(
                {
                    organization: activeOrganization.slug,
                    project: activeProject.slug,
                    environment: activeEnvironment.slug,
                    scheduledTask: 0,
                },
                { query: { name: row.name ?? '', cron: row.cron ?? '' } },
            ),
        );
    };

    return (
        <div className="flex flex-col gap-3">
            <AnalyticsTableHeader
                icon={CalendarClock}
                label="Scheduled Tasks"
                count={pagination.total}
                search={searchValue}
                searchPlaceholder="Search tasks..."
                onSearch={handleSearch}
            />
            <Table
                className="border-separate border-spacing-y-1.5"
                containerClassName="overflow-x-visible"
            >
                <TableHeader className="sticky top-16 z-10 backdrop-blur-sm [&_tr]:border-0">
                    <TableRow className="border-0 shadow-sm shadow-black/4 hover:bg-transparent [&_th]:border-y [&_th]:border-border [&_th]:bg-muted/50 [&_th:first-child]:rounded-l-lg [&_th:first-child]:border-l [&_th:last-child]:rounded-r-lg [&_th:last-child]:border-r">
                        <SortableHead
                            column="task"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            className="h-11 px-5 text-xs font-medium"
                        >
                            Task
                        </SortableHead>
                        <TableHead className="h-11 w-px px-4 text-xs font-medium tracking-wide whitespace-nowrap text-muted-foreground uppercase">
                            Schedule
                        </TableHead>
                        <TableHead className="h-11 w-px px-4 text-xs font-medium tracking-wide whitespace-nowrap text-muted-foreground uppercase">
                            Next Run
                        </TableHead>
                        <SortableHead
                            column="processed"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            Processed
                        </SortableHead>
                        <SortableHead
                            column="skipped"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            Skipped
                        </SortableHead>
                        <SortableHead
                            column="failed"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            Failed
                        </SortableHead>
                        <SortableHead
                            column="total"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            Total
                        </SortableHead>
                        <SortableHead
                            column="avg"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            AVG
                        </SortableHead>
                        <SortableHead
                            column="p95"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            P95
                        </SortableHead>
                        <TableHead className="h-11 w-px rounded-r-lg border-y border-r border-border bg-muted/50 pr-5 whitespace-nowrap" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {tasks.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell
                                colSpan={10}
                                className="py-12 text-center text-sm text-muted-foreground"
                            >
                                No scheduled tasks recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        tasks.map((row, i) => (
                            <TableRow
                                key={i}
                                onClick={() => router.visit(showHref(row))}
                                className="group/row cursor-pointer border-0 bg-surface shadow-sm shadow-black/4 hover:bg-transparent [&_td]:border-y [&_td]:border-border [&_td]:bg-surface [&_td]:transition-colors [&_td]:duration-150 hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td:first-child]:rounded-l-lg [&_td:first-child]:border-l [&_td:last-child]:rounded-r-lg [&_td:last-child]:border-r"
                            >
                                <TableCell className="h-11 overflow-hidden px-5">
                                    <span className="truncate font-mono text-sm">
                                        {row.name ?? 'Unknown Task'}
                                    </span>
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 whitespace-nowrap">
                                    <span
                                        className="text-sm"
                                        title={row.cron ?? undefined}
                                    >
                                        {row.cron
                                            ? (() => {
                                                  try {
                                                      return cronstrue.toString(
                                                          row.cron,
                                                          {
                                                              verbose: false,
                                                              throwExceptionOnParseError: true,
                                                          },
                                                      );
                                                  } catch {
                                                      return row.cron;
                                                  }
                                              })()
                                            : '—'}
                                    </span>
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-sm whitespace-nowrap tabular-nums">
                                    {row.next_run ?? '—'}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right whitespace-nowrap tabular-nums">
                                    {row.processed.toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right whitespace-nowrap tabular-nums">
                                    {row.skipped.toLocaleString()}
                                </TableCell>
                                <TableCell
                                    className={`h-11 w-px px-4 text-right whitespace-nowrap tabular-nums ${row.failed === 0 ? 'text-muted-foreground' : 'text-red-500'}`}
                                >
                                    <div className="flex items-center justify-end gap-1">
                                        {row.failed > 0 && (
                                            <OctagonAlert className="size-3 shrink-0" />
                                        )}
                                        {row.failed.toLocaleString()}
                                    </div>
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right font-medium whitespace-nowrap tabular-nums">
                                    {row.total.toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right whitespace-nowrap tabular-nums">
                                    {formatDuration(row.avg)}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right whitespace-nowrap tabular-nums">
                                    {formatDuration(row.p95)}
                                </TableCell>
                                <TableCell className="h-11 w-px pr-5">
                                    <div className="flex items-center justify-end">
                                        <span className="flex items-center rounded-sm border border-border/20 bg-muted/30 text-foreground/10 transition-colors group-hover/row:border-border/60 group-hover/row:text-emerald-500 dark:border-white/7 dark:bg-white/1 dark:text-white/10 dark:group-hover/row:border-white/15 dark:group-hover/row:text-emerald-500">
                                            <span className="flex size-6 items-center justify-center">
                                                <ArrowUpRight className="size-4" />
                                            </span>
                                        </span>
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
