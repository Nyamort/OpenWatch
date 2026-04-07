import { Deferred, Head } from '@inertiajs/react';
import { ChartsSkeleton, TableWithSearchSkeleton } from '@/components/analytics/skeletons';
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
                fallback={<TableWithSearchSkeleton />}
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
