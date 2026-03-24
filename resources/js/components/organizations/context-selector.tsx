import { Link, router, usePage } from '@inertiajs/react';
import { Check, ChevronRight, ChevronsUpDown, Network, Plus, Search, Settings } from 'lucide-react';
import { useRef, useState } from 'react';
import { store as switchOrg } from '@/actions/App/Http/Controllers/Organization/OrganizationSwitcherController';
import { store as switchEnvironment } from '@/actions/App/Http/Controllers/Project/EnvironmentSwitcherController';
import { store as switchProject } from '@/actions/App/Http/Controllers/Project/ProjectSwitcherController';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface Environment {
    id: number;
    name: string;
    slug: string;
}

interface Project {
    id: number;
    name: string;
    slug: string;
    logo_url: string;
    environments: Environment[];
}

interface ProjectGroup {
    organization: { id: number; name: string; slug: string };
    projects: Project[];
}

interface SharedProps {
    activeOrganization?: { id: number; name: string; slug: string } | null;
    activeProject?: Project | null;
    activeEnvironment?: Environment | null;
    projectGroups: ProjectGroup[];
}

const AVATAR_COLORS = [
    'bg-violet-500',
    'bg-blue-500',
    'bg-emerald-500',
    'bg-orange-500',
    'bg-rose-500',
    'bg-cyan-500',
    'bg-amber-500',
    'bg-indigo-500',
];

function avatarColor(name: string): string {
    return AVATAR_COLORS[name.charCodeAt(0) % AVATAR_COLORS.length];
}

export function ContextSelector({ onNewApplication }: { onNewApplication?: () => void }) {
    const { activeOrganization, activeProject, activeEnvironment, projectGroups } =
        usePage().props as unknown as SharedProps;

    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [previewProject, setPreviewProject] = useState<Project | null>(null);
    const searchRef = useRef<HTMLInputElement>(null);

    const currentPreview = previewProject ?? activeProject ?? null;

    const allGroups = projectGroups ?? [];
    const filteredGroups = search.trim()
        ? allGroups
              .map((g) => ({
                  ...g,
                  projects: g.projects.filter((p) =>
                      p.name.toLowerCase().includes(search.toLowerCase()),
                  ),
              }))
              .filter((g) => g.projects.length > 0)
        : allGroups;

    function handleSelectProject(project: Project) {
        setPreviewProject(project);
    }

    function handleSelectEnvironment(env: Environment) {
        const targetProject = previewProject ?? activeProject;
        if (!targetProject) return;

        const group = allGroups.find((g) => g.projects.some((p) => p.id === targetProject.id));
        const targetOrg = group?.organization;

        const finish = () => {
            setOpen(false);
            setPreviewProject(null);
            setSearch('');
        };

        const switchEnvFn = () =>
            router.post(switchEnvironment().url, { environment_id: env.id }, { onFinish: finish });

        const switchProjectFn = () =>
            router.post(switchProject().url, { project_id: targetProject.id }, { onSuccess: switchEnvFn });

        const needsOrgSwitch = targetOrg && targetOrg.id !== activeOrganization?.id;
        const needsProjectSwitch = targetProject.id !== activeProject?.id;

        if (needsOrgSwitch) {
            router.post(switchOrg().url, { organization_id: targetOrg!.id }, { onSuccess: needsProjectSwitch ? switchProjectFn : switchEnvFn });
        } else if (needsProjectSwitch) {
            switchProjectFn();
        } else {
            switchEnvFn();
        }
    }

    function handleOpenChange(value: boolean) {
        setOpen(value);
        if (!value) {
            setPreviewProject(null);
            setSearch('');
        } else {
            setTimeout(() => searchRef.current?.focus(), 50);
        }
    }

    function handleNewApplication() {
        setOpen(false);
        onNewApplication?.();
    }

    return (
        <DropdownMenu open={open} onOpenChange={handleOpenChange}>
            <DropdownMenuTrigger asChild>
                <button className="flex w-full items-center gap-2.5 rounded-lg px-2 py-2 text-left transition-colors hover:bg-sidebar-accent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-sidebar-ring group-data-[collapsible=icon]:justify-center group-data-[collapsible=icon]:px-0 group-data-[collapsible=icon]:py-0">
                    <div
                        className={`flex size-8 shrink-0 items-center justify-center overflow-hidden rounded-md text-sm font-bold text-white ${
                            activeProject && !activeProject.logo_url ? avatarColor(activeProject.name) : 'bg-muted'
                        }`}
                    >
                        {activeProject?.logo_url ? (
                            <img src={activeProject.logo_url} alt={activeProject.name} className="size-full object-cover" />
                        ) : (
                            activeProject ? activeProject.name.charAt(0).toUpperCase() : '?'
                        )}
                    </div>
                    <div className="grid flex-1 leading-tight group-data-[collapsible=icon]:hidden">
                        <span className="truncate text-sm font-semibold text-sidebar-foreground">
                            {activeProject ? activeProject.name : 'Select application'}
                        </span>
                        <span className="truncate text-xs text-sidebar-foreground/50">
                            {activeEnvironment ? activeEnvironment.name : 'No environment'}
                        </span>
                    </div>
                    <ChevronsUpDown className="size-4 shrink-0 text-sidebar-foreground/40 group-data-[collapsible=icon]:hidden" />
                </button>
            </DropdownMenuTrigger>

            <DropdownMenuContent
                className="flex p-0 shadow-lg"
                align="start"
                side="bottom"
                sideOffset={4}
                style={{ width: 'auto', minWidth: 0 }}
                onCloseAutoFocus={(e) => e.preventDefault()}
            >
                {/* Left panel — Application selector */}
                <div className="flex w-56 flex-col border-r border-border max-h-80">
                    {/* Search */}
                    <div className="flex items-center gap-2 border-b border-border px-3 py-2">
                        <Search className="size-3.5 shrink-0 text-muted-foreground" />
                        <input
                            ref={searchRef}
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Find application..."
                            className="flex-1 bg-transparent text-xs outline-none placeholder:text-muted-foreground"
                        />
                    </div>

                    {/* Application groups */}
                    <div className="flex-1 overflow-y-auto py-1">
                        {filteredGroups.map((group) => (
                            <div key={group.organization.id}>
                                {/* Org header */}
                                <div className="flex items-center justify-between px-3 py-1.5">
                                    <div className="flex items-center gap-1.5 min-w-0">
                                        <Network className="size-3 shrink-0 text-muted-foreground" />
                                        <span className="truncate text-xs font-medium text-muted-foreground">
                                            {group.organization.name}
                                        </span>
                                    </div>
                                    <Link
                                        href={`/settings/organizations/${group.organization.slug}/general`}
                                        onClick={() => setOpen(false)}
                                        className="ml-1 shrink-0 rounded p-0.5 text-muted-foreground/50 transition-colors hover:bg-accent hover:text-foreground"
                                        title="Organization settings"
                                    >
                                        <Settings className="size-3" />
                                    </Link>
                                </div>

                                {/* Applications */}
                                {group.projects.map((project) => {
                                    const isActive = project.id === activeProject?.id;
                                    const isPreviewed = project.id === currentPreview?.id;

                                    return (
                                        <button
                                            key={project.id}
                                            onClick={() => handleSelectProject(project)}
                                            className={`flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm transition-colors hover:bg-accent ${
                                                isPreviewed ? 'bg-accent' : ''
                                            }`}
                                        >
                                            <div
                                                className={`flex size-5 shrink-0 items-center justify-center overflow-hidden rounded text-xs font-bold text-white ${!project.logo_url ? avatarColor(project.name) : 'bg-muted'}`}
                                            >
                                                {project.logo_url ? (
                                                    <img src={project.logo_url} alt={project.name} className="size-full object-cover" />
                                                ) : (
                                                    project.name.charAt(0).toUpperCase()
                                                )}
                                            </div>
                                            <span className="flex-1 truncate">{project.name}</span>
                                            {isActive && !isPreviewed && (
                                                <Check className="size-3.5 shrink-0 text-primary" />
                                            )}
                                            <ChevronRight className="size-3.5 shrink-0 text-muted-foreground" />
                                        </button>
                                    );
                                })}

                                {group.projects.length === 0 && (
                                    <p className="px-3 py-2 text-xs text-muted-foreground">No applications</p>
                                )}
                            </div>
                        ))}

                        {filteredGroups.length === 0 && (
                            <p className="px-3 py-3 text-center text-xs text-muted-foreground">No results</p>
                        )}
                    </div>

                    {/* New application */}
                    <div className="border-t border-border p-1">
                        <button
                            onClick={handleNewApplication}
                            className="flex w-full items-center gap-2 rounded px-2 py-1.5 text-xs text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                        >
                            <Plus className="size-3.5" />
                            New Application
                        </button>
                    </div>
                </div>

                {/* Right panel — Environment selector */}
                <div className="flex w-44 flex-col max-h-80">
                    <div className="border-b border-border px-3 py-2">
                        <p className="text-xs font-medium text-muted-foreground">Environments</p>
                    </div>

                    <div className="flex-1 overflow-y-auto py-1">
                        {currentPreview ? (
                            currentPreview.environments.length > 0 ? (
                                currentPreview.environments.map((env) => {
                                    const isActive =
                                        env.id === activeEnvironment?.id &&
                                        currentPreview.id === activeProject?.id;

                                    return (
                                        <button
                                            key={env.id}
                                            onClick={() => handleSelectEnvironment(env)}
                                            className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm transition-colors hover:bg-accent"
                                        >
                                            <span className="flex-1 truncate">{env.name}</span>
                                            {isActive && <Check className="size-3.5 shrink-0 text-primary" />}
                                        </button>
                                    );
                                })
                            ) : (
                                <p className="px-3 py-3 text-center text-xs text-muted-foreground">
                                    No environments
                                </p>
                            )
                        ) : (
                            <p className="px-3 py-3 text-center text-xs text-muted-foreground">
                                Select an application
                            </p>
                        )}
                    </div>
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
