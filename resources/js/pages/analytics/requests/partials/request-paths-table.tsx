import { router, usePage } from '@inertiajs/react';
import {
    ArrowUpRight,
    FolderClosed,
    Globe,
    OctagonAlert,
    TriangleAlert,
} from 'lucide-react';
import { HttpMethodBadge } from '@/components/analytics/http-method-badge';
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
import { route as requestRoute } from '@/routes/analytics/requests';
import type { Pagination, PathRow, SortDir, SortKey } from '../types';

interface RequestPathsTableProps {
    paths: PathRow[];
    pagination: Pagination;
    sort: SortKey;
    direction: SortDir;
    search: string;
}

export function RequestPathsTable({
    paths,
    pagination,
    sort,
    direction,
    search,
}: RequestPathsTableProps) {
    const { searchValue, handleSearch, handlePage, handleSort } =
        useAnalyticsTable<SortKey>({
            search,
            only: ['paths', 'pagination', 'sort', 'direction'],
        });

    const onSort = (col: string) => handleSort(col as SortKey, sort, direction);

    const { props } = usePage();
    const { activeOrganization, activeProject, activeEnvironment } = props as {
        activeOrganization?: { slug: string } | null;
        activeProject?: { slug: string } | null;
        activeEnvironment?: { slug: string } | null;
    };
    const analyticsHref = useAnalyticsHref();

    const routeHref = (row: PathRow) => {
        if (!activeOrganization || !activeProject || !activeEnvironment) {
            return '#';
        }

        return analyticsHref(
            requestRoute.url(
                {
                    organization: activeOrganization.slug,
                    project: activeProject.slug,
                    environment: activeEnvironment.slug,
                },
                {
                    query: {
                        route_path: row.path ?? '',
                        method: row.methods[0] ?? '',
                    },
                },
            ),
        );
    };

    return (
        <div className="flex flex-col gap-3">
            <AnalyticsTableHeader
                icon={Globe}
                label="Routes"
                count={pagination.total}
                search={searchValue}
                searchPlaceholder="Search routes..."
                onSearch={handleSearch}
            />
            <Table
                className="border-separate border-spacing-y-1.5"
                containerClassName="overflow-x-visible"
            >
                <TableHeader className="sticky top-16 z-10 backdrop-blur-sm [&_tr]:border-0">
                    <TableRow className="border-0 shadow-sm shadow-black/4 hover:bg-transparent [&_th]:border-y [&_th]:border-border [&_th]:bg-muted/50 [&_th:first-child]:rounded-l-lg [&_th:first-child]:border-l [&_th:last-child]:rounded-r-lg [&_th:last-child]:border-r">
                        <SortableHead
                            column="method"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            className="h-11 w-px pl-5 text-xs font-medium whitespace-nowrap"
                        >
                            Method
                        </SortableHead>
                        <SortableHead
                            column="path"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            className="h-11 px-4 text-xs font-medium"
                        >
                            Path
                        </SortableHead>
                        <SortableHead
                            column="2xx"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            1/2/3xx
                        </SortableHead>
                        <SortableHead
                            column="4xx"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            4xx
                        </SortableHead>
                        <SortableHead
                            column="5xx"
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
                    {paths.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell
                                colSpan={9}
                                className="py-12 text-center text-sm text-muted-foreground"
                            >
                                No requests recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        paths.map((row, i) => (
                            <TableRow
                                key={i}
                                onClick={() => router.visit(routeHref(row))}
                                className="group/row cursor-pointer border-0 bg-surface shadow-sm shadow-black/4 hover:bg-transparent [&_td]:border-y [&_td]:border-border [&_td]:bg-surface [&_td]:transition-colors [&_td]:duration-150 hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td:first-child]:rounded-l-lg [&_td:first-child]:border-l [&_td:last-child]:rounded-r-lg [&_td:last-child]:border-r"
                            >
                                <TableCell className="h-11 w-px pr-4 pl-5 whitespace-nowrap">
                                    <HttpMethodBadge methods={row.methods} />
                                </TableCell>
                                <TableCell className="h-11 overflow-hidden px-4">
                                    <div className="flex min-w-0 items-center gap-2">
                                        {row.path ? (
                                            <Globe className="size-4 shrink-0 stroke-1 text-muted-foreground transition-colors duration-150 group-hover/row:text-emerald-500" />
                                        ) : (
                                            <FolderClosed className="size-4 shrink-0 stroke-1 text-muted-foreground transition-colors duration-150 group-hover/row:text-emerald-500" />
                                        )}
                                        <span className="truncate font-mono text-sm">
                                            {row.path ?? 'Unmatched Route'}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right whitespace-nowrap tabular-nums">
                                    {row['2xx'].toLocaleString()}
                                </TableCell>
                                <TableCell
                                    className={`h-11 w-px px-4 text-right whitespace-nowrap tabular-nums ${row['4xx'] === 0 ? 'text-muted-foreground' : 'text-amber-500'}`}
                                >
                                    <div className="flex items-center justify-end gap-1">
                                        {row['4xx'] > 0 && (
                                            <TriangleAlert className="size-3" />
                                        )}
                                        {row['4xx'].toLocaleString()}
                                    </div>
                                </TableCell>
                                <TableCell
                                    className={`h-11 w-px px-4 text-right whitespace-nowrap tabular-nums ${row['5xx'] === 0 ? 'text-muted-foreground' : 'text-red-500'}`}
                                >
                                    <div className="flex items-center justify-end gap-1">
                                        {row['5xx'] > 0 && (
                                            <OctagonAlert className="size-3 shrink-0" />
                                        )}
                                        {row['5xx'].toLocaleString()}
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
