import { Deferred, Head, usePage } from '@inertiajs/react';
import { ChartsSkeleton, TableSkeleton } from '@/components/analytics/skeletons';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { index as scheduledTasksIndex } from '@/routes/analytics/scheduled-tasks';
import { ScheduledTaskTypeCharts } from './partials/scheduled-task-type-charts';
import { ScheduledTaskTypeTable } from './partials/scheduled-task-type-table';
import type {
    Pagination,
    ScheduledTaskTypeGraphBucket,
    ScheduledTaskTypeSortKey,
    ScheduledTaskTypeStats,
    ScheduledTaskRunRow,
    SortDir,
} from './types';

interface Props {
    graph?: ScheduledTaskTypeGraphBucket[];
    stats?: ScheduledTaskTypeStats;
    runs?: ScheduledTaskRunRow[];
    pagination?: Pagination;
    name: string;
    period: string;
    sort: ScheduledTaskTypeSortKey;
    direction: SortDir;
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
                <ScheduledTaskTypeCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['runs', 'pagination']}
                fallback={<TableSkeleton />}
            >
                <ScheduledTaskTypeTable
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
