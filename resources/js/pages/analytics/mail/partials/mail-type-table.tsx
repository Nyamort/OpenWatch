import { Paperclip, Users } from 'lucide-react';
import { useState } from 'react';
import { InfoRow } from '@/components/analytics/detail-card';
import { SortableHead } from '@/components/analytics/table/sortable-head';
import { TablePagination } from '@/components/analytics/table/table-pagination';
import { Badge } from '@/components/ui/badge';
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/components/ui/popover';
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
import type {
    MailRunRow,
    MailTypeSortKey,
    Pagination,
    SortDir,
} from '../types';

interface RecipientsProps {
    to: number;
    cc: number;
    bcc: number;
    attachments: number;
}

function Recipients({ to, cc, bcc, attachments }: RecipientsProps) {
    const total = to + cc + bcc;
    const [open, setOpen] = useState(false);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <button
                    className="inline-flex cursor-default items-stretch overflow-hidden rounded-md border border-border text-xs tabular-nums transition-colors hover:bg-muted/50"
                    onMouseEnter={() => setOpen(true)}
                    onMouseLeave={() => setOpen(false)}
                >
                    <span className="flex items-center gap-1 px-2 py-0.5 text-muted-foreground">
                        <Users className="size-3 shrink-0" />
                        {total}
                    </span>
                    <span className="w-px bg-border" />
                    <span className="flex items-center gap-1 px-2 py-0.5 text-muted-foreground">
                        <Paperclip className="size-3 shrink-0" />
                        {attachments}
                    </span>
                </button>
            </PopoverTrigger>
            <PopoverContent
                side="top"
                className="w-44 p-3"
                onMouseEnter={() => setOpen(true)}
                onMouseLeave={() => setOpen(false)}
            >
                <InfoRow label="To" value={to} />
                <InfoRow label="CC" value={cc} />
                <InfoRow label="BCC" value={bcc} />
                <div className="my-1 border-t border-border" />
                <InfoRow label="Attachments" value={attachments} />
            </PopoverContent>
        </Popover>
    );
}

interface MailTypeTableProps {
    runs: MailRunRow[];
    pagination: Pagination;
    sort: MailTypeSortKey;
    direction: SortDir;
    count: number;
}

export function MailTypeTable({
    runs,
    pagination,
    sort,
    direction,
    count,
}: MailTypeTableProps) {
    const { handlePage, handleSort } = useAnalyticsTable<MailTypeSortKey>({
        search: '',
        only: ['runs', 'pagination', 'sort', 'direction'],
    });

    const onSort = (col: string) =>
        handleSort(col as MailTypeSortKey, sort, direction);

    return (
        <div className="flex flex-col gap-3">
            <div className="flex items-center gap-2">
                <span className="text-sm font-medium">
                    {count.toLocaleString()} {count === 1 ? 'mail' : 'mails'}
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
                            Mailer
                        </TableHead>
                        <TableHead className="h-11 px-4 text-xs font-medium tracking-wide text-muted-foreground uppercase">
                            Subject
                        </TableHead>
                        <TableHead className="h-11 w-px px-4 text-xs font-medium tracking-wide whitespace-nowrap text-muted-foreground uppercase">
                            Recipients
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
                                No mails recorded for this period.
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
                                    <Badge
                                        variant="outline"
                                        className="font-mono text-xs"
                                    >
                                        {row.mailer}
                                    </Badge>
                                </TableCell>
                                <TableCell className="h-11 max-w-xs px-4">
                                    {row.subject ? (
                                        <span className="truncate text-sm">
                                            {row.subject}
                                        </span>
                                    ) : (
                                        <span className="text-muted-foreground">
                                            —
                                        </span>
                                    )}
                                </TableCell>
                                <TableCell className="h-11 w-px px-4 whitespace-nowrap">
                                    <Recipients
                                        to={row.to}
                                        cc={row.cc}
                                        bcc={row.bcc}
                                        attachments={row.attachments}
                                    />
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
