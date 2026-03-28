const methodColors: Record<string, string> = {
    GET: 'text-sky-500',
    POST: 'text-green-500',
    PUT: 'text-amber-500',
    PATCH: 'text-amber-500',
    DELETE: 'text-red-500',
    HEAD: 'text-muted-foreground',
    OPTIONS: 'text-muted-foreground',
};

interface HttpMethodBadgeProps {
    methods: string[];
}

export function HttpMethodBadge({ methods }: HttpMethodBadgeProps) {
    if (methods.length === 0) {
        return <span className="font-mono text-xs font-semibold text-muted-foreground">ANY</span>;
    }

    return (
        <span className="font-mono text-xs font-semibold">
            {methods.map((method, i) => (
                <span key={method}>
                    {i > 0 && <span className="text-muted-foreground"> | </span>}
                    <span className={methodColors[method] ?? 'text-muted-foreground'}>{method}</span>
                </span>
            ))}
        </span>
    );
}
