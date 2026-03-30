import { Link, usePage } from '@inertiajs/react';
import { formatDistanceToNow, parseISO } from 'date-fns';
import { AlertCircle, ArrowUpRight } from 'lucide-react';
import { AnalyticsTableHeader } from '@/components/analytics/table/analytics-table-header';
import { SortableHead } from '@/components/analytics/table/sortable-head';
import { TablePagination } from '@/components/analytics/table/table-pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useAnalyticsTable } from '@/hooks/use-analytics-table';
import { show } from '@/routes/analytics/exceptions';
import type { ExceptionRow, ExceptionSortKey, Pagination, SortDir } from '../types';

interface ExceptionTableProps {
    exceptions: ExceptionRow[];
    pagination: Pagination;
    sort: ExceptionSortKey;
    direction: SortDir;
    search: string;
}

export function ExceptionTable({ exceptions, pagination, sort, direction, search }: ExceptionTableProps) {
    const { props } = usePage();
    const { activeOrganization, activeProject, activeEnvironment } = props as unknown as {
        activeOrganization: { slug: string };
        activeProject: { slug: string };
        activeEnvironment: { slug: string };
    };

    const { searchValue, handleSearch, handlePage, handleSort } = useAnalyticsTable<ExceptionSortKey>({
        search,
        only: ['exceptions', 'pagination', 'sort', 'direction'],
    });

    const onSort = (col: string) => handleSort(col as ExceptionSortKey, sort, direction);

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
            <Table className="border-separate border-spacing-y-1.5" containerClassName="overflow-x-visible">
                <TableHeader className="sticky top-16 z-10 backdrop-blur-sm [&_tr]:border-0">
                    <TableRow className="border-0 hover:bg-transparent shadow-sm shadow-black/4 [&_th]:border-y [&_th]:border-border [&_th:first-child]:border-l [&_th:first-child]:rounded-l-lg [&_th:last-child]:border-r [&_th:last-child]:rounded-r-lg [&_th]:bg-muted/50">
                        <SortableHead column="last_seen" sort={sort} direction={direction} onSort={onSort} className="h-11 w-px whitespace-nowrap pl-5 text-xs font-medium">
                            Last Seen
                        </SortableHead>
                        <SortableHead column="class" sort={sort} direction={direction} onSort={onSort} className="h-11 px-4 text-xs font-medium">
                            Exception
                        </SortableHead>
                        <SortableHead column="count" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            Count
                        </SortableHead>
                        <SortableHead column="users" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            Users
                        </SortableHead>
                        <TableHead className="h-11 w-px pr-5" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {exceptions.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell colSpan={5} className="py-12 text-center text-sm text-muted-foreground">
                                No exceptions recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        exceptions.map((row) => (
                            <TableRow
                                key={row.group_key}
                                className="bg-surface group/row border-0 hover:bg-transparent cursor-pointer shadow-sm shadow-black/4 [&_td]:border-y [&_td]:border-border [&_td:first-child]:border-l [&_td:first-child]:rounded-l-lg [&_td:last-child]:border-r [&_td:last-child]:rounded-r-lg [&_td]:bg-surface hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td]:transition-colors [&_td]:duration-150"
                            >
                                <TableCell className="h-11 w-px whitespace-nowrap pl-5 pr-4 text-sm tabular-nums text-muted-foreground">
                                    {formatDistanceToNow(parseISO(row.last_seen), { addSuffix: true })}
                                </TableCell>
                                <TableCell className="h-11 overflow-hidden px-4">
                                    <span className="truncate font-mono text-sm">{row.class}</span>
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums font-medium">
                                    {row.count.toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {row.users.toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px pr-5">
                                    <div className="flex items-center justify-end">
                                        <Link
                                            href={show.url({
                                                organization: activeOrganization.slug,
                                                project: activeProject.slug,
                                                environment: activeEnvironment.slug,
                                                group: row.group_key,
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
