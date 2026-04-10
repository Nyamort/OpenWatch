import { Head, router, usePage } from '@inertiajs/react';
import { formatDistanceToNow, parseISO } from 'date-fns';
import { ArrowUpRight, Bug } from 'lucide-react';
import { SortableHead } from '@/components/analytics/table/sortable-head';
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
import AppLayout from '@/layouts/app-layout';
import { show } from '@/routes/issues';
import type { BreadcrumbItem } from '@/types';

type IssueSortKey = 'id' | 'last_seen_at' | 'occurrence_count' | 'first_seen_at';
type SortDir = 'asc' | 'desc';

interface Issue {
    id: number;
    title: string;
    type: string;
    status: string;
    priority: string;
    occurrence_count: number;
    first_seen_at: string;
    last_seen_at: string;
    assignee: { id: number; name: string; email: string } | null;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Props {
    issues: Issue[];
    pagination: Pagination;
    sort: IssueSortKey;
    direction: SortDir;
}

const priorityVariantMap: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    critical: 'destructive',
    high: 'default',
    medium: 'secondary',
    low: 'outline',
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Issues', href: '#' }];

export default function IssuesIndex({
    issues,
    pagination,
    sort,
    direction,
}: Props) {
    const { props } = usePage();
    const { activeEnvironment } = props as unknown as {
        activeEnvironment: { slug: string };
    };

    const { handleSort } = useAnalyticsTable<IssueSortKey>({
        search: '',
        only: ['issues', 'pagination', 'sort', 'direction'],
    });

    const onSort = (col: string) =>
        handleSort(col as IssueSortKey, sort, direction);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Issues" />
            <div className="flex flex-col gap-3 p-6">
                <div className="flex items-center gap-2">
                    <Bug className="size-4 text-muted-foreground" />
                    <span className="text-sm font-medium">Issues</span>
                    <span className="text-sm text-muted-foreground">
                        {pagination.total.toLocaleString()}
                    </span>
                </div>

                <Table
                    className="border-separate border-spacing-y-1.5"
                    containerClassName="overflow-x-visible"
                >
                    <TableHeader className="sticky top-16 z-10 backdrop-blur-sm [&_tr]:border-0">
                        <TableRow className="border-0 shadow-sm shadow-black/4 hover:bg-transparent [&_th]:border-y [&_th]:border-border [&_th]:bg-muted/50 [&_th:first-child]:rounded-l-lg [&_th:first-child]:border-l [&_th:last-child]:rounded-r-lg [&_th:last-child]:border-r">
                            <SortableHead
                                column="id"
                                sort={sort}
                                direction={direction}
                                onSort={onSort}
                                className="h-11 w-px pl-5 text-xs font-medium whitespace-nowrap"
                            >
                                ID
                            </SortableHead>
                            <TableHead className="h-11 w-px px-4 text-xs font-medium whitespace-nowrap text-muted-foreground uppercase tracking-wide">
                                Priority
                            </TableHead>
                            <TableHead className="h-11 px-4 text-xs font-medium text-muted-foreground uppercase tracking-wide">
                                Issue
                            </TableHead>
                            <SortableHead
                                column="occurrence_count"
                                sort={sort}
                                direction={direction}
                                onSort={onSort}
                                align="right"
                                className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap"
                            >
                                Count
                            </SortableHead>
                            <TableHead className="h-11 w-px px-4 text-right text-xs font-medium whitespace-nowrap text-muted-foreground uppercase tracking-wide">
                                Users
                            </TableHead>
                            <SortableHead
                                column="first_seen_at"
                                sort={sort}
                                direction={direction}
                                onSort={onSort}
                                className="h-11 w-px px-4 text-xs font-medium whitespace-nowrap"
                            >
                                First Seen
                            </SortableHead>
                            <SortableHead
                                column="last_seen_at"
                                sort={sort}
                                direction={direction}
                                onSort={onSort}
                                className="h-11 w-px px-4 text-xs font-medium whitespace-nowrap"
                            >
                                Last Seen
                            </SortableHead>
                            <TableHead className="h-11 w-px pr-5 text-xs font-medium whitespace-nowrap text-muted-foreground uppercase tracking-wide">
                                Assigned
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {issues.length === 0 ? (
                            <TableRow className="border-0 hover:bg-transparent">
                                <TableCell
                                    colSpan={8}
                                    className="py-12 text-center text-sm text-muted-foreground"
                                >
                                    No issues found.
                                </TableCell>
                            </TableRow>
                        ) : (
                            issues.map((issue) => (
                                <TableRow
                                    key={issue.id}
                                    onClick={() =>
                                        router.visit(
                                            show.url({
                                                environment:
                                                    activeEnvironment.slug,
                                                issue: issue.id,
                                            }),
                                        )
                                    }
                                    className="group/row cursor-pointer border-0 bg-surface shadow-sm shadow-black/4 hover:bg-transparent [&_td]:border-y [&_td]:border-border [&_td]:bg-surface [&_td]:transition-colors [&_td]:duration-150 hover:[&_td]:bg-muted/50 dark:hover:[&_td]:bg-muted/70 [&_td:first-child]:rounded-l-lg [&_td:first-child]:border-l [&_td:last-child]:rounded-r-lg [&_td:last-child]:border-r"
                                >
                                    <TableCell className="h-11 w-px pl-5 font-mono text-xs text-muted-foreground tabular-nums">
                                        #{issue.id}
                                    </TableCell>
                                    <TableCell className="h-11 w-px px-4">
                                        <Badge
                                            variant={
                                                priorityVariantMap[
                                                    issue.priority
                                                ] ?? 'outline'
                                            }
                                            className="capitalize"
                                        >
                                            {issue.priority}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="overflow-hidden px-4 py-2">
                                        <div className="flex min-w-0 items-start gap-2">
                                            <Badge
                                                variant="outline"
                                                className="mt-0.5 shrink-0"
                                            >
                                                {issue.type}
                                            </Badge>
                                            <span className="truncate text-sm font-medium">
                                                {issue.title}
                                            </span>
                                        </div>
                                    </TableCell>
                                    <TableCell className="h-11 w-px px-4 text-right font-medium whitespace-nowrap tabular-nums">
                                        {issue.occurrence_count.toLocaleString()}
                                    </TableCell>
                                    <TableCell className="h-11 w-px px-4 text-right whitespace-nowrap tabular-nums text-muted-foreground">
                                        —
                                    </TableCell>
                                    <TableCell className="h-11 w-px px-4 text-sm whitespace-nowrap text-muted-foreground tabular-nums">
                                        {formatDistanceToNow(
                                            parseISO(issue.first_seen_at),
                                            { addSuffix: true },
                                        )}
                                    </TableCell>
                                    <TableCell className="h-11 w-px px-4 text-sm whitespace-nowrap text-muted-foreground tabular-nums">
                                        {formatDistanceToNow(
                                            parseISO(issue.last_seen_at),
                                            { addSuffix: true },
                                        )}
                                    </TableCell>
                                    <TableCell className="h-11 w-px pr-5">
                                        <div className="flex items-center justify-between gap-3">
                                            <span className="text-sm whitespace-nowrap text-muted-foreground">
                                                {issue.assignee
                                                    ? issue.assignee.name
                                                    : '—'}
                                            </span>
                                            <span className="flex items-center rounded-sm border border-border/20 bg-muted/30 text-foreground/10 transition-colors group-hover/row:border-border/60 group-hover/row:text-emerald-500 dark:border-white/7 dark:bg-white/1 dark:text-white/10 dark:group-hover/row:border-white/15 dark:group-hover/row:text-emerald-500">
                                                <span className="flex size-6 items-center justify-center">
                                                    <ArrowUpRight className="size-4" />
                                                </span>
                                            </span>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            ))
                        )}
                    </TableBody>
                </Table>
            </div>
        </AppLayout>
    );
}
