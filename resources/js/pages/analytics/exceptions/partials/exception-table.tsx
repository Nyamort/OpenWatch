import { router, usePage } from '@inertiajs/react';
import { formatDistanceToNow, parseISO } from 'date-fns';
import { AlertCircle, ArrowUpRight } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
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
import { useAnalyticsTable } from '@/hooks/use-analytics-table';
import { show } from '@/routes/analytics/exceptions';
import type {
    ExceptionRow,
    ExceptionSortKey,
    Pagination,
    SortDir,
} from '../types';

interface ExceptionTableProps {
    exceptions: ExceptionRow[];
    pagination: Pagination;
    sort: ExceptionSortKey;
    direction: SortDir;
    search: string;
}

export function ExceptionTable({
    exceptions,
    pagination,
    sort,
    direction,
    search,
}: ExceptionTableProps) {
    const { props } = usePage();
    const { activeEnvironment } =
        props as unknown as {
            activeEnvironment: { slug: string };
        };

    const { searchValue, handleSearch, handlePage, handleSort } =
        useAnalyticsTable<ExceptionSortKey>({
            search,
            only: ['exceptions', 'pagination', 'sort', 'direction'],
        });

    const onSort = (col: string) =>
        handleSort(col as ExceptionSortKey, sort, direction);

    return (
        <div className="flex flex-col gap-3">
            <AnalyticsTableHeader
                icon={AlertCircle}
                label="Exceptions"
                count={pagination.total}
                search={searchValue}
                searchPlaceholder="Search exceptions..."
                onSearch={handleSearch}
            />
            <Table
                className="border-separate border-spacing-y-1.5"
                containerClassName="overflow-x-visible"
            >
                <TableHeader className="sticky top-16 z-10 backdrop-blur-sm [&_tr]:border-0">
                    <TableRow className="border-0 shadow-sm shadow-black/4 hover:bg-transparent [&_th]:border-y [&_th]:border-border [&_th]:bg-muted/50 [&_th:first-child]:rounded-l-lg [&_th:first-child]:border-l [&_th:last-child]:rounded-r-lg [&_th:last-child]:border-r">
                        <SortableHead
                            column="last_seen"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            className="h-11 w-px pl-5 text-xs font-medium whitespace-nowrap"
                        >
                            Last Seen
                        </SortableHead>
                        <SortableHead
                            column="class"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            className="h-11 px-4 text-xs font-medium"
                        >
                            Exception
                        </SortableHead>
                        <SortableHead
                            column="count"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            Count
                        </SortableHead>
                        <SortableHead
                            column="users"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            Users
                        </SortableHead>
                        <TableHead className="h-11 w-px pr-5" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {exceptions.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell
                                colSpan={5}
                                className="py-12 text-center text-sm text-muted-foreground"
                            >
                                No exceptions recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        exceptions.map((row) => (
                            <TableRow
                                key={row.group_key}
                                onClick={() =>
                                    router.visit(
                                        show.url({
                                            environment: activeEnvironment.slug,
                                            group: row.group_key,
                                        }),
                                    )
                                }
                                className="group/row cursor-pointer border-0 bg-surface shadow-sm shadow-black/4 hover:bg-transparent [&_td]:border-y [&_td]:border-border [&_td]:bg-surface [&_td]:transition-colors [&_td]:duration-150 hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td:first-child]:rounded-l-lg [&_td:first-child]:border-l [&_td:last-child]:rounded-r-lg [&_td:last-child]:border-r"
                            >
                                <TableCell className="h-11 w-px pr-4 pl-5 text-sm whitespace-nowrap text-muted-foreground tabular-nums">
                                    {formatDistanceToNow(
                                        parseISO(row.last_seen),
                                        {
                                            addSuffix: true,
                                        },
                                    )}
                                </TableCell>
                                <TableCell className="overflow-hidden px-4 py-2">
                                    <div className="flex min-w-0 items-start gap-2">
                                        <Badge
                                            variant={row.handled ? 'secondary' : 'destructive'}
                                            className="mt-0.5 shrink-0"
                                        >
                                            {row.handled ? 'Handled' : 'Unhandled'}
                                        </Badge>
                                        <div className="flex min-w-0 flex-col">
                                            <span className="truncate font-mono text-sm">
                                                {row.class}
                                            </span>
                                            {row.message && (
                                                <span className="truncate text-xs text-muted-foreground">
                                                    {row.message}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right font-medium whitespace-nowrap tabular-nums">
                                    {row.count.toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right whitespace-nowrap tabular-nums">
                                    {row.users.toLocaleString()}
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
