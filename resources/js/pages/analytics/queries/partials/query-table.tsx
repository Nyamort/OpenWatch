import { Link, usePage } from '@inertiajs/react';
import { ArrowUpRight, Check, Copy, Database } from 'lucide-react';
import { format as formatSql } from 'sql-formatter';
import { useRef, useState } from 'react';
import { AnalyticsTableHeader } from '@/components/analytics/table/analytics-table-header';
import SqlSyntaxHighlighter from '@/components/analytics/sql-syntax-highlighter';
import { SortableHead } from '@/components/analytics/table/sortable-head';
import { TablePagination } from '@/components/analytics/table/table-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useAnalyticsTable } from '@/hooks/use-analytics-table';
import { show } from '@/routes/analytics/queries';
import { formatDuration } from '../../requests/partials/request-charts';
import type { Pagination, QueryRow, QuerySortKey, SortDir } from '../types';


interface QueryTableProps {
    queries: QueryRow[];
    pagination: Pagination;
    sort: QuerySortKey;
    direction: SortDir;
    search: string;
}

function QueryCell({ query }: { query: string }) {
    const [open, setOpen] = useState(false);
    const [copied, setCopied] = useState(false);
    const openTimer = useRef<ReturnType<typeof setTimeout> | null>(null);
    const closeTimer = useRef<ReturnType<typeof setTimeout> | null>(null);

    function handleMouseEnter() {
        if (closeTimer.current) clearTimeout(closeTimer.current);
        openTimer.current = setTimeout(() => setOpen(true), 800);
    }

    function handleMouseLeave() {
        if (openTimer.current) clearTimeout(openTimer.current);
        closeTimer.current = setTimeout(() => setOpen(false), 150);
    }

    function handlePopoverEnter() {
        if (closeTimer.current) clearTimeout(closeTimer.current);
    }

    function handleCopy() {
        navigator.clipboard.writeText(query);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    }

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <div onMouseEnter={handleMouseEnter} onMouseLeave={handleMouseLeave}>
                    <SqlSyntaxHighlighter className="overflow-hidden" wrapLongLines={false}>
                        {query}
                    </SqlSyntaxHighlighter>
                </div>
            </PopoverTrigger>
            <PopoverContent className="w-xl p-0" align="start">
                <div className="flex items-center justify-between border-b border-border px-3 py-2">
                    <span className="text-sm font-medium">Query</span>
                    <Button variant="ghost" size="icon" className="size-7" onClick={handleCopy}>
                        {copied ? <Check className="size-3.5 text-emerald-500" /> : <Copy className="size-3.5" />}
                    </Button>
                </div>
                <div
                    className="max-h-64 overflow-y-auto p-3"
                    onMouseEnter={handlePopoverEnter}
                    onMouseLeave={handleMouseLeave}
                >
                    <SqlSyntaxHighlighter wrapLongLines>
                        {formatSql(query, { language: 'sql' })}
                    </SqlSyntaxHighlighter>
                </div>
            </PopoverContent>
        </Popover>
    );
}

export function QueryTable({ queries, pagination, sort, direction, search }: QueryTableProps) {
    const { props } = usePage();
    const { activeOrganization, activeProject, activeEnvironment } = props as unknown as {
        activeOrganization: { slug: string };
        activeProject: { slug: string };
        activeEnvironment: { slug: string };
    };

    const { searchValue, handleSearch, handlePage, handleSort } = useAnalyticsTable<QuerySortKey>({
        search,
        only: ['queries', 'pagination', 'sort', 'direction'],
    });

    const onSort = (col: string) => handleSort(col as QuerySortKey, sort, direction);

    return (
        <div className="flex flex-col gap-3">
            <AnalyticsTableHeader
                icon={Database}
                label="Queries"
                count={pagination.total}
                search={searchValue}
                searchPlaceholder="Search queries..."
                onSearch={handleSearch}
            />
            <Table className="border-separate border-spacing-y-1.5" containerClassName="overflow-x-visible">
                <TableHeader className="sticky top-16 z-10 backdrop-blur-sm [&_tr]:border-0">
                    <TableRow className="border-0 shadow-sm shadow-black/4 hover:bg-transparent [&_th]:border-y [&_th]:border-border [&_th]:bg-muted/50 [&_th:first-child]:rounded-l-lg [&_th:first-child]:border-l [&_th:last-child]:rounded-r-lg [&_th:last-child]:border-r">
                        <SortableHead
                            column="query"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            className="h-11 px-5 text-xs font-medium"
                        >
                            Query
                        </SortableHead>
                        <SortableHead
                            column="connection"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            className="h-11 w-px px-4 text-xs font-medium whitespace-nowrap"
                        >
                            Connection
                        </SortableHead>
                        <SortableHead
                            column="calls"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            Calls
                        </SortableHead>
                        <SortableHead
                            column="total"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            Total
                        </SortableHead>
                        <SortableHead
                            column="avg"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            AVG
                        </SortableHead>
                        <SortableHead
                            column="p95"
                            sort={sort}
                            direction={direction}
                            onSort={onSort}
                            align="right"
                            className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                        >
                            P95
                        </SortableHead>
                        <TableHead className="h-11 w-px pr-5" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {queries.length === 0 ? (
                        <TableRow className="border-0 hover:bg-transparent">
                            <TableCell
                                colSpan={7}
                                className="py-12 text-center text-sm text-muted-foreground"
                            >
                                No queries recorded for this period.
                            </TableCell>
                        </TableRow>
                    ) : (
                        queries.map((row) => (
                            <TableRow
                                key={row.sql_hash}
                                className="group/row cursor-pointer border-0 bg-surface shadow-sm shadow-black/4 hover:bg-transparent [&_td]:border-y [&_td]:border-border [&_td]:bg-surface [&_td]:transition-colors [&_td]:duration-150 hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td:first-child]:rounded-l-lg [&_td:first-child]:border-l [&_td:last-child]:rounded-r-lg [&_td:last-child]:border-r"
                            >
                                <TableCell className="h-11 overflow-hidden px-5 md:max-w-px">
                                    <QueryCell query={row.query} />
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 whitespace-nowrap">
                                    <Badge
                                        variant="outline"
                                        className="font-mono text-xs"
                                    >
                                        {row.connection}
                                    </Badge>
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right font-medium whitespace-nowrap tabular-nums">
                                    {row.calls.toLocaleString()}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right whitespace-nowrap tabular-nums">
                                    {formatDuration(row.total)}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right whitespace-nowrap tabular-nums">
                                    {formatDuration(row.avg)}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 text-right whitespace-nowrap tabular-nums">
                                    {formatDuration(row.p95)}
                                </TableCell>
                                <TableCell className="h-11 w-px pr-5">
                                    <div className="flex items-center justify-end">
                                        <Link
                                            href={show.url({
                                                organization:
                                                    activeOrganization.slug,
                                                project: activeProject.slug,
                                                environment:
                                                    activeEnvironment.slug,
                                                query: row.sql_hash,
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
