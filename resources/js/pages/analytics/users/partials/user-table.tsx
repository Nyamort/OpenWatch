import { OctagonAlert, TriangleAlert, Users } from 'lucide-react';
import { AnalyticsTableHeader } from '@/components/analytics/table/analytics-table-header';
import { SortableHead } from '@/components/analytics/table/sortable-head';
import { TablePagination } from '@/components/analytics/table/table-pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useAnalyticsTable } from '@/hooks/use-analytics-table';
import type { Pagination, SortDir, SortKey, UserRow } from '../types';

interface UserTableProps {
    users: UserRow[];
    pagination: Pagination;
    sort: SortKey;
    direction: SortDir;
    search: string;
}

export function UserTable({
    users,
    pagination,
    sort,
    direction,
    search,
}: UserTableProps) {
    const { searchValue, handleSearch, handlePage, handleSort } =
        useAnalyticsTable<SortKey>({
            search,
            only: ['users', 'pagination'],
        });

    const onSort = (col: string) => handleSort(col as SortKey, sort, direction);

    return (
        <div className="flex flex-col gap-3">
            <AnalyticsTableHeader
                icon={Users}
                label="Users"
                count={pagination.total}
                search={searchValue}
                searchPlaceholder="Search users..."
                onSearch={handleSearch}
            />
            <Table
                className="border-separate border-spacing-y-1.5"
                containerClassName="overflow-x-visible"
            >
                <TableHeader className="sticky top-16 z-10 backdrop-blur-sm [&_tr]:border-0">
                    <TableRow className="border-0 shadow-sm shadow-black/4 hover:bg-transparent [&_th]:border-y [&_th]:border-border [&_th]:bg-muted/50 [&_th:first-child]:rounded-l-lg [&_th:first-child]:border-l [&_th:last-child]:rounded-r-lg [&_th:last-child]:border-r">
                        <SortableHead
                            column="email"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            className="h-11 pl-5 text-xs font-medium"
                        >
                            Email
                        </SortableHead>
                        <SortableHead
                            column="2xx"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            1/2/3xx
                        </SortableHead>
                        <SortableHead
                            column="4xx"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            4xx
                        </SortableHead>
                        <SortableHead
                            column="5xx"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            5xx
                        </SortableHead>
                        <SortableHead
                            column="request_count"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            Requests
                        </SortableHead>
                        <SortableHead
                            column="job_count"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            Queued Jobs
                        </SortableHead>
                        <SortableHead
                            column="exception_count"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            Exceptions
                        </SortableHead>
                        <SortableHead
                            column="last_seen"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 pr-5 text-right text-xs font-medium whitespace-nowrap"
                        >
                            Last Seen
                        </SortableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {users.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell
                                colSpan={8}
                                className="py-12 text-center text-sm text-muted-foreground"
                            >
                                No authenticated users recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        users.map((row, i) => (
                            <TableRow
                                key={i}
                                className="border-0 bg-card shadow-sm shadow-black/4 [&_td]:border-y [&_td]:border-border [&_td]:bg-card [&_td]:transition-colors [&_td]:duration-150 hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td:first-child]:rounded-l-lg [&_td:first-child]:border-l [&_td:last-child]:rounded-r-lg [&_td:last-child]:border-r"
                            >
                                <TableCell className="h-11 overflow-hidden pl-5">
                                    <div className="flex min-w-0 flex-col">
                                        <span className="truncate text-sm font-medium">
                                            {row.email}
                                        </span>
                                        {row.name && (
                                            <span className="truncate text-xs text-muted-foreground">
                                                {row.name}
                                            </span>
                                        )}
                                    </div>
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right whitespace-nowrap tabular-nums">
                                    {row['2xx'].toLocaleString()}
                                </TableCell>
                                <TableCell
                                    className={`h-11 w-px px-4 text-right whitespace-nowrap tabular-nums ${row['4xx'] === 0 ? 'text-muted-foreground' : 'text-amber-500'}`}
                                >
                                    <div className="flex items-center justify-end gap-1">
                                        {row['4xx'] > 0 && (
                                            <TriangleAlert className="size-3" />
                                        )}
                                        {row['4xx'].toLocaleString()}
                                    </div>
                                </TableCell>
                                <TableCell
                                    className={`h-11 w-px px-4 text-right whitespace-nowrap tabular-nums ${row['5xx'] === 0 ? 'text-muted-foreground' : 'text-red-500'}`}
                                >
                                    <div className="flex items-center justify-end gap-1">
                                        {row['5xx'] > 0 && (
                                            <OctagonAlert className="size-3 shrink-0" />
                                        )}
                                        {row['5xx'].toLocaleString()}
                                    </div>
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right font-medium whitespace-nowrap tabular-nums">
                                    {row.request_count.toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right whitespace-nowrap tabular-nums">
                                    {row.job_count.toLocaleString()}
                                </TableCell>
                                <TableCell
                                    className={`h-11 w-px px-4 text-right whitespace-nowrap tabular-nums ${row.exception_count === 0 ? 'text-muted-foreground' : 'text-red-500'}`}
                                >
                                    {row.exception_count.toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 pr-5 text-right text-sm whitespace-nowrap text-muted-foreground tabular-nums">
                                    {new Date(row.last_seen).toLocaleString(
                                        [],
                                        {
                                            day: 'numeric',
                                            month: 'short',
                                            hour: '2-digit',
                                            minute: '2-digit',
                                        },
                                    )}
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
