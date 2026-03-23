import { Link, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown, Plus, Search } from 'lucide-react';
import { useRef, useState } from 'react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editNotifications } from '@/routes/notifications';
import { index as sessionsIndex } from '@/routes/sessions';
import { edit } from '@/routes/profile';
import { show } from '@/routes/two-factor';
import { edit as editPassword } from '@/routes/user-password';

const accountNavItems: NavItem[] = [
    { title: 'Profile', href: edit(), icon: null },
    { title: 'Password', href: editPassword(), icon: null },
    { title: 'Two-Factor Auth', href: show(), icon: null },
    { title: 'Notifications', href: editNotifications(), icon: null },
    { title: 'Sessions', href: sessionsIndex(), icon: null },
    { title: 'Appearance', href: editAppearance(), icon: null },
];

function orgNavItems(slug: string): NavItem[] {
    return [
        { title: 'General', href: `/settings/organizations/${slug}/general`, icon: null },
        { title: 'Members', href: `/settings/organizations/${slug}/members`, icon: null },
        { title: 'Audit', href: `/settings/organizations/${slug}/audit`, icon: null },
    ];
}

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentUrl } = useCurrentUrl();
    const { organizations, activeOrganization } = usePage().props as {
        organizations: { id: number; name: string; slug: string }[];
        activeOrganization?: { id: number; name: string; slug: string } | null;
    };

    const [selectedOrgSlug, setSelectedOrgSlug] = useState<string>(
        activeOrganization?.slug ?? organizations[0]?.slug ?? '',
    );
    const [open, setOpen] = useState(false);
    const [search, setSearch] = useState('');
    const searchRef = useRef<HTMLInputElement>(null);

    const selectedOrg = organizations.find((o) => o.slug === selectedOrgSlug);
    const filteredOrgs = search.trim()
        ? organizations.filter((o) => o.name.toLowerCase().includes(search.toLowerCase()))
        : organizations;

    const orgItems = selectedOrgSlug ? orgNavItems(selectedOrgSlug) : [];

    function handleOpenChange(value: boolean) {
        setOpen(value);
        if (!value) {
            setSearch('');
        } else {
            setTimeout(() => searchRef.current?.focus(), 50);
        }
    }

    if (typeof window === 'undefined') {
        return null;
    }

    return (
        <div className="px-4 py-6">
            <Heading
                title="Settings"
                description="Manage your profile and account settings"
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48 space-y-4">
                    {organizations.length > 0 && (
                        <div className="space-y-1">
                            <p className="px-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                                Organization
                            </p>

                            <DropdownMenu open={open} onOpenChange={handleOpenChange}>
                                <DropdownMenuTrigger asChild>
                                    <button className="flex w-full items-center gap-2 rounded-md border border-input bg-background px-2 py-1.5 text-sm transition-colors hover:bg-accent focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring">
                                        <span className="flex-1 truncate text-left">
                                            {selectedOrg?.name ?? 'Select organization'}
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
                                        {filteredOrgs.length === 0 ? (
                                            <p className="px-3 py-3 text-center text-xs text-muted-foreground">
                                                No results
                                            </p>
                                        ) : (
                                            filteredOrgs.map((org) => (
                                                <button
                                                    key={org.id}
                                                    onClick={() => {
                                                        setSelectedOrgSlug(org.slug);
                                                        setOpen(false);
                                                        setSearch('');
                                                    }}
                                                    className="flex w-full items-center gap-2 px-3 py-1.5 text-left text-sm transition-colors hover:bg-accent"
                                                >
                                                    <span className="flex-1 truncate">{org.name}</span>
                                                    {org.slug === selectedOrgSlug && (
                                                        <Check className="size-3.5 shrink-0 text-primary" />
                                                    )}
                                                </button>
                                            ))
                                        )}
                                    </div>

                                    <div className="border-t border-border p-1">
                                        <Link
                                            href="/organizations/create"
                                            onClick={() => setOpen(false)}
                                            className="flex w-full items-center gap-2 rounded px-2 py-1.5 text-xs text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                                        >
                                            <Plus className="size-3.5" />
                                            New Organization
                                        </Link>
                                    </div>
                                </DropdownMenuContent>
                            </DropdownMenu>

                            <nav className="flex flex-col space-y-1" aria-label="Organization settings">
                                {orgItems.map((item) => (
                                    <Button
                                        key={toUrl(item.href)}
                                        size="sm"
                                        variant="ghost"
                                        asChild
                                        className={cn('w-full justify-start', {
                                            'bg-muted': isCurrentUrl(item.href),
                                        })}
                                    >
                                        <Link href={item.href}>{item.title}</Link>
                                    </Button>
                                ))}
                            </nav>
                        </div>
                    )}

                    <Separator />

                    <div className="space-y-1">
                        <p className="px-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                            Account
                        </p>
                        <nav className="flex flex-col space-y-1" aria-label="Account settings">
                            {accountNavItems.map((item, index) => (
                                <Button
                                    key={`${toUrl(item.href)}-${index}`}
                                    size="sm"
                                    variant="ghost"
                                    asChild
                                    className={cn('w-full justify-start', {
                                        'bg-muted': isCurrentUrl(item.href),
                                    })}
                                >
                                    <Link href={item.href}>{item.title}</Link>
                                </Button>
                            ))}
                        </nav>
                    </div>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
