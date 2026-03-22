import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown, Plus } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { store as switchOrg } from '@/actions/App/Http/Controllers/Organization/OrganizationSwitcherController';
import { create as createOrg } from '@/routes/organizations';

interface Org {
    id: number;
    name: string;
    slug: string;
}

export function OrgSwitcher() {
    const { activeOrganization, organizations } = usePage<{
        activeOrganization?: Org | null;
        organizations: Org[];
    }>().props;

    const { state } = useSidebar();

    function handleSwitch(org: Org) {
        if (org.id === activeOrganization?.id) {
            return;
        }
        router.post(switchOrg().url, { organization_id: org.id });
    }

    const active = activeOrganization ?? null;

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground"
                        >
                            <div className="flex aspect-square size-8 items-center justify-center rounded-lg bg-muted text-foreground">
                                <Building2 className="size-4" />
                            </div>
                            <div className="grid flex-1 text-left text-sm leading-tight">
                                <span className="truncate font-semibold">
                                    {active ? active.name : 'Select organization'}
                                </span>
                                <span className="truncate text-xs text-muted-foreground">Organization</span>
                            </div>
                            <ChevronsUpDown className="ml-auto size-4" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
                        align="start"
                        side={state === 'collapsed' ? 'right' : 'bottom'}
                        sideOffset={4}
                    >
                        <DropdownMenuLabel className="text-xs text-muted-foreground">
                            Organizations
                        </DropdownMenuLabel>

                        {organizations.map((org) => (
                            <DropdownMenuItem
                                key={org.id}
                                onSelect={() => handleSwitch(org)}
                                className="gap-2 p-2 cursor-pointer"
                            >
                                <div className="flex size-6 items-center justify-center rounded-sm border bg-card">
                                    <Building2 className="size-3.5 shrink-0" />
                                </div>
                                <span className="flex-1 truncate">{org.name}</span>
                                {org.id === active?.id && <Check className="size-4 text-primary" />}
                            </DropdownMenuItem>
                        ))}

                        {organizations.length === 0 && (
                            <DropdownMenuItem disabled className="text-muted-foreground text-sm">
                                No organizations yet
                            </DropdownMenuItem>
                        )}

                        <DropdownMenuSeparator />

                        <DropdownMenuItem asChild className="gap-2 p-2 cursor-pointer">
                            <a href={createOrg().url}>
                                <div className="flex size-6 items-center justify-center rounded-md border bg-card">
                                    <Plus className="size-4" />
                                </div>
                                <span>Create organization</span>
                            </a>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
