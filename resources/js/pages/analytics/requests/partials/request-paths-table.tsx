import { router, usePage } from '@inertiajs/react';
import { ArrowUpRight, ChevronDown, ChevronUp, ChevronsUpDown, FolderClosed, Globe, PanelRight } from 'lucide-react';
import { HttpMethodBadge } from '@/components/analytics/http-method-badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { PathRow, SortDir, SortKey } from '../types';
import { formatDuration } from './request-charts';

function SortIcon({ column, sort, direction }: { column: SortKey; sort: SortKey; direction: SortDir }) {
    if (sort !== column) return <ChevronsUpDown className="size-3 opacity-40" />;
    return direction === 'asc' ? <ChevronUp className="size-3" /> : <ChevronDown className="size-3" />;
}

interface RequestPathsTableProps {
    paths: PathRow[];
    sort: SortKey;
    direction: SortDir;
}

export function RequestPathsTable({ paths, sort, direction }: RequestPathsTableProps) {
    const { url } = usePage();

    function handleSort(key: SortKey) {
        const urlObj = new URL(url, window.location.origin);
        const newDir: SortDir = sort === key && direction === 'desc' ? 'asc' : 'desc';
        urlObj.searchParams.set('sort', key);
        urlObj.searchParams.set('direction', newDir);
        router.get(urlObj.pathname + urlObj.search, {}, { preserveScroll: true, preserveState: true, only: ['paths', 'sort', 'direction'] });
    }

    return (
        <Table className="border-separate border-spacing-y-1.5">
            <TableHeader className="[&_tr]:border-0">
                <TableRow className="border-0 hover:bg-transparent shadow-sm shadow-black/4 [&_th]:border-y [&_th]:border-border [&_th:first-child]:border-l [&_th:first-child]:rounded-l-lg [&_th:last-child]:border-r [&_th:last-child]:rounded-r-lg [&_th]:bg-muted/50">
                    <TableHead className="h-11 w-px whitespace-nowrap pl-5 text-xs font-medium uppercase tracking-wide">
                        <button onClick={() => handleSort('method')} className="flex cursor-pointer items-center gap-1 hover:text-foreground">
                            Method <SortIcon column="method" sort={sort} direction={direction} />
                        </button>
                    </TableHead>
                    <TableHead className="h-11 px-4 text-xs font-medium uppercase tracking-wide">
                        <button onClick={() => handleSort('path')} className="flex cursor-pointer items-center gap-1 hover:text-foreground">
                            Path <SortIcon column="path" sort={sort} direction={direction} />
                        </button>
                    </TableHead>
                    <TableHead className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium uppercase tracking-wide">
                        <button onClick={() => handleSort('2xx')} className="flex w-full cursor-pointer items-center justify-end gap-1 hover:text-foreground">
                            <SortIcon column="2xx" sort={sort} direction={direction} /> 1/2/3xx
                        </button>
                    </TableHead>
                    <TableHead className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium uppercase tracking-wide">
                        <button onClick={() => handleSort('4xx')} className="flex w-full cursor-pointer items-center justify-end gap-1 hover:text-foreground">
                            <SortIcon column="4xx" sort={sort} direction={direction} /> 4xx
                        </button>
                    </TableHead>
                    <TableHead className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium uppercase tracking-wide">
                        <button onClick={() => handleSort('5xx')} className="flex w-full cursor-pointer items-center justify-end gap-1 hover:text-foreground">
                            <SortIcon column="5xx" sort={sort} direction={direction} /> 5xx
                        </button>
                    </TableHead>
                    <TableHead className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium uppercase tracking-wide">
                        <button onClick={() => handleSort('total')} className="flex w-full cursor-pointer items-center justify-end gap-1 hover:text-foreground">
                            <SortIcon column="total" sort={sort} direction={direction} /> Total
                        </button>
                    </TableHead>
                    <TableHead className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium uppercase tracking-wide">
                        <button onClick={() => handleSort('avg')} className="flex w-full cursor-pointer items-center justify-end gap-1 hover:text-foreground">
                            <SortIcon column="avg" sort={sort} direction={direction} /> AVG
                        </button>
                    </TableHead>
                    <TableHead className="h-11 w-px whitespace-nowrap px-4 text-right text-xs font-medium uppercase tracking-wide">
                        <button onClick={() => handleSort('p95')} className="flex w-full cursor-pointer items-center justify-end gap-1 hover:text-foreground">
                            <SortIcon column="p95" sort={sort} direction={direction} /> P95
                        </button>
                    </TableHead>
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
                                        ? <Globe className="size-4 shrink-0 stroke-1 text-muted-foreground" />
                                        : <FolderClosed className="size-4 shrink-0 stroke-1 text-muted-foreground" />
                                    }
                                    <span className="truncate font-mono text-sm">
                                        {row.path ?? 'Unmatched Route'}
                                    </span>
                                </div>
                            </TableCell>
                            <TableCell className="h-11 w-px whitespace-nowrap px-4 text-right tabular-nums">
                                {row['2xx'].toLocaleString()}
                            </TableCell>
                            <TableCell className={`h-11 w-px whitespace-nowrap px-4 text-right tabular-nums ${row['4xx'] === 0 ? 'text-muted-foreground' : ''}`}>
                                {row['4xx'].toLocaleString()}
                            </TableCell>
                            <TableCell className={`h-11 w-px whitespace-nowrap px-4 text-right tabular-nums ${row['5xx'] === 0 ? 'text-muted-foreground' : 'text-red-500'}`}>
                                {row['5xx'].toLocaleString()}
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
                                    <div className="flex items-center rounded-sm border border-border/20 bg-muted/30 uppercase text-foreground/10 transition-colors group-hover/row:border-border/60 group-hover/row:text-green-500 dark:border-white/7 dark:bg-white/1 dark:text-white/10 dark:group-hover/row:border-white/15 dark:group-hover/row:text-green-500">
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
    );
}
