import { router, usePage } from '@inertiajs/react';
import { ArrowUpRight, Globe, OctagonAlert, TriangleAlert } from 'lucide-react';
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
import { formatDuration } from '@/lib/utils';
import { host as hostRoute } from '@/routes/analytics/outgoing-requests';
import type {
    OutgoingRequestHostRow,
    OutgoingRequestSortKey,
    Pagination,
    SortDir,
} from '../types';

interface OutgoingRequestTableProps {
    hosts: OutgoingRequestHostRow[];
    pagination: Pagination;
    sort: OutgoingRequestSortKey;
    direction: SortDir;
    search: string;
}

export function OutgoingRequestTable({
    hosts,
    pagination,
    sort,
    direction,
    search,
}: OutgoingRequestTableProps) {
    const { props } = usePage();
    const { activeOrganization, activeProject, activeEnvironment } =
        props as unknown as {
            activeOrganization: { slug: string };
            activeProject: { slug: string };
            activeEnvironment: { slug: string };
        };

    const { searchValue, handleSearch, handlePage, handleSort } =
        useAnalyticsTable<OutgoingRequestSortKey>({
            search,
            only: ['hosts', 'pagination', 'sort', 'direction'],
        });

    const onSort = (col: string) =>
        handleSort(col as OutgoingRequestSortKey, sort, direction);

    function handleRowClick(host: string) {
        router.visit(
            hostRoute.url(
                { environment: activeEnvironment.slug },
                { query: { host } },
            ),
        );
    }

    return (
        <div className="flex flex-col gap-3">
            <AnalyticsTableHeader
                icon={Globe}
                label="Domains"
                count={pagination.total}
                search={searchValue}
                searchPlaceholder="Search domains..."
                onSearch={handleSearch}
            />
            <Table
                className="border-separate border-spacing-y-1.5"
                containerClassName="overflow-x-visible"
            >
                <TableHeader className="sticky top-16 z-10 backdrop-blur-sm [&_tr]:border-0">
                    <TableRow className="border-0 shadow-sm shadow-black/4 hover:bg-transparent [&_th]:border-y [&_th]:border-border [&_th]:bg-muted/50 [&_th:first-child]:rounded-l-lg [&_th:first-child]:border-l [&_th:last-child]:rounded-r-lg [&_th:last-child]:border-r">
                        <SortableHead
                            column="host"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            className="h-11 px-5 text-xs font-medium"
                        >
                            Host
                        </SortableHead>
                        <SortableHead
                            column="success"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            1/2/3xx
                        </SortableHead>
                        <SortableHead
                            column="count_4xx"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            4xx
                        </SortableHead>
                        <SortableHead
                            column="count_5xx"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            5xx
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
                        <TableHead className="h-11 w-px pr-5" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {hosts.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell
                                colSpan={8}
                                className="py-12 text-center text-sm text-muted-foreground"
                            >
                                No outgoing requests recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        hosts.map((row) => (
                            <TableRow
                                key={row.host}
                                onClick={() => handleRowClick(row.host)}
                                className="group/row cursor-pointer border-0 bg-surface shadow-sm shadow-black/4 hover:bg-transparent [&_td]:border-y [&_td]:border-border [&_td]:bg-surface [&_td]:transition-colors [&_td]:duration-150 hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td:first-child]:rounded-l-lg [&_td:first-child]:border-l [&_td:last-child]:rounded-r-lg [&_td:last-child]:border-r"
                            >
                                <TableCell className="h-11 max-w-xs overflow-hidden px-5">
                                    <span className="block truncate font-mono text-sm">
                                        {row.host}
                                    </span>
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right whitespace-nowrap tabular-nums">
                                    {row.success.toLocaleString()}
                                </TableCell>
                                <TableCell
                                    className={`h-11 w-px px-4 text-right whitespace-nowrap tabular-nums ${row.count_4xx === 0 ? 'text-muted-foreground' : 'text-amber-500'}`}
                                >
                                    <span className="flex items-center justify-end gap-1">
                                        {row.count_4xx > 0 && (
                                            <TriangleAlert className="size-3" />
                                        )}
                                        {row.count_4xx.toLocaleString()}
                                    </span>
                                </TableCell>
                                <TableCell
                                    className={`h-11 w-px px-4 text-right whitespace-nowrap tabular-nums ${row.count_5xx === 0 ? 'text-muted-foreground' : 'text-red-500'}`}
                                >
                                    <span className="flex items-center justify-end gap-1">
                                        {row.count_5xx > 0 && (
                                            <OctagonAlert className="size-3 shrink-0" />
                                        )}
                                        {row.count_5xx.toLocaleString()}
                                    </span>
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
