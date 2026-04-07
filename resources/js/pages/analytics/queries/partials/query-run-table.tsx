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
import { formatDuration } from '@/lib/utils';
import type { Pagination } from '@/types/analytics';

export interface QueryRunRow {
    id: string;
    recorded_at: string;
    source: string | null;
    source_preview: string | null;
    file: string | null;
    line: number | null;
    connection: string;
    duration: number;
}

type QueryRunSortKey = 'date' | 'duration';
type SortDir = 'asc' | 'desc';

interface QueryRunTableProps {
    runs: QueryRunRow[];
    pagination: Pagination;
    sort: string;
    direction: SortDir;
    count: number;
}

export function QueryRunTable({ runs, pagination, sort, direction, count }: QueryRunTableProps) {
    const { handlePage, handleSort } = useAnalyticsTable<QueryRunSortKey>({
        search: '',
        only: ['runs', 'pagination', 'sort', 'direction'],
    });

    const onSort = (col: string) =>
        handleSort(col as QueryRunSortKey, sort as QueryRunSortKey, direction);

    return (
        <div className="flex flex-col gap-3">
            <div className="flex items-center gap-2">
                <span className="text-sm font-medium">
                    {count.toLocaleString()} {count === 1 ? 'call' : 'calls'}
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
                        <TableHead className="h-11 px-4 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Location
                        </TableHead>
                        <TableHead className="h-11 w-px px-4 text-xs font-medium tracking-wide text-muted-foreground uppercase whitespace-nowrap">
                            Connection
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
                                colSpan={5}
                                className="py-12 text-center text-sm text-muted-foreground"
                            >
                                No calls recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        runs.map((row) => (
                            <TableRow
                                key={row.id}
                                className="border-0 bg-surface shadow-sm shadow-black/4 hover:bg-transparent [&_td]:border-y [&_td]:border-border [&_td]:bg-surface [&_td]:transition-colors [&_td]:duration-150 hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td:first-child]:rounded-l-lg [&_td:first-child]:border-l [&_td:last-child]:rounded-r-lg [&_td:last-child]:border-r"
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
                                        <span className="text-muted-foreground">—</span>
                                    )}
                                </TableCell>
                                <TableCell className="h-11 px-4">
                                    {row.file ? (
                                        <span className="font-mono text-xs text-muted-foreground">
                                            {row.file}
                                            {row.line !== null && (
                                                <span className="text-muted-foreground/60">
                                                    :{row.line}
                                                </span>
                                            )}
                                        </span>
                                    ) : (
                                        <span className="text-muted-foreground">—</span>
                                    )}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 whitespace-nowrap">
                                    <Badge variant="outline" className="font-mono text-xs">
                                        {row.connection}
                                    </Badge>
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
