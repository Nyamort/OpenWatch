import { Link, usePage } from '@inertiajs/react';
import { ArrowUpRight, Database } from 'lucide-react';
import { AnalyticsTableHeader } from '@/components/analytics/table/analytics-table-header';
import { SortableHead } from '@/components/analytics/table/sortable-head';
import { TablePagination } from '@/components/analytics/table/table-pagination';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useAnalyticsTable } from '@/hooks/use-analytics-table';
import { show } from '@/routes/analytics/queries';
import { formatDuration } from '../../requests/partials/request-charts';
import type { Pagination, QueryRow, QuerySortKey, SortDir } from '../types';

interface QueryTableProps {
    queries: QueryRow[];
    pagination: Pagination;
    sort: QuerySortKey;
    direction: SortDir;
    search: string;
}

export function QueryTable({ queries, pagination, sort, direction, search }: QueryTableProps) {
    const { props } = usePage();
    const { activeOrganization, activeProject, activeEnvironment } = props as unknown as {
        activeOrganization: { slug: string };
        activeProject: { slug: string };
        activeEnvironment: { slug: string };
    };

    const { searchValue, handleSearch, handlePage, handleSort } = useAnalyticsTable<QuerySortKey>({
        search,
        only: ['queries', 'pagination', 'sort', 'direction'],
    });

    const onSort = (col: string) => handleSort(col as QuerySortKey, sort, direction);

    return (
        <div className="flex flex-col gap-3">
            <AnalyticsTableHeader
                icon={Database}
                label="Queries"
                count={pagination.total}
                search={searchValue}
                searchPlaceholder="Search queries..."
                onSearch={handleSearch}
            />
            <Table className="border-separate border-spacing-y-1.5">
                <TableHeader className="[&_tr]:border-0">
                    <TableRow className="border-0 hover:bg-transparent shadow-sm shadow-black/4 [&_th]:border-y [&_th]:border-border [&_th:first-child]:border-l [&_th:first-child]:rounded-l-lg [&_th:last-child]:border-r [&_th:last-child]:rounded-r-lg [&_th]:bg-muted/50">
                        <SortableHead column="query" sort={sort} direction={direction} onSort={onSort} className="h-11 px-5 text-xs font-medium">
                            Query
                        </SortableHead>
                        <SortableHead column="connection" sort={sort} direction={direction} onSort={onSort} className="h-11 w-px whitespace-nowrap px-4 text-xs font-medium">
                            Connection
                        </SortableHead>
                        <SortableHead column="calls" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            Calls
                        </SortableHead>
                        <SortableHead column="total" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            Total
                        </SortableHead>
                        <SortableHead column="avg" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            AVG
                        </SortableHead>
                        <SortableHead column="p95" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            P95
                        </SortableHead>
                        <TableHead className="h-11 w-px pr-5" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {queries.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell colSpan={7} className="py-12 text-center text-sm text-muted-foreground">
                                No queries recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        queries.map((row) => (
                            <TableRow
                                key={row.sql_hash}
                                className="bg-surface group/row border-0 hover:bg-transparent cursor-pointer shadow-sm shadow-black/4 [&_td]:border-y [&_td]:border-border [&_td:first-child]:border-l [&_td:first-child]:rounded-l-lg [&_td:last-child]:border-r [&_td:last-child]:rounded-r-lg [&_td]:bg-surface hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td]:transition-colors [&_td]:duration-150"
                            >
                                <TableCell className="h-11 overflow-hidden px-5">
                                    <span className="truncate font-mono text-sm">{row.query}</span>
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4">
                                    <Badge variant="outline" className="font-mono text-xs">
                                        {row.connection}
                                    </Badge>
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums font-medium">
                                    {row.calls.toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {formatDuration(row.total)}
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {formatDuration(row.avg)}
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {formatDuration(row.p95)}
                                </TableCell>
                                <TableCell className="h-11 w-px pr-5">
                                    <div className="flex items-center justify-end">
                                        <Link
                                            href={show.url({
                                                organization: activeOrganization.slug,
                                                project: activeProject.slug,
                                                environment: activeEnvironment.slug,
                                                query: row.sql_hash,
                                            })}
                                            className="flex items-center rounded-sm border border-border/20 bg-muted/30 text-foreground/10 transition-colors group-hover/row:border-border/60 group-hover/row:text-emerald-500 dark:border-white/7 dark:bg-white/1 dark:text-white/10 dark:group-hover/row:border-white/15 dark:group-hover/row:text-emerald-500"
                                        >
                                            <span className="flex size-6 items-center justify-center">
                                                <ArrowUpRight className="size-4" />
                                            </span>
                                        </Link>
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
