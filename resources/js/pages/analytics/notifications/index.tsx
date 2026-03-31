import { Deferred, Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { NotificationCharts } from './partials/notification-charts';
import { NotificationTable } from './partials/notification-table';
import type {
    NotificationGraphBucket,
    NotificationRow,
    NotificationSortKey,
    NotificationStats,
    Pagination,
    SortDir,
} from './types';

interface Props {
    graph?: NotificationGraphBucket[];
    stats?: NotificationStats;
    notifications?: NotificationRow[];
    pagination?: Pagination;
    period: string;
    sort: NotificationSortKey;
    direction: SortDir;
    search: string;
}

const breadcrumbs = [{ title: 'Notifications', href: '#' }];

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

export default function NotificationsIndex({
    graph,
    stats,
    notifications,
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
                <NotificationCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['notifications', 'pagination']}
                fallback={<TableSkeleton />}
            >
                <NotificationTable
                    notifications={notifications!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                    search={search}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
