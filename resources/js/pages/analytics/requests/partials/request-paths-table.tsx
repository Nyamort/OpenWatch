import { router, usePage } from '@inertiajs/react';
import { ArrowUpRight, FolderClosed, Globe, OctagonAlert, PanelRight, TriangleAlert } from 'lucide-react';
import { useRef, useState } from 'react';
import { AnalyticsTableHeader } from '@/components/analytics/table/analytics-table-header';
import { SortableHead } from '@/components/analytics/table/sortable-head';
import { TablePagination } from '@/components/analytics/table/table-pagination';
import { HttpMethodBadge } from '@/components/analytics/http-method-badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { Pagination, PathRow, SortDir, SortKey } from '../types';
import { formatDuration } from './request-charts';

interface RequestPathsTableProps {
    paths: PathRow[];
    pagination: Pagination;
    sort: SortKey;
    direction: SortDir;
    search: string;
}

export function RequestPathsTable({ paths, pagination, sort, direction, search }: RequestPathsTableProps) {
    const { url } = usePage();
    const [searchValue, setSearchValue] = useState(search);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);

    function handleSearch(value: string) {
        setSearchValue(value);
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => {
            const urlObj = new URL(url, window.location.origin);
            urlObj.searchParams.set('search', value);
            urlObj.searchParams.delete('page');
            router.get(urlObj.pathname + urlObj.search, {}, { preserveScroll: true, preserveState: true, only: ['paths', 'pagination', 'sort', 'direction', 'search'] });
        }, 300);
    }

    function handlePage(page: number) {
        const urlObj = new URL(url, window.location.origin);
        urlObj.searchParams.set('page', String(page));
        router.get(urlObj.pathname + urlObj.search, {}, { preserveScroll: true, preserveState: true, only: ['paths', 'pagination', 'sort', 'direction'] });
    }

    function handleSort(key: SortKey) {
        const urlObj = new URL(url, window.location.origin);
        const newDir: SortDir = sort === key && direction === 'desc' ? 'asc' : 'desc';
        urlObj.searchParams.set('sort', key);
        urlObj.searchParams.set('direction', newDir);
        urlObj.searchParams.delete('page');
        router.get(urlObj.pathname + urlObj.search, {}, { preserveScroll: true, preserveState: true, only: ['paths', 'pagination', 'sort', 'direction'] });
    }

    const onSort = (col: string) => handleSort(col as SortKey);

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
            <Table className="border-separate border-spacing-y-1.5">
                <TableHeader className="[&_tr]:border-0">
                    <TableRow className="border-0 hover:bg-transparent shadow-sm shadow-black/4 [&_th]:border-y [&_th]:border-border [&_th:first-child]:border-l [&_th:first-child]:rounded-l-lg [&_th:last-child]:border-r [&_th:last-child]:rounded-r-lg [&_th]:bg-muted/50">
                        <SortableHead column="method" sort={sort} direction={direction} onSort={onSort} className="h-11 w-px whitespace-nowrap pl-5 text-xs font-medium">
                            Method
                        </SortableHead>
                        <SortableHead column="path" sort={sort} direction={direction} onSort={onSort} className="h-11 px-4 text-xs font-medium">
                            Path
                        </SortableHead>
                        <SortableHead column="2xx" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            1/2/3xx
                        </SortableHead>
                        <SortableHead column="4xx" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            4xx
                        </SortableHead>
                        <SortableHead column="5xx" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            5xx
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
                    {paths.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell colSpan={9} className="py-12 text-center text-sm text-muted-foreground">
                                No requests recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        paths.map((row, i) => (
                            <TableRow
                                key={i}
                                className="bg-surface group/row border-0 hover:bg-transparent cursor-pointer shadow-sm shadow-black/4 [&_td]:border-y [&_td]:border-border [&_td:first-child]:border-l [&_td:first-child]:rounded-l-lg [&_td:last-child]:border-r [&_td:last-child]:rounded-r-lg [&_td]:bg-surface hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td]:transition-colors [&_td]:duration-150"
                            >
                                <TableCell className="h-11 w-px whitespace-nowrap pl-5 pr-4">
                                    <HttpMethodBadge methods={row.methods} />
                                </TableCell>
                                <TableCell className="h-11 overflow-hidden px-4">
                                    <div className="flex min-w-0 items-center gap-2">
                                        {row.path
                                            ? <Globe className="size-4 shrink-0 stroke-1 text-muted-foreground transition-colors duration-150 group-hover/row:text-emerald-500" />
                                            : <FolderClosed className="size-4 shrink-0 stroke-1 text-muted-foreground transition-colors duration-150 group-hover/row:text-emerald-500" />
                                        }
                                        <span className="truncate font-mono text-sm">
                                            {row.path ?? 'Unmatched Route'}
                                        </span>
                                    </div>
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {row['2xx'].toLocaleString()}
                                </TableCell>
                                <TableCell className={`h-11 w-px whitespace-nowrap px-4 text-right tabular-nums ${row['4xx'] === 0 ? 'text-muted-foreground' : 'text-amber-500'}`}>
                                    <div className="flex items-center justify-end gap-1">
                                        {row['4xx'] > 0 && <TriangleAlert className="size-3" />}
                                        {row['4xx'].toLocaleString()}
                                    </div>
                                </TableCell>
                                <TableCell className={`h-11 w-px whitespace-nowrap px-4 text-right tabular-nums ${row['5xx'] === 0 ? 'text-muted-foreground' : 'text-red-500'}`}>
                                    <div className="flex items-center justify-end gap-1">
                                        {row['5xx'] > 0 && <OctagonAlert className="size-3 shrink-0" />}
                                        {row['5xx'].toLocaleString()}
                                    </div>
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums font-medium">
                                    {row.total.toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {formatDuration(row.avg)}
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {formatDuration(row.p95)}
                                </TableCell>
                                <TableCell className="h-11 w-px pr-5">
                                    <div className="flex items-center justify-end">
                                        <div className="flex items-center rounded-sm border border-border/20 bg-muted/30 uppercase text-foreground/10 transition-colors group-hover/row:border-border/60 group-hover/row:text-emerald-500 dark:border-white/7 dark:bg-white/1 dark:text-white/10 dark:group-hover/row:border-white/15 dark:group-hover/row:text-emerald-500">
                                            <button className="flex size-6 items-center justify-center">
                                                <PanelRight className="size-3" />
                                            </button>
                                            <a className="-ml-1 flex size-6 items-center justify-center">
                                                <ArrowUpRight className="size-4" />
                                            </a>
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
