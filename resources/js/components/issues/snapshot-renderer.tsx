import { Badge } from '@/components/ui/badge';

interface IssueSource {
    id: number;
    source_type: string;
    trace_id: string | null;
    group_key: string | null;
    execution_id: string | null;
    snapshot: Record<string, unknown> | null;
    created_at: string;
}

interface Props {
    sources: IssueSource[];
    issueType: string;
    issueTitle: string;
}

export function SnapshotRenderer({ sources, issueType, issueTitle }: Props) {
    const latestSource = sources[0] ?? null;

    return (
        <div className="space-y-4">
            <div className="rounded-lg border bg-card p-4">
                <div className="mb-2 flex items-center gap-2">
                    <Badge variant="outline">{issueType}</Badge>
                    {latestSource && <Badge variant="secondary">{latestSource.source_type}</Badge>}
                </div>
                <h2 className="font-mono text-sm font-semibold break-all">{issueTitle}</h2>
            </div>

            {latestSource && (
                <div className="rounded-lg border bg-muted/40 p-4 text-sm space-y-2">
                    {latestSource.trace_id && (
                        <div>
                            <span className="text-muted-foreground">Trace ID: </span>
                            <span className="font-mono">{latestSource.trace_id}</span>
                        </div>
                    )}
                    {latestSource.group_key && (
                        <div>
                            <span className="text-muted-foreground">Group Key: </span>
                            <span className="font-mono">{latestSource.group_key}</span>
                        </div>
                    )}
                    {latestSource.execution_id && (
                        <div>
                            <span className="text-muted-foreground">Execution ID: </span>
                            <span className="font-mono">{latestSource.execution_id}</span>
                        </div>
                    )}
                    {latestSource.snapshot && (
                        <details className="mt-2">
                            <summary className="cursor-pointer text-muted-foreground">Snapshot data</summary>
                            <pre className="mt-2 overflow-auto rounded bg-background p-2 text-xs">
                                {JSON.stringify(latestSource.snapshot, null, 2)}
                            </pre>
                        </details>
                    )}
                </div>
            )}

            {sources.length > 1 && (
                <p className="text-xs text-muted-foreground">
                    + {sources.length - 1} more occurrence{sources.length > 2 ? 's' : ''}
                </p>
            )}
        </div>
    );
}
