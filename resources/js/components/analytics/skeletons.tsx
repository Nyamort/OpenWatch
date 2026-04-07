export function ChartsSkeleton() {
    return (
        <div className="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {[0, 1].map((i) => (
                <div
                    key={i}
                    className="h-[206px] animate-pulse rounded-xl border bg-muted/40"
                />
            ))}
        </div>
    );
}

export function TableSkeleton() {
    return (
        <div className="flex flex-col gap-1.5">
            <div className="h-11 animate-pulse rounded-lg bg-muted/40" />
            {Array.from({ length: 8 }).map((_, i) => (
                <div
                    key={i}
                    className="h-11 animate-pulse rounded-lg bg-muted/20"
                />
            ))}
        </div>
    );
}

export function TableWithSearchSkeleton() {
    return (
        <div className="flex flex-col gap-3">
            <div className="h-10 w-64 animate-pulse rounded-lg bg-muted/40" />
            <TableSkeleton />
        </div>
    );
}

export function CardSkeleton() {
    return <div className="h-48 animate-pulse rounded-xl border bg-muted/40" />;
}
