import { Deferred, Head, usePage } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { index as scheduledTasksIndex } from '@/routes/analytics/scheduled-tasks';
import { ScheduledTaskDetailCharts } from './partials/scheduled-task-detail-charts';
import { ScheduledTaskDetailTable } from './partials/scheduled-task-detail-table';
import type {
    Pagination,
    ScheduledTaskDetailGraphBucket,
    ScheduledTaskDetailSortKey,
    ScheduledTaskDetailStats,
    ScheduledTaskRunRow,
    SortDir,
} from './types';

interface Props {
    graph?: ScheduledTaskDetailGraphBucket[];
    stats?: ScheduledTaskDetailStats;
    runs?: ScheduledTaskRunRow[];
    pagination?: Pagination;
    name: string;
    period: string;
    sort: ScheduledTaskDetailSortKey;
    direction: SortDir;
}

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
        <div className="flex flex-col gap-1.5">
            <div className="h-11 animate-pulse rounded-lg bg-muted/40" />
            {Array.from({ length: 8 }).map((_, i) => (
                <div
                    key={i}
                    className="h-11 animate-pulse rounded-lg bg-muted/20"
                />
            ))}
        </div>
    );
}

export default function ScheduledTaskType({
    graph,
    stats,
    runs,
    pagination,
    name,
    period,
    sort,
    direction,
}: Props) {
    const { props } = usePage();
    const { activeOrganization, activeProject, activeEnvironment } = props as {
        activeOrganization?: { slug: string } | null;
        activeProject?: { slug: string } | null;
        activeEnvironment?: { slug: string } | null;
    };

    const breadcrumbs = [
        {
            title: 'Scheduled Tasks',
            href:
                activeOrganization && activeProject && activeEnvironment
                    ? scheduledTasksIndex.url({ environment: activeEnvironment.slug })
                    : '#',
        },
        { title: name || 'Unknown Task', href: '#' },
    ];

    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head />
            <Deferred data={['graph', 'stats']} fallback={<ChartsSkeleton />}>
                <ScheduledTaskDetailCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['runs', 'pagination']}
                fallback={<TableSkeleton />}
            >
                <ScheduledTaskDetailTable
                    runs={runs!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                    environment={activeEnvironment?.slug ?? ''}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
