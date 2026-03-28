import { router, usePage } from '@inertiajs/react';
import { BriefcaseBusiness, OctagonAlert, TriangleAlert } from 'lucide-react';
import { useRef, useState } from 'react';
import { AnalyticsTableHeader } from '@/components/analytics/table/analytics-table-header';
import { SortableHead } from '@/components/analytics/table/sortable-head';
import { TablePagination } from '@/components/analytics/table/table-pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { JobRow, JobSortKey, Pagination, SortDir } from '../types';
import { formatDuration } from '../../requests/partials/request-charts';

interface JobTableProps {
    jobs: JobRow[];
    pagination: Pagination;
    sort: JobSortKey;
    direction: SortDir;
    search: string;
}

export function JobTable({ jobs, pagination, sort, direction, search }: JobTableProps) {
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
            router.get(urlObj.pathname + urlObj.search, {}, { preserveScroll: true, preserveState: true, only: ['jobs', 'pagination', 'sort', 'direction', 'search'] });
        }, 300);
    }

    function handlePage(page: number) {
        const urlObj = new URL(url, window.location.origin);
        urlObj.searchParams.set('page', String(page));
        router.get(urlObj.pathname + urlObj.search, {}, { preserveScroll: true, preserveState: true, only: ['jobs', 'pagination', 'sort', 'direction'] });
    }

    function handleSort(key: JobSortKey) {
        const urlObj = new URL(url, window.location.origin);
        const newDir: SortDir = sort === key && direction === 'desc' ? 'asc' : 'desc';
        urlObj.searchParams.set('sort', key);
        urlObj.searchParams.set('direction', newDir);
        urlObj.searchParams.delete('page');
        router.get(urlObj.pathname + urlObj.search, {}, { preserveScroll: true, preserveState: true, only: ['jobs', 'pagination', 'sort', 'direction'] });
    }

    const onSort = (col: string) => handleSort(col as JobSortKey);

    return (
        <div className="flex flex-col gap-3">
            <AnalyticsTableHeader
                icon={BriefcaseBusiness}
                label="Jobs"
                count={pagination.total}
                search={searchValue}
                searchPlaceholder="Search jobs..."
                onSearch={handleSearch}
            />
            <Table className="border-separate border-spacing-y-1.5">
                <TableHeader className="[&_tr]:border-0">
                    <TableRow className="border-0 hover:bg-transparent shadow-sm shadow-black/4 [&_th]:border-y [&_th]:border-border [&_th:first-child]:border-l [&_th:first-child]:rounded-l-lg [&_th:last-child]:border-r [&_th:last-child]:rounded-r-lg [&_th]:bg-muted/50">
                        <SortableHead column="name" sort={sort} direction={direction} onSort={onSort} className="h-11 px-5 text-xs font-medium">
                            Job
                        </SortableHead>
                        <SortableHead column="total" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            Total
                        </SortableHead>
                        <SortableHead column="queued" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            Queued
                        </SortableHead>
                        <SortableHead column="processed" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            Processed
                        </SortableHead>
                        <SortableHead column="failed" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            Failed
                        </SortableHead>
                        <SortableHead column="released" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            Released
                        </SortableHead>
                        <SortableHead column="avg" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            AVG
                        </SortableHead>
                        <SortableHead column="p95" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap pr-5 text-right text-xs font-medium">
                            P95
                        </SortableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {jobs.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell colSpan={8} className="py-12 text-center text-sm text-muted-foreground">
                                No jobs recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        jobs.map((row, i) => (
                            <TableRow
                                key={i}
                                className="bg-surface group/row border-0 hover:bg-transparent cursor-pointer shadow-sm shadow-black/4 [&_td]:border-y [&_td]:border-border [&_td:first-child]:border-l [&_td:first-child]:rounded-l-lg [&_td:last-child]:border-r [&_td:last-child]:rounded-r-lg [&_td]:bg-surface hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td]:transition-colors [&_td]:duration-150"
                            >
                                <TableCell className="h-11 overflow-hidden px-5">
                                    <span className="truncate font-mono text-sm">
                                        {row.name ?? 'Unknown Job'}
                                    </span>
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums font-medium">
                                    {row.total.toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {row.queued.toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {row.processed.toLocaleString()}
                                </TableCell>
                                <TableCell className={`h-11 w-px whitespace-nowrap px-4 text-right tabular-nums ${row.failed === 0 ? 'text-muted-foreground' : 'text-red-500'}`}>
                                    <div className="flex items-center justify-end gap-1">
                                        {row.failed > 0 && <OctagonAlert className="size-3 shrink-0" />}
                                        {row.failed.toLocaleString()}
                                    </div>
                                </TableCell>
                                <TableCell className={`h-11 w-px whitespace-nowrap px-4 text-right tabular-nums ${row.released === 0 ? 'text-muted-foreground' : 'text-amber-500'}`}>
                                    <div className="flex items-center justify-end gap-1">
                                        {row.released > 0 && <TriangleAlert className="size-3" />}
                                        {row.released.toLocaleString()}
                                    </div>
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {formatDuration(row.avg)}
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap pr-5 text-right tabular-nums">
                                    {formatDuration(row.p95)}
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
