import { Link, usePage } from '@inertiajs/react';
import {
    Bell,
    Building2,
    FolderOpen,
    LayoutGrid,
    Settings,
    Shield,
    Users,
} from 'lucide-react';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';
import AppLogo from './app-logo';
import { dashboard } from '@/routes';
import { index as orgsIndex, audit } from '@/routes/organizations';
import { index as membersIndex } from '@/routes/organizations/members';
import { index as projectsIndex } from '@/routes/organizations/projects';
import { edit as editProfile } from '@/routes/profile';

interface ActiveOrg {
    id: number;
    name: string;
    slug: string;
}

const footerNavItems: NavItem[] = [
    {
        title: 'Settings',
        href: editProfile(),
        icon: Settings,
    },
];

export function AppSidebar() {
    const { activeOrganization } = usePage<{ activeOrganization?: ActiveOrg | null }>().props;

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
        {
            title: 'Organizations',
            href: orgsIndex(),
            icon: Building2,
        },
    ];

    const orgNavItems: NavItem[] = activeOrganization
        ? [
              {
                  title: 'Projects',
                  href: projectsIndex({ organization: activeOrganization }),
                  icon: FolderOpen,
              },
              {
                  title: 'Members',
                  href: membersIndex({ organization: activeOrganization }),
                  icon: Users,
              },
              {
                  title: 'Alerts',
                  href: `/organizations/${activeOrganization.slug}/projects`,
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
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                {activeOrganization && (
                    <OrgSection label={activeOrganization.name} items={orgNavItems} />
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}

function OrgSection({ label, items }: { label: string; items: NavItem[] }) {
    return (
        <div>
            <div className="px-4 pt-4 pb-1">
                <p className="truncate text-xs font-semibold uppercase tracking-wider text-sidebar-foreground/50">
                    {label}
                </p>
            </div>
            <NavMain items={items} />
        </div>
    );
}
