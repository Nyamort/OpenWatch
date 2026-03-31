import { Deferred, Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { JobCharts } from './partials/job-charts';
import { JobTable } from './partials/job-table';
import type {
    JobGraphBucket,
    JobRow,
    JobSortKey,
    JobStats,
    Pagination,
    SortDir,
} from './types';

interface Props {
    graph?: JobGraphBucket[];
    stats?: JobStats;
    jobs?: JobRow[];
    pagination?: Pagination;
    period: string;
    sort: JobSortKey;
    direction: SortDir;
    search: string;
}

const breadcrumbs = [{ title: 'Jobs', href: '#' }];

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

export default function JobsIndex({
    graph,
    stats,
    jobs,
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
                <JobCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['jobs', 'pagination']}
                fallback={<TableSkeleton />}
            >
                <JobTable
                    jobs={jobs!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                    search={search}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
