import { Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { JobCharts } from './partials/job-charts';
import { JobTable } from './partials/job-table';
import type { JobGraphBucket, JobRow, JobSortKey, JobStats, Pagination, SortDir } from './types';

interface Props {
    graph: JobGraphBucket[];
    stats: JobStats;
    jobs: JobRow[];
    pagination: Pagination;
    period: string;
    sort: JobSortKey;
    direction: SortDir;
    search: string;
}

const breadcrumbs = [{ title: 'Jobs', href: '#' }];

export default function JobsIndex({ graph, stats, jobs, pagination, period, sort, direction, search }: Props) {
    return (
        <AnalyticsLayout period={period} breadcrumbs={breadcrumbs}>
            <Head />
            <JobCharts graph={graph} stats={stats} />
            <JobTable jobs={jobs} pagination={pagination} sort={sort} direction={direction} search={search} />
        </AnalyticsLayout>
    );
}
