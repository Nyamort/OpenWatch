import { LayoutGrid } from 'lucide-react';
import { useState } from 'react';
import { NavMain } from '@/components/layout/nav-main';
import { NavUser } from '@/components/layout/nav-user';
import { ContextSelector } from '@/components/organizations/context-selector';
import { SetupWizardDialog } from '@/components/setup-wizard-dialog';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types/navigation';

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
        <>
        <SetupWizardDialog open={wizardOpen} onOpenChange={setWizardOpen} />
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <ContextSelector onNewApplication={() => setWizardOpen(true)} />
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
        </>
    );
}
