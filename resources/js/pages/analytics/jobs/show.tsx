import { Deferred, Head, usePage } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { index as jobsIndex } from '@/routes/analytics/jobs';
import { JobDetailCharts } from './partials/job-detail-charts';
import { JobDetailTable } from './partials/job-detail-table';
import type {
    JobAttemptRow,
    JobDetailGraphBucket,
    JobDetailSortKey,
    JobDetailStats,
    Pagination,
    SortDir,
} from './types';

interface Props {
    graph?: JobDetailGraphBucket[];
    stats?: JobDetailStats;
    attempts?: JobAttemptRow[];
    pagination?: Pagination;
    name: string;
    period: string;
    sort: JobDetailSortKey;
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

export default function JobShow({
    graph,
    stats,
    attempts,
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
            title: 'Jobs',
            href:
                activeOrganization && activeProject && activeEnvironment
                    ? jobsIndex.url({ environment: activeEnvironment.slug })
                    : '#',
        },
        { title: name || 'Unknown Job', href: '#' },
    ];

    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head />
            <Deferred data={['graph', 'stats']} fallback={<ChartsSkeleton />}>
                <JobDetailCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['attempts', 'pagination']}
                fallback={<TableSkeleton />}
            >
                <JobDetailTable
                    attempts={attempts!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
