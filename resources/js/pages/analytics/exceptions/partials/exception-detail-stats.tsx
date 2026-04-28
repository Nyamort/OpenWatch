import { InfoRow, Section } from '@/components/analytics/detail-card';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import type { ExceptionSummary } from '../types';

function formatDatetime(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }
    return new Date(value).toLocaleString([], {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function OccurrenceBreakdown({
    count7d,
    count24h,
}: {
    count7d: number;
    count24h: number;
}) {
    return (
        <div className="flex gap-4 text-muted-foreground uppercase">
            {(
                [
                    { label: '7 days', value: count7d },
                    { label: '24 hours', value: count24h },
                ] as const
            ).map(({ label, value }) => (
                <span key={label} className="flex items-center gap-1.5">
                    {label}
                    <Badge className="bg-black/8 text-neutral-900 dark:border-neutral-700 dark:bg-white/10 dark:text-neutral-100">
                        {value.toLocaleString()}
                    </Badge>
                </span>
            ))}
        </div>
    );
}

interface ExceptionDetailStatsProps {
    summary: ExceptionSummary;
}

export function ExceptionDetailStats({ summary }: ExceptionDetailStatsProps) {
    return (
        <Card className="gap-0 bg-surface py-0">
            <CardContent className="py-6">
                <Section>
                    <InfoRow
                        label="Last Seen"
                        value={formatDatetime(summary.last_seen)}
                    />
                    <InfoRow
                        label="First Seen"
                        value={formatDatetime(summary.first_seen)}
                    />
                    <InfoRow
                        label="First Reported In"
                        value={summary.first_reported_in ?? '—'}
                    />
                    <InfoRow
                        label="Impacted Users"
                        value={summary.impacted_users.toLocaleString()}
                    />
                    <InfoRow
                        label="Occurrences"
                        value={
                            <OccurrenceBreakdown
                                count7d={summary.occurrences_7d}
                                count24h={summary.occurrences_24h}
                            />
                        }
                    />
                    <InfoRow
                        label="Servers"
                        value={summary.servers.toLocaleString()}
                    />
                </Section>
            </CardContent>
        </Card>
    );
}
