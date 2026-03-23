import { usePage } from '@inertiajs/react';
import { LayoutGrid } from 'lucide-react';
import { useState } from 'react';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { ContextSelector } from '@/components/context-selector';
import { SetupWizardDialog } from '@/components/setup-wizard-dialog';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';

export function AppSidebar() {
    const [wizardOpen, setWizardOpen] = useState(false);

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <ContextSelector onNewApplication={() => setWizardOpen(true)} />
            </SidebarHeader>
            <SetupWizardDialog open={wizardOpen} onOpenChange={setWizardOpen} />

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
