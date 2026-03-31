import { Deferred, Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { ScheduledTaskCharts } from './partials/scheduled-task-charts';
import { ScheduledTaskTable } from './partials/scheduled-task-table';
import type {
    Pagination,
    ScheduledTaskGraphBucket,
    ScheduledTaskRow,
    ScheduledTaskSortKey,
    ScheduledTaskStats,
    SortDir,
} from './types';

interface Props {
    graph?: ScheduledTaskGraphBucket[];
    stats?: ScheduledTaskStats;
    tasks?: ScheduledTaskRow[];
    pagination?: Pagination;
    period: string;
    sort: ScheduledTaskSortKey;
    direction: SortDir;
    search: string;
}

const breadcrumbs = [{ title: 'Scheduled Tasks', href: '#' }];

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

export default function ScheduledTasksIndex({
    graph,
    stats,
    tasks,
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
                <ScheduledTaskCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['tasks', 'pagination']}
                fallback={<TableSkeleton />}
            >
                <ScheduledTaskTable
                    tasks={tasks!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                    search={search}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
