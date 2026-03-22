import { usePage } from '@inertiajs/react';
import { Bell, LayoutGrid, Shield, Users } from 'lucide-react';
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
import { audit } from '@/routes/organizations';
import { index as membersIndex } from '@/routes/organizations/members';

interface ActiveOrg {
    id: number;
    name: string;
    slug: string;
}

export function AppSidebar() {
    const { activeOrganization } = usePage<{ activeOrganization?: ActiveOrg | null }>().props;
    const [wizardOpen, setWizardOpen] = useState(false);

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    const orgNavItems: NavItem[] = activeOrganization
        ? [
              {
                  title: 'Members',
                  href: membersIndex({ organization: activeOrganization }),
                  icon: Users,
              },
              {
                  title: 'Alerts',
                  href: `/organizations/${activeOrganization.slug}/alerts`,
                  icon: Bell,
              },
              {
                  title: 'Audit Log',
                  href: audit({ organization: activeOrganization }),
                  icon: Shield,
              },
          ]
        : [];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <ContextSelector onNewApplication={() => setWizardOpen(true)} />
            </SidebarHeader>
            <SetupWizardDialog open={wizardOpen} onOpenChange={setWizardOpen} />

            <SidebarContent>
                <NavMain items={mainNavItems} />
                {activeOrganization && orgNavItems.length > 0 && (
                    <NavMain items={orgNavItems} />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
