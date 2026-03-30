import { Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { ExceptionCharts } from './partials/exception-charts';
import { ExceptionTable } from './partials/exception-table';
import type { ExceptionGraphBucket, ExceptionRow, ExceptionSortKey, ExceptionStats, Pagination, SortDir } from './types';

interface Props {
    graph: ExceptionGraphBucket[];
    stats: ExceptionStats;
    exceptions: ExceptionRow[];
    pagination: Pagination;
    period: string;
    sort: ExceptionSortKey;
    direction: SortDir;
    search: string;
}

const breadcrumbs = [{ title: 'Exceptions', href: '#' }];

export default function ExceptionsIndex({ graph, stats, exceptions, pagination, period, sort, direction, search }: Props) {
    return (
        <AnalyticsLayout title="Exceptions" period={period} breadcrumbs={breadcrumbs}>
            <Head title="Exceptions" />
            <ExceptionCharts graph={graph} stats={stats} />
            <ExceptionTable exceptions={exceptions} pagination={pagination} sort={sort} direction={direction} search={search} />
        </AnalyticsLayout>
    );
}
