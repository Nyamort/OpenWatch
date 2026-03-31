interface EmptyStateProps {
    title?: string;
    description?: string;
}

export function EmptyState({
    title = 'No data',
    description = 'No records found for the selected period.',
}: EmptyStateProps) {
    return (
        <div className="flex flex-col items-center justify-center py-12 text-center">
            <div className="rounded-full bg-muted p-4">
                <svg
                    className="h-8 w-8 text-muted-foreground"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        strokeWidth={1.5}
                        d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
                    />
                </svg>
            </div>
            <h3 className="mt-4 text-sm font-medium text-foreground">
                {title}
            </h3>
            <p className="mt-1 text-sm text-muted-foreground">{description}</p>
        </div>
    );
}
