import { AdminSidebar } from '@/components/layout/admin-sidebar';
import { AppContent } from '@/components/layout/app-content';
import { AppShell } from '@/components/layout/app-shell';
import { AppSidebarHeader } from '@/components/layout/app-sidebar-header';
import { Toaster } from '@/components/ui/sonner';
import type { AppLayoutProps } from '@/types';

export default function AdminLayout({
    children,
    breadcrumbs = [],
    actions,
}: AppLayoutProps) {
    return (
        <AppShell variant="sidebar">
            <AdminSidebar />
            <AppContent variant="sidebar" className="overflow-x-clip">
                <AppSidebarHeader breadcrumbs={breadcrumbs} actions={actions} />
                {children}
            </AppContent>
            <Toaster />
        </AppShell>
    );
}
