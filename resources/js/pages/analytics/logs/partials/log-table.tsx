import { ScrollText } from 'lucide-react';
import { useState } from 'react';
import { AnalyticsTableHeader } from '@/components/analytics/table/analytics-table-header';
import { TablePagination } from '@/components/analytics/table/table-pagination';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { CodeBlock } from '@/components/ui/code-block';
import {
    Sheet,
    SheetContent,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useAnalyticsTable } from '@/hooks/use-analytics-table';
import { cn } from '@/lib/utils';
import type { LogRow, Pagination } from '../types';

const LEVEL_COLORS: Record<string, string> = {
    emergency: 'border-red-500/50 bg-red-500/10 text-red-600 dark:text-red-400',
    alert: 'border-red-500/50 bg-red-500/10 text-red-600 dark:text-red-400',
    critical: 'border-red-400/50 bg-red-400/10 text-red-500 dark:text-red-400',
    error: 'border-red-400/40 bg-red-400/8 text-red-500 dark:text-red-400',
    warning:
        'border-orange-400/50 bg-orange-400/10 text-orange-600 dark:text-orange-400',
    notice: 'border-blue-400/50 bg-blue-400/10 text-blue-600 dark:text-blue-400',
    info: 'border-emerald-400/50 bg-emerald-400/10 text-emerald-600 dark:text-emerald-500',
    debug: 'border-border bg-muted/50 text-muted-foreground',
};

function formatContext(context: string | null): string {
    if (!context) return '';
    try {
        return JSON.stringify(JSON.parse(context), null, 2);
    } catch {
        return context;
    }
}

interface LogTableProps {
    logs: LogRow[];
    pagination: Pagination;
    search: string;
}

export function LogTable({ logs, pagination, search }: LogTableProps) {
    const [selected, setSelected] = useState<LogRow | null>(null);

    const { searchValue, handleSearch, handlePage } = useAnalyticsTable({
        search,
        only: ['logs', 'pagination'],
    });

    return (
        <>
            <div className="flex flex-col gap-3">
                <AnalyticsTableHeader
                    icon={ScrollText}
                    label="Logs"
                    count={pagination.total}
                    search={searchValue}
                    searchPlaceholder="Search logs..."
                    onSearch={handleSearch}
                />
                <Table
                    className="border-separate border-spacing-y-1.5"
                    containerClassName="overflow-x-visible"
                >
                    <TableHeader className="sticky top-16 z-10 backdrop-blur-sm [&_tr]:border-0">
                        <TableRow className="border-0 shadow-sm shadow-black/4 hover:bg-transparent [&_th]:border-y [&_th]:border-border [&_th]:bg-muted/50 [&_th:first-child]:rounded-l-lg [&_th:first-child]:border-l [&_th:last-child]:rounded-r-lg [&_th:last-child]:border-r">
                            <TableHead className="h-11 w-px px-5 text-xs font-medium tracking-wide whitespace-nowrap text-muted-foreground uppercase">
                                Date
                            </TableHead>
                            <TableHead className="h-11 px-4 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Source
                            </TableHead>
                            <TableHead className="h-11 w-px px-4 text-xs font-medium tracking-wide whitespace-nowrap text-muted-foreground uppercase">
                                Level
                            </TableHead>
                            <TableHead className="h-11 px-4 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                Message
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {logs.length === 0 ? (
                            <TableRow className="border-0 hover:bg-transparent">
                                <TableCell
                                    colSpan={4}
                                    className="py-12 text-center text-sm text-muted-foreground"
                                >
                                    No logs recorded for this period.
                                </TableCell>
                            </TableRow>
                        ) : (
                            logs.map((row) => (
                                <TableRow
                                    key={row.id}
                                    onClick={() => setSelected(row)}
                                    className="cursor-pointer border-0 bg-surface shadow-sm shadow-black/4 hover:bg-transparent [&_td]:border-y [&_td]:border-border [&_td]:bg-surface [&_td]:transition-colors [&_td]:duration-150 hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td:first-child]:rounded-l-lg [&_td:first-child]:border-l [&_td:last-child]:rounded-r-lg [&_td:last-child]:border-r"
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
                                        <Badge
                                            variant="outline"
                                            className={cn(
                                                'font-mono text-xs capitalize',
                                                LEVEL_COLORS[row.level],
                                            )}
                                        >
                                            {row.level}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="h-11 max-w-sm px-4">
                                        <span className="truncate text-sm">
                                            {row.message}
                                        </span>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
                <TablePagination pagination={pagination} onPage={handlePage} />
            </div>

            <Sheet
                open={selected !== null}
                onOpenChange={(open) => !open && setSelected(null)}
            >
                <SheetContent
                    side="right"
                    className="w-full overflow-y-auto sm:max-w-lg"
                >
                    {selected && (
                        <>
                            <SheetHeader className="gap-2">
                                <div className="flex items-center gap-2 pr-6">
                                    <Badge
                                        variant="outline"
                                        className={cn(
                                            'shrink-0 font-mono text-xs capitalize',
                                            LEVEL_COLORS[selected.level],
                                        )}
                                    >
                                        {selected.level}
                                    </Badge>
                                    <SheetTitle className="text-sm leading-snug font-medium">
                                        {selected.message}
                                    </SheetTitle>
                                </div>
                            </SheetHeader>
                            <div className="flex flex-col gap-3 px-4 pb-4">
                                <Card className="gap-0 bg-surface py-0">
                                    <CardHeader className="border-b py-4">
                                        <span className="text-sm font-semibold">
                                            Source
                                        </span>
                                    </CardHeader>
                                    <CardContent className="py-4">
                                        <span className="font-mono text-sm break-all text-muted-foreground">
                                            {selected.source_preview ??
                                                selected.source ??
                                                '—'}
                                        </span>
                                    </CardContent>
                                </Card>
                                <Card className="gap-0 bg-surface py-0">
                                    <CardHeader className="border-b py-4">
                                        <span className="text-sm font-semibold">
                                            Log Context
                                        </span>
                                    </CardHeader>
                                    <CardContent className="py-4">
                                        {selected.context ? (
                                            <CodeBlock
                                                code={formatContext(
                                                    selected.context,
                                                )}
                                                language="json"
                                                className="overflow-x-auto rounded-md bg-muted p-3"
                                            />
                                        ) : (
                                            <span className="text-sm text-muted-foreground">
                                                —
                                            </span>
                                        )}
                                    </CardContent>
                                </Card>
                            </div>
                        </>
                    )}
                </SheetContent>
            </Sheet>
        </>
    );
}
