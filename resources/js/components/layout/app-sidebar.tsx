import { usePage } from '@inertiajs/react';
import { BriefcaseBusiness, Globe, LayoutGrid, Terminal } from 'lucide-react';
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
import { index as commandsIndex } from '@/routes/analytics/commands';
import { index as jobsIndex } from '@/routes/analytics/jobs';
import { index as requestsIndex } from '@/routes/analytics/requests';
import type { NavItem } from '@/types/navigation';

export function AppSidebar() {
    const [wizardOpen, setWizardOpen] = useState(false);
    const { activeOrganization, activeProject, activeEnvironment } = usePage().props as {
        activeOrganization?: { slug: string } | null;
        activeProject?: { slug: string } | null;
        activeEnvironment?: { slug: string } | null;
    };

    const hasContext = !!(activeOrganization && activeProject && activeEnvironment);

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        ...(hasContext ? [
            {
                title: 'Requests',
                href: requestsIndex({
                    organization: activeOrganization!.slug,
                    project: activeProject!.slug,
                    environment: activeEnvironment!.slug,
                }),
                icon: Globe,
            },
            {
                title: 'Jobs',
                href: jobsIndex({
                    organization: activeOrganization!.slug,
                    project: activeProject!.slug,
                    environment: activeEnvironment!.slug,
                }),
                icon: BriefcaseBusiness,
            },
            {
                title: 'Commands',
                href: commandsIndex({
                    organization: activeOrganization!.slug,
                    project: activeProject!.slug,
                    environment: activeEnvironment!.slug,
                }),
                icon: Terminal,
            },
        ] : []),
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
