import { PeriodSelector } from '@/components/analytics/period-selector';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { ReactNode } from 'react';

interface AnalyticsLayoutProps {
    children: ReactNode;
    title: string;
    period: string;
    breadcrumbs?: BreadcrumbItem[];
}

export default function AnalyticsLayout({ children, title, period, breadcrumbs = [] }: AnalyticsLayoutProps) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <div className="flex flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-xl font-semibold">{title}</h1>
                    <PeriodSelector current={period} />
                </div>
                {children}
            </div>
        </AppLayout>
    );
}
