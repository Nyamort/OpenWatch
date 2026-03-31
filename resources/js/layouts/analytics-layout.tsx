import type { ReactNode } from 'react';
import { PeriodSelector } from '@/components/analytics/period-selector';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

interface AnalyticsLayoutProps {
    children: ReactNode;
    period: string;
    breadcrumbs?: BreadcrumbItem[];
}

export default function AnalyticsLayout({
    children,
    period,
    breadcrumbs = [],
}: AnalyticsLayoutProps) {
    return (
        <AppLayout
            breadcrumbs={breadcrumbs}
            actions={<PeriodSelector current={period} />}
        >
            <div className="flex flex-col gap-6 p-6">{children}</div>
        </AppLayout>
    );
}
