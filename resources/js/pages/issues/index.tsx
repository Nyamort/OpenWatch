import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { index } from '@/routes/issues';
import type { BreadcrumbItem } from '@/types';

interface Props {}

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Issues', href: '#' }];

export default function IssuesIndex({}: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Issues" />
        </AppLayout>
    );
}
