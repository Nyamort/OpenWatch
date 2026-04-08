import { Deferred, Head } from '@inertiajs/react';
import { TableSkeleton } from '@/components/analytics/skeletons';
import AnalyticsLayout from '@/layouts/analytics-layout';
import { LogTable } from './partials/log-table';
import type { LogRow, Pagination } from './types';

interface Props {
    logs?: LogRow[];
    pagination?: Pagination;
    period: string;
    search: string;
    level: string | null;
}

export default function LogsIndex({ logs, pagination, period, search }: Props) {
    return (
        <AnalyticsLayout period={period}>
            <Head />
            <Deferred data={['logs', 'pagination']} fallback={<TableSkeleton />}>
                <LogTable
                    logs={logs!}
                    pagination={pagination!}
                    search={search}
                />
            </Deferred>
        </AnalyticsLayout>
    );
}
