import { Check, ChevronsUpDown, Plus, Search } from 'lucide-react';
import { useRef, useState } from 'react';
import { CreateOrganizationDialog } from '@/components/create-organization-dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

interface Organization {
    id: number;
    name: string;
    slug: string;
}

interface OrgSelectorProps {
    organizations: Organization[];
    value: string;
    onChange: (slug: string) => void;
}

export function OrgSelector({ organizations, value, onChange }: OrgSelectorProps) {
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const [dialogOpen, setDialogOpen] = useState(false);
    const searchRef = useRef<HTMLInputElement>(null);

    const selected = organizations.find((o) => o.slug === value);
    const filtered = search.trim()
        ? organizations.filter((o) => o.name.toLowerCase().includes(search.toLowerCase()))
        : organizations;

    function handleOpenChange(next: boolean) {
        setOpen(next);
        if (!next) {
            setSearch('');
        } else {
            setTimeout(() => searchRef.current?.focus(), 50);
        }
    }

    return (
        <DropdownMenu open={open} onOpenChange={handleOpenChange}>
            <DropdownMenuTrigger asChild>
                <button className="flex w-full items-center gap-2 rounded-md border border-input bg-background px-2 py-1.5 text-sm transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                    <span className="flex-1 truncate text-left">
                        {selected?.name ?? 'Select organization'}
                    </span>
                    <ChevronsUpDown className="size-3.5 shrink-0 text-muted-foreground" />
                </button>
            </DropdownMenuTrigger>

            <DropdownMenuContent
                className="flex w-52 flex-col p-0 shadow-lg"
                align="start"
                sideOffset={4}
                onCloseAutoFocus={(e) => e.preventDefault()}
            >
                <div className="flex items-center gap-2 border-b border-border px-3 py-2">
                    <Search className="size-3.5 shrink-0 text-muted-foreground" />
                    <input
                        ref={searchRef}
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Find organization..."
                        className="flex-1 bg-transparent text-xs outline-none placeholder:text-muted-foreground"
                    />
                </div>

                <div className="max-h-48 overflow-y-auto py-1">
                    {filtered.length === 0 ? (
                        <p className="px-3 py-3 text-center text-xs text-muted-foreground">
                            No results
                        </p>
                    ) : (
                        filtered.map((org) => (
                            <button
                                key={org.id}
                                onClick={() => {
                                    onChange(org.slug);
                                    setOpen(false);
                                    setSearch('');
                                }}
                                className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm transition-colors hover:bg-accent"
                            >
                                <span className="flex-1 truncate">{org.name}</span>
                                {org.slug === value && (
                                    <Check className="size-3.5 shrink-0 text-primary" />
                                )}
                            </button>
                        ))
                    )}
                </div>

                <div className="border-t border-border p-1">
                    <button
                        onClick={() => { setOpen(false); setDialogOpen(true); }}
                        className="flex w-full items-center gap-2 rounded px-2 py-1.5 text-xs text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                    >
                        <Plus className="size-3.5" />
                        New Organization
                    </button>
                </div>
            </DropdownMenuContent>
        </DropdownMenu>

        <CreateOrganizationDialog open={dialogOpen} onOpenChange={setDialogOpen} />
    );
}
