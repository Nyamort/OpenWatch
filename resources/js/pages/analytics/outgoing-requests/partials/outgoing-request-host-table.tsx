import { SortableHead } from '@/components/analytics/table/sortable-head';
import { TablePagination } from '@/components/analytics/table/table-pagination';
import { Badge } from '@/components/ui/badge';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useAnalyticsTable } from '@/hooks/use-analytics-table';
import { cn, formatDuration } from '@/lib/utils';
import type {
    OutgoingRequestHostSortKey,
    OutgoingRequestRunRow,
    Pagination,
    SortDir,
} from '../types';

function statusColor(code: number | null): string {
    if (code === null) return '';
    if (code >= 500) return 'text-red-500';
    if (code >= 400) return 'text-orange-500';
    return 'text-emerald-600 dark:text-emerald-500';
}

interface OutgoingRequestHostTableProps {
    runs: OutgoingRequestRunRow[];
    pagination: Pagination;
    sort: OutgoingRequestHostSortKey;
    direction: SortDir;
    count: number;
}

export function OutgoingRequestHostTable({
    runs,
    pagination,
    sort,
    direction,
    count,
}: OutgoingRequestHostTableProps) {
    const { handlePage, handleSort } =
        useAnalyticsTable<OutgoingRequestHostSortKey>({
            search: '',
            only: ['runs', 'pagination', 'sort', 'direction'],
        });

    const onSort = (col: string) =>
        handleSort(col as OutgoingRequestHostSortKey, sort, direction);

    return (
        <div className="flex flex-col gap-3">
            <div className="flex items-center gap-2">
                <span className="text-sm font-medium">
                    {count.toLocaleString()}{' '}
                    {count === 1 ? 'request' : 'requests'}
                </span>
            </div>
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
                            className="h-11 w-px px-5 text-xs font-medium whitespace-nowrap"
                        >
                            Date
                        </SortableHead>
                        <TableHead className="h-11 px-4 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Source
                        </TableHead>
                        <TableHead className="h-11 w-px px-4 text-xs font-medium tracking-wide whitespace-nowrap text-muted-foreground uppercase">
                            Method
                        </TableHead>
                        <SortableHead
                            column="status"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            className="h-11 w-px px-4 text-xs font-medium whitespace-nowrap"
                        >
                            Status
                        </SortableHead>
                        <TableHead className="h-11 px-4 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            URL
                        </TableHead>
                        <SortableHead
                            column="duration"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-5 text-right text-xs font-medium whitespace-nowrap"
                        >
                            Duration
                        </SortableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {runs.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell
                                colSpan={6}
                                className="py-12 text-center text-sm text-muted-foreground"
                            >
                                No requests recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        runs.map((row) => (
                            <TableRow
                                key={row.id}
                                className="border-0 bg-card shadow-sm shadow-black/4 hover:bg-transparent [&_td]:border-y [&_td]:border-border [&_td]:bg-card [&_td]:transition-colors [&_td]:duration-150 hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td:first-child]:rounded-l-lg [&_td:first-child]:border-l [&_td:last-child]:rounded-r-lg [&_td:last-child]:border-r"
                            >
                                <TableCell className="h-11 w-px px-5 text-sm whitespace-nowrap text-muted-foreground tabular-nums">
                                    {row.recorded_at}
                                </TableCell>
                                <TableCell className="h-11 px-4">
                                    {row.source ? (
                                        <div className="flex flex-col">
                                            <span className="font-mono text-xs capitalize">
                                                {row.source}
                                            </span>
                                            {row.source_preview && (
                                                <span className="truncate text-xs text-muted-foreground">
                                                    {row.source_preview}
                                                </span>
                                            )}
                                        </div>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            —
                                        </span>
                                    )}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 whitespace-nowrap">
                                    {row.method ? (
                                        <Badge
                                            variant="outline"
                                            className="font-mono text-xs"
                                        >
                                            {row.method}
                                        </Badge>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            —
                                        </span>
                                    )}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 whitespace-nowrap">
                                    {row.status_code !== null ? (
                                        <span
                                            className={cn(
                                                'font-mono text-sm font-medium tabular-nums',
                                                statusColor(row.status_code),
                                            )}
                                        >
                                            {row.status_code}
                                        </span>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            —
                                        </span>
                                    )}
                                </TableCell>
                                <TableCell className="h-11 max-w-xs px-4">
                                    {row.url ? (
                                        <span className="truncate font-mono text-xs text-muted-foreground">
                                            {row.url}
                                        </span>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            —
                                        </span>
                                    )}
                                </TableCell>
                                <TableCell className="h-11 w-px px-5 text-right whitespace-nowrap tabular-nums">
                                    {formatDuration(row.duration)}
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
