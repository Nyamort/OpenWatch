import { router } from '@inertiajs/react';
import { ArrowUpRight } from 'lucide-react';
import { HttpMethodBadge } from '@/components/analytics/http-method-badge';
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
import { show as requestShow } from '@/routes/analytics/requests';
import type {
    Pagination,
    RouteRequestRow,
    RouteSortKey,
    SortDir,
} from '../types';

interface RouteTableProps {
    requests: RouteRequestRow[];
    pagination: Pagination;
    sort: RouteSortKey;
    direction: SortDir;
    environment: string;
}

function statusClass(code: number): string {
    if (code >= 500) {
        return 'text-red-500';
    }

    if (code >= 400) {
        return 'text-orange-500';
    }

    return '';
}

export function RouteTable({
    requests,
    pagination,
    sort,
    direction,
    environment,
}: RouteTableProps) {
    const { handlePage, handleSort } = useAnalyticsTable<RouteSortKey>({
        search: '',
        only: ['requests', 'pagination', 'sort', 'direction'],
    });

    const onSort = (col: string) =>
        handleSort(col as RouteSortKey, sort, direction);

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
                        <TableHead className="h-11 w-px px-4 text-xs font-medium tracking-wide whitespace-nowrap text-muted-foreground uppercase">
                            Method
                        </TableHead>
                        <TableHead className="h-11 px-4 text-xs font-medium tracking-wide whitespace-nowrap text-muted-foreground uppercase">
                            Details
                        </TableHead>
                        <SortableHead
                            column="status"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
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
                    {requests.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell
                                colSpan={6}
                                className="py-12 text-center text-sm text-muted-foreground"
                            >
                                No requests recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        requests.map((row) => (
                            <TableRow
                                key={row.id}
                                onClick={() =>
                                    router.visit(
                                        requestShow.url({
                                            environment,
                                            request: row.id,
                                        }),
                                    )
                                }
                                className="group/row cursor-pointer border-0 bg-surface shadow-sm shadow-black/4 hover:bg-transparent [&_td]:border-y [&_td]:border-border [&_td]:bg-surface [&_td]:transition-colors [&_td]:duration-150 hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td:first-child]:rounded-l-lg [&_td:first-child]:border-l [&_td:last-child]:rounded-r-lg [&_td:last-child]:border-r"
                            >
                                <TableCell className="h-11 w-px px-5 text-sm whitespace-nowrap text-muted-foreground tabular-nums">
                                    {row.recorded_at}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4">
                                    <HttpMethodBadge methods={[row.method]} />
                                </TableCell>
                                <TableCell className="h-11 max-w-xs px-4">
                                    <span className="block truncate font-mono text-xs text-muted-foreground">
                                        {row.route_path}
                                    </span>
                                </TableCell>
                                <TableCell
                                    className={`h-11 w-px px-4 text-right font-mono text-sm font-medium whitespace-nowrap tabular-nums ${statusClass(row.status_code)}`}
                                >
                                    {row.status_code}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right whitespace-nowrap tabular-nums">
                                    {formatDuration(row.duration)}
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
