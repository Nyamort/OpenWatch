import { Head, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

interface Session {
    id: string;
    ip_address: string;
    user_agent: string;
    last_activity: number;
    is_current: boolean;
}

interface Props {
    sessions: Session[];
    currentSessionId: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Sessions', href: '/settings/sessions' },
];

function formatLastActivity(timestamp: number): string {
    const date = new Date(timestamp * 1000);
    return date.toLocaleString();
}

function parseUserAgent(userAgent: string): string {
    if (userAgent.includes('Chrome')) {
        return 'Chrome';
    }
    if (userAgent.includes('Firefox')) {
        return 'Firefox';
    }
    if (userAgent.includes('Safari')) {
        return 'Safari';
    }
    if (userAgent.includes('Edge')) {
        return 'Edge';
    }
    return userAgent.slice(0, 40) + (userAgent.length > 40 ? '…' : '');
}

export default function Sessions({ sessions }: Props) {
    function revokeSession(sessionId: string) {
        router.delete(`/settings/sessions/${sessionId}`, {
            preserveScroll: true,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Sessions" />

            <h1 className="sr-only">Session Settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Active sessions"
                        description="Manage and revoke your active browser sessions"
                    />

                    {sessions.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            No active sessions found.
                        </p>
                    ) : (
                        <div className="space-y-3">
                            {sessions.map((session) => (
                                <div
                                    key={session.id}
                                    className="flex items-center justify-between gap-4 rounded-lg border p-4"
                                >
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="text-sm font-medium text-foreground">
                                                {parseUserAgent(
                                                    session.user_agent,
                                                )}
                                            </p>
                                            {session.is_current && (
                                                <Badge variant="secondary">
                                                    Current
                                                </Badge>
                                            )}
                                        </div>
                                        <p className="mt-0.5 text-xs text-muted-foreground">
                                            {session.ip_address} &middot; Last
                                            active{' '}
                                            {formatLastActivity(
                                                session.last_activity,
                                            )}
                                        </p>
                                    </div>
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        disabled={session.is_current}
                                        onClick={() =>
                                            revokeSession(session.id)
                                        }
                                    >
                                        Revoke
                                    </Button>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
