import { OctagonAlert, Terminal } from 'lucide-react';
import { AnalyticsTableHeader } from '@/components/analytics/table/analytics-table-header';
import { SortableHead } from '@/components/analytics/table/sortable-head';
import { TablePagination } from '@/components/analytics/table/table-pagination';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useAnalyticsTable } from '@/hooks/use-analytics-table';
import { formatDuration } from '../../requests/partials/request-charts';
import type { CommandRow, CommandSortKey, Pagination, SortDir } from '../types';

interface CommandTableProps {
    commands: CommandRow[];
    pagination: Pagination;
    sort: CommandSortKey;
    direction: SortDir;
    search: string;
}

export function CommandTable({ commands, pagination, sort, direction, search }: CommandTableProps) {
    const { searchValue, handleSearch, handlePage, handleSort } = useAnalyticsTable<CommandSortKey>({
        search,
        only: ['commands', 'pagination', 'sort', 'direction'],
    });

    const onSort = (col: string) => handleSort(col as CommandSortKey, sort, direction);

    return (
        <div className="flex flex-col gap-3">
            <AnalyticsTableHeader
                icon={Terminal}
                label="Commands"
                count={pagination.total}
                search={searchValue}
                searchPlaceholder="Search commands..."
                onSearch={handleSearch}
            />
            <Table className="border-separate border-spacing-y-1.5">
                <TableHeader className="[&_tr]:border-0">
                    <TableRow className="border-0 hover:bg-transparent shadow-sm shadow-black/4 [&_th]:border-y [&_th]:border-border [&_th:first-child]:border-l [&_th:first-child]:rounded-l-lg [&_th:last-child]:border-r [&_th:last-child]:rounded-r-lg [&_th]:bg-muted/50">
                        <SortableHead column="name" sort={sort} direction={direction} onSort={onSort} className="h-11 px-5 text-xs font-medium">
                            Command
                        </SortableHead>
                        <SortableHead column="successful" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            Success
                        </SortableHead>
                        <SortableHead column="failed" sort={sort} direction={direction} onSort={onSort} align="right" className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium">
                            Failed
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
                        <TableHead className="h-11 w-px whitespace-nowrap rounded-r-lg border-y border-r border-border bg-muted/50 pr-5" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {commands.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell colSpan={7} className="py-12 text-center text-sm text-muted-foreground">
                                No commands recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        commands.map((row, i) => (
                            <TableRow
                                key={i}
                                className="bg-surface group/row border-0 hover:bg-transparent cursor-pointer shadow-sm shadow-black/4 [&_td]:border-y [&_td]:border-border [&_td:first-child]:border-l [&_td:first-child]:rounded-l-lg [&_td:last-child]:border-r [&_td:last-child]:rounded-r-lg [&_td]:bg-surface hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td]:transition-colors [&_td]:duration-150"
                            >
                                <TableCell className="h-11 overflow-hidden px-5">
                                    <span className="truncate font-mono text-sm">
                                        {row.name ?? 'Unknown Command'}
                                    </span>
                                </TableCell>
                                <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                    {row.successful.toLocaleString()}
                                </TableCell>
                                <TableCell className={`h-11 w-px whitespace-nowrap px-4 text-right tabular-nums ${row.failed === 0 ? 'text-muted-foreground' : 'text-red-500'}`}>
                                    <div className="flex items-center justify-end gap-1">
                                        {row.failed > 0 && <OctagonAlert className="size-3 shrink-0" />}
                                        {row.failed.toLocaleString()}
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
                                <TableCell className="h-11 w-px whitespace-nowrap pr-5 text-right">
                                    <Terminal className="text-muted-foreground group-hover/row:text-emerald-500 size-4 transition-colors duration-150" />
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
