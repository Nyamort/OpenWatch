import { Deferred, Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { CommandCharts } from './partials/command-charts';
import { CommandTable } from './partials/command-table';
import type {
    CommandGraphBucket,
    CommandRow,
    CommandSortKey,
    CommandStats,
    Pagination,
    SortDir,
} from './types';

interface Props {
    graph?: CommandGraphBucket[];
    stats?: CommandStats;
    commands?: CommandRow[];
    pagination?: Pagination;
    period: string;
    sort: CommandSortKey;
    direction: SortDir;
    search: string;
}

const breadcrumbs = [{ title: 'Commands', href: '#' }];

function ChartsSkeleton() {
    return (
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {[0, 1].map((i) => (
                <div
                    key={i}
                    className="h-[206px] animate-pulse rounded-xl border bg-muted/40"
                />
            ))}
        </div>
    );
}

function TableSkeleton() {
    return (
        <div className="flex flex-col gap-3">
            <div className="h-10 w-64 animate-pulse rounded-lg bg-muted/40" />
            <div className="flex flex-col gap-1.5">
                <div className="h-11 animate-pulse rounded-lg bg-muted/40" />
                {Array.from({ length: 8 }).map((_, i) => (
                    <div
                        key={i}
                        className="h-11 animate-pulse rounded-lg bg-muted/20"
                    />
                ))}
            </div>
        </div>
    );
}

export default function CommandsIndex({
    graph,
    stats,
    commands,
    pagination,
    period,
    sort,
    direction,
    search,
}: Props) {
    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head />
            <Deferred data={['graph', 'stats']} fallback={<ChartsSkeleton />}>
                <CommandCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['commands', 'pagination']}
                fallback={<TableSkeleton />}
            >
                <CommandTable
                    commands={commands!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                    search={search}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
