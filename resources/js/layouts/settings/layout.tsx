import { Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { OrgSelector } from '@/components/org-selector';
import { Button } from '@/components/ui/button';
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
        { title: 'Applications', href: `/settings/organizations/${slug}/applications`, icon: null },
        { title: 'Audit', href: `/settings/organizations/${slug}/audit`, icon: null },
    ];
}

export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentUrl } = useCurrentUrl();
    const { url, props } = usePage();
    const { organizations, activeOrganization } = props as {
        organizations: { id: number; name: string; slug: string }[];
        activeOrganization?: { id: number; name: string; slug: string } | null;
    };

    const orgUrlMatch = url.match(/^\/settings\/organizations\/([^/]+)\/([^/?]+)/);
    const urlOrgSlug = orgUrlMatch?.[1] ?? null;
    const urlSection = orgUrlMatch?.[2] ?? null;

    const [selectedOrgSlug, setSelectedOrgSlug] = useState<string>(
        urlOrgSlug ?? activeOrganization?.slug ?? organizations[0]?.slug ?? '',
    );

    useEffect(() => {
        if (urlOrgSlug) {
            setSelectedOrgSlug(urlOrgSlug);
        }
    }, [urlOrgSlug]);

    function handleOrgChange(slug: string) {
        setSelectedOrgSlug(slug);
        if (urlSection) {
            router.visit(`/settings/organizations/${slug}/${urlSection}`);
        }
    }

    const orgItems = selectedOrgSlug ? orgNavItems(selectedOrgSlug) : [];

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

                            <OrgSelector
                                organizations={organizations}
                                value={selectedOrgSlug}
                                onChange={handleOrgChange}
                            />

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
