import { LayoutGrid } from 'lucide-react';
import { NavMain } from '@/components/layout/nav-main';
import { NavUser } from '@/components/layout/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes/admin';
import type { NavItem } from '@/types/navigation';

export function AdminSidebar() {
    const navGroups: { label?: string; items: NavItem[] }[] = [
        {
            items: [
                {
                    title: 'Overview',
                    href: dashboard.url(),
                    icon: LayoutGrid,
                },
            ],
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <div className="px-2 py-1.5 text-sm font-semibold tracking-tight">
                    Super Admin
                </div>
            </SidebarHeader>

            <SidebarContent>
                {navGroups.map((group, i) => (
                    <NavMain
                        key={group.label ?? i}
                        label={group.label}
                        items={group.items}
                    />
                ))}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
