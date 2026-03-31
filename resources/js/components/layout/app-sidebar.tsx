import { usePage } from '@inertiajs/react';
import { AlertCircle, Bell, BriefcaseBusiness, Database, Globe, LayoutGrid, Terminal } from 'lucide-react';
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
import { useAnalyticsHref } from '@/hooks/use-analytics-href';
import { dashboard } from '@/routes';
import { index as commandsIndex } from '@/routes/analytics/commands';
import { index as exceptionsIndex } from '@/routes/analytics/exceptions';
import { index as notificationsIndex } from '@/routes/analytics/notifications';
import { index as queriesIndex } from '@/routes/analytics/queries';
import { index as jobsIndex } from '@/routes/analytics/jobs';
import { index as requestsIndex } from '@/routes/analytics/requests';
import type { NavItem } from '@/types/navigation';

export function AppSidebar() {
    const [wizardOpen, setWizardOpen] = useState(false);
    const { props } = usePage();
    const { activeOrganization, activeProject, activeEnvironment } = props as {
        activeOrganization?: { slug: string } | null;
        activeProject?: { slug: string } | null;
        activeEnvironment?: { slug: string } | null;
    };

    const hasContext = !!(activeOrganization && activeProject && activeEnvironment);

    const analyticsHref = useAnalyticsHref();

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        ...(hasContext ? [
            {
                title: 'Requests',
                href: analyticsHref(requestsIndex.url({
                    organization: activeOrganization!.slug,
                    project: activeProject!.slug,
                    environment: activeEnvironment!.slug,
                })),
                icon: Globe,
            },
            {
                title: 'Jobs',
                href: analyticsHref(jobsIndex.url({
                    organization: activeOrganization!.slug,
                    project: activeProject!.slug,
                    environment: activeEnvironment!.slug,
                })),
                icon: BriefcaseBusiness,
            },
            {
                title: 'Commands',
                href: analyticsHref(commandsIndex.url({
                    organization: activeOrganization!.slug,
                    project: activeProject!.slug,
                    environment: activeEnvironment!.slug,
                })),
                icon: Terminal,
            },
            {
                title: 'Queries',
                href: analyticsHref(queriesIndex.url({
                    organization: activeOrganization!.slug,
                    project: activeProject!.slug,
                    environment: activeEnvironment!.slug,
                })),
                icon: Database,
            },
            {
                title: 'Exceptions',
                href: analyticsHref(exceptionsIndex.url({
                    organization: activeOrganization!.slug,
                    project: activeProject!.slug,
                    environment: activeEnvironment!.slug,
                })),
                icon: AlertCircle,
            },
            {
                title: 'Notifications',
                href: analyticsHref(notificationsIndex.url({
                    organization: activeOrganization!.slug,
                    project: activeProject!.slug,
                    environment: activeEnvironment!.slug,
                })),
                icon: Bell,
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
