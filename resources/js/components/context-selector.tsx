import { router, usePage } from '@inertiajs/react';
import { Building2, Check, ChevronsUpDown, FolderOpen, Globe, Plus } from 'lucide-react';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useSidebar } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import { store as switchOrg } from '@/actions/App/Http/Controllers/Organization/OrganizationSwitcherController';
import { store as switchProject } from '@/actions/App/Http/Controllers/Project/ProjectSwitcherController';
import { store as switchEnvironment } from '@/actions/App/Http/Controllers/Project/EnvironmentSwitcherController';
import { create as createOrgRoute } from '@/routes/organizations';

interface Item {
    id: number;
    name: string;
    slug: string;
}

interface SharedProps {
    activeOrganization?: Item | null;
    activeProject?: Item | null;
    activeEnvironment?: Item | null;
    organizations: Item[];
    projects: Item[];
    environments: Item[];
}

function SwitcherRow({
    icon: Icon,
    label,
    placeholder,
    active,
    items,
    onSelect,
    onCreate,
    createLabel,
    disabled,
    side,
    isLast,
}: {
    icon: React.ElementType;
    label: string;
    placeholder: string;
    active: Item | null | undefined;
    items: Item[];
    onSelect: (item: Item) => void;
    onCreate?: () => void;
    createLabel?: string;
    disabled?: boolean;
    side: 'right' | 'bottom';
    isLast?: boolean;
}) {
    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild disabled={disabled}>
                <button
                    className={cn(
                        'flex w-full items-center gap-2 px-2 py-1.5 text-left transition-colors',
                        'hover:bg-sidebar-accent hover:text-sidebar-accent-foreground',
                        'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-sidebar-ring',
                        'disabled:pointer-events-none disabled:opacity-40',
                        !isLast && 'border-b border-sidebar-border',
                    )}
                >
                    <Icon className="size-3.5 shrink-0 text-sidebar-foreground/60" />
                    <span className="flex-1 truncate text-xs font-medium">
                        {active
                            ? active.name
                            : <span className="font-normal text-sidebar-foreground/50">{placeholder}</span>
                        }
                    </span>
                    <ChevronsUpDown className="size-3 shrink-0 text-sidebar-foreground/40" />
                </button>
            </DropdownMenuTrigger>

            <DropdownMenuContent
                className="w-56 rounded-lg"
                align="start"
                side={side}
                sideOffset={4}
            >
                <DropdownMenuLabel className="text-xs text-muted-foreground">{label}</DropdownMenuLabel>

                {items.map((item) => (
                    <DropdownMenuItem
                        key={item.id}
                        onSelect={() => onSelect(item)}
                        className="gap-2 cursor-pointer"
                    >
                        <span className="flex-1 truncate">{item.name}</span>
                        {item.id === active?.id && <Check className="size-4 text-primary shrink-0" />}
                    </DropdownMenuItem>
                ))}

                {items.length === 0 && (
                    <DropdownMenuItem disabled className="text-sm text-muted-foreground">
                        Aucun élément
                    </DropdownMenuItem>
                )}

                {onCreate && (
                    <>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem onSelect={onCreate} className="gap-2 cursor-pointer">
                            <Plus className="size-4 shrink-0" />
                            <span>{createLabel}</span>
                        </DropdownMenuItem>
                    </>
                )}
            </DropdownMenuContent>
        </DropdownMenu>
    );
}

export function ContextSelector() {
    const {
        activeOrganization,
        activeProject,
        activeEnvironment,
        organizations,
        projects,
        environments,
    } = usePage().props as unknown as SharedProps;

    const { state } = useSidebar();
    const side = state === 'collapsed' ? 'right' : 'bottom';

    return (
        <div className="rounded-lg border border-sidebar-border bg-sidebar-accent/30 overflow-hidden">
            <SwitcherRow
                icon={Building2}
                label="Organisations"
                placeholder="Sélectionner une organisation"
                active={activeOrganization}
                items={organizations}
                onSelect={(org) => router.post(switchOrg().url, { organization_id: org.id })}
                onCreate={() => router.visit(createOrgRoute().url)}
                createLabel="Créer une organisation"
                side={side}
            />
            <SwitcherRow
                icon={FolderOpen}
                label="Projets"
                placeholder="Sélectionner un projet"
                active={activeProject}
                items={projects}
                onSelect={(project) => router.post(switchProject().url, { project_id: project.id })}
                disabled={!activeOrganization}
                side={side}
            />
            <SwitcherRow
                icon={Globe}
                label="Environnements"
                placeholder="Sélectionner un environnement"
                active={activeEnvironment}
                items={environments}
                onSelect={(env) => router.post(switchEnvironment().url, { environment_id: env.id })}
                disabled={!activeProject}
                side={side}
                isLast
            />
        </div>
    );
}
