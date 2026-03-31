import { usePage } from '@inertiajs/react';
import { HardDrive } from 'lucide-react';
import { AnalyticsTableHeader } from '@/components/analytics/table/analytics-table-header';
import { SortableHead } from '@/components/analytics/table/sortable-head';
import { TablePagination } from '@/components/analytics/table/table-pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useAnalyticsTable } from '@/hooks/use-analytics-table';
import type { CacheKeyRow, CacheSortKey, Pagination, SortDir } from '../types';

interface CacheTableProps {
    keys: CacheKeyRow[];
    pagination: Pagination;
    sort: CacheSortKey;
    direction: SortDir;
    search: string;
}

function HitPct({ value }: { value: number | null }) {
    if (value === null) return <span className="text-muted-foreground">—</span>;
    const color = value >= 80 ? 'text-emerald-600' : value >= 50 ? 'text-yellow-600' : 'text-red-500';
    return <span className={color}>{value.toFixed(1)}%</span>;
}

export function CacheTable({ keys, pagination, sort, direction, search }: CacheTableProps) {
    const { searchValue, handleSearch, handlePage, handleSort } = useAnalyticsTable<CacheSortKey>({
        search,
        only: ['keys', 'pagination', 'sort', 'direction'],
    });

    const onSort = (col: string) => handleSort(col as CacheSortKey, sort, direction);

    return (
        <div className="flex flex-col gap-3">
            <AnalyticsTableHeader
                icon={HardDrive}
                label="Cache Keys"
                count={pagination.total}
                search={searchValue}
                searchPlaceholder="Search keys..."
                onSearch={handleSearch}
            />
            <Table className="border-separate border-spacing-y-1.5" containerClassName="overflow-x-visible">
                <TableHeader className="sticky top-16 z-10 backdrop-blur-sm [&_tr]:border-0">
                    <TableRow className="border-0 hover:bg-transparent shadow-sm shadow-black/4 [&_th]:border-y [&_th]:border-border [&_th:first-child]:border-l [&_th:first-child]:rounded-l-lg [&_th:last-child]:border-r [&_th:last-child]:rounded-r-lg [&_th]:bg-muted/50">
                        <SortableHead column="key" sort={sort} direction={direction} onSort={onSort} className="h-11 px-5 text-xs font-medium">
                            Key
                        </SortableHead>
                        <SortableHead column="hit_pct" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            Hit %
                        </SortableHead>
                        <SortableHead column="hits" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            Hits
                        </SortableHead>
                        <SortableHead column="misses" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            Misses
                        </SortableHead>
                        <SortableHead column="writes" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            Writes
                        </SortableHead>
                        <SortableHead column="deletes" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            Deletes
                        </SortableHead>
                        <SortableHead column="failures" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            Failures
                        </SortableHead>
                        <SortableHead column="total" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap pr-5 text-right text-xs font-medium">
                            Total
                        </SortableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {keys.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell colSpan={8} className="py-12 text-center text-sm text-muted-foreground">
                                No cache events recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        keys.map((row) => (
                            <TableRow
                                key={row.key}
                                className="bg-surface group/row border-0 hover:bg-transparent shadow-sm shadow-black/4 [&_td]:border-y [&_td]:border-border [&_td:first-child]:border-l [&_td:first-child]:rounded-l-lg [&_td:last-child]:border-r [&_td:last-child]:rounded-r-lg [&_td]:bg-surface hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td]:transition-colors [&_td]:duration-150"
                            >
                                <TableCell className="h-11 overflow-hidden px-5 md:max-w-px">
                                    <span className="truncate font-mono text-sm">{row.key}</span>
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums font-medium">
                                    <HitPct value={row.hit_pct} />
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {row.hits.toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {row.misses.toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {row.writes.toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {row.deletes.toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {row.failures > 0
                                        ? <span className="text-red-500">{row.failures.toLocaleString()}</span>
                                        : row.failures.toLocaleString()
                                    }
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap pr-5 text-right tabular-nums font-medium">
                                    {row.total.toLocaleString()}
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
