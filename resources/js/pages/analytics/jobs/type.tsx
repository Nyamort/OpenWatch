import { Deferred, Head, usePage } from '@inertiajs/react';
import {
    ChartsSkeleton,
    TableSkeleton,
} from '@/components/analytics/skeletons';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { index as jobsIndex } from '@/routes/analytics/jobs';
import { JobTypeCharts } from './partials/job-type-charts';
import { JobTypeTable } from './partials/job-type-table';
import type {
    JobAttemptRow,
    JobTypeGraphBucket,
    JobTypeSortKey,
    JobTypeStats,
    Pagination,
    SortDir,
} from './types';

interface Props {
    graph?: JobTypeGraphBucket[];
    stats?: JobTypeStats;
    attempts?: JobAttemptRow[];
    pagination?: Pagination;
    name: string;
    period: string;
    sort: JobTypeSortKey;
    direction: SortDir;
}

export default function JobType({
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
                <JobTypeCharts graph={graph!} stats={stats!} />
            </Deferred>
            <Deferred
                data={['attempts', 'pagination']}
                fallback={<TableSkeleton />}
            >
                <JobTypeTable
                    attempts={attempts!}
                    pagination={pagination!}
                    sort={sort}
                    direction={direction}
                    environment={activeEnvironment?.slug ?? ''}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
