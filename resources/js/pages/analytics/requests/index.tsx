import { Head } from '@inertiajs/react';
import AnalyticsLayout from '@/layouts/analytics-layout';

interface Props {
    period: string;
}

const breadcrumbs = [{ title: 'Requests', href: '#' }];

export default function RequestsIndex({ period }: Props) {
    return (
        <AnalyticsLayout title="Requests" period={period} breadcrumbs={breadcrumbs}>
            <Head title="Requests" />
        </AnalyticsLayout>
    );
}
