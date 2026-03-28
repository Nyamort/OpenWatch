import { Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { RequestCharts } from './partials/request-charts';
import { RequestPathsTable } from './partials/request-paths-table';
import type { GraphBucket, PathRow, SortDir, SortKey, Stats } from './types';

interface Props {
    graph: GraphBucket[];
    stats: Stats;
    paths: PathRow[];
    period: string;
    sort: SortKey;
    direction: SortDir;
}

const breadcrumbs = [{ title: 'Requests', href: '#' }];

export default function RequestsIndex({ graph, stats, paths, period, sort, direction }: Props) {
    return (
        <AnalyticsLayout title="Requests" period={period} breadcrumbs={breadcrumbs}>
            <Head title="Requests" />
            <RequestCharts graph={graph} stats={stats} />
            <RequestPathsTable paths={paths} sort={sort} direction={direction} />
        </AnalyticsLayout>
    );
}
