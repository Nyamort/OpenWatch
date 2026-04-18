import { format, parseISO } from 'date-fns';
import { TablePagination } from '@/components/analytics/table/table-pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Pagination } from '@/types/analytics';

export interface ExceptionOccurrenceRow {
    id: string;
    recorded_at: string;
    user: string | null;
    message: string | null;
    execution_source: string | null;
    execution_preview: string | null;
    [key: string]: unknown;
}

interface ExceptionOccurrenceTableProps {
    rows: ExceptionOccurrenceRow[];
    pagination: Pagination;
    count: number;
}

export function ExceptionOccurrenceTable({
    rows,
    pagination,
    count,
}: ExceptionOccurrenceTableProps) {
    return (
        <div className="flex flex-col gap-3">
            <div className="flex items-center gap-2">
                <span className="text-sm font-medium">
                    {count.toLocaleString()}{' '}
                    {count === 1 ? 'occurrence' : 'occurrences'}
                </span>
            </div>
            <Table
                className="border-separate border-spacing-y-1.5"
                containerClassName="overflow-x-visible"
            >
                <TableHeader className="sticky top-16 z-10 backdrop-blur-sm [&_tr]:border-0">
                    <TableRow className="border-0 shadow-sm shadow-black/4 hover:bg-transparent [&_th]:border-y [&_th]:border-border [&_th]:bg-muted/50 [&_th:first-child]:rounded-l-lg [&_th:first-child]:border-l [&_th:last-child]:rounded-r-lg [&_th:last-child]:border-r">
                        <TableHead className="h-11 w-px px-5 text-xs font-medium tracking-wide whitespace-nowrap text-muted-foreground uppercase">
                            Date
                        </TableHead>
                        <TableHead className="h-11 w-px px-4 text-xs font-medium tracking-wide whitespace-nowrap text-muted-foreground uppercase">
                            Source
                        </TableHead>
                        <TableHead className="h-11 px-4 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Message
                        </TableHead>
                        <TableHead className="h-11 w-px px-5 text-xs font-medium tracking-wide whitespace-nowrap text-muted-foreground uppercase">
                            User
                        </TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {rows.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell
                                colSpan={5}
                                className="py-12 text-center text-sm text-muted-foreground"
                            >
                                No occurrences recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        rows.map((row) => (
                            <TableRow
                                key={row.id}
                                className="border-0 bg-surface shadow-sm shadow-black/4 hover:bg-transparent [&_td]:border-y [&_td]:border-border [&_td]:bg-surface [&_td]:transition-colors [&_td]:duration-150 hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td:first-child]:rounded-l-lg [&_td:first-child]:border-l [&_td:last-child]:rounded-r-lg [&_td:last-child]:border-r"
                            >
                                <TableCell className="h-11 w-px px-5 text-sm whitespace-nowrap text-muted-foreground tabular-nums">
                                    {format(parseISO(row.recorded_at), 'yyyy-MM-dd HH:mm:ss')}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4">
                                    {row.execution_source ? (
                                        <div className="flex flex-col">
                                            <span className="font-mono text-xs capitalize">
                                                {row.execution_source}
                                            </span>
                                            {row.execution_preview && (
                                                <span className="truncate text-xs text-muted-foreground">
                                                    {row.execution_preview}
                                                </span>
                                            )}
                                        </div>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            —
                                        </span>
                                    )}
                                </TableCell>
                                <TableCell className="h-11 px-4 text-sm">
                                    {row.message ? (
                                        <span className="truncate text-xs">
                                            {row.message}
                                        </span>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            —
                                        </span>
                                    )}
                                </TableCell>
                                <TableCell className="h-11 w-px px-5 whitespace-nowrap">
                                    {row.user ? (
                                        <span className="font-mono text-xs">
                                            {row.user}
                                        </span>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            —
                                        </span>
                                    )}
                                </TableCell>
                            </TableRow>
                        ))
                    )}
                </TableBody>
            </Table>
            <TablePagination pagination={pagination} onPage={() => {}} />
        </div>
    );
}
