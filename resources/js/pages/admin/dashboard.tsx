import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import AdminLayout from '@/layouts/admin-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Super Admin', href: '/admin' },
];

export default function AdminDashboard() {
    return (
        <AdminLayout breadcrumbs={breadcrumbs}>
            <Head title="Super Admin" />
            <div className="px-6 py-6">
                <Heading
                    title="Super administration"
                    description="Manage application-wide settings, users, and feature flags."
                />
            </div>
        </AdminLayout>
    );
}
