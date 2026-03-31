const ALL_METHODS = ['DELETE', 'GET', 'HEAD', 'OPTIONS', 'PATCH', 'POST', 'PUT']
    .sort()
    .join();

function formatMethods(methods: string[]): string {
    if (methods.sort().join() === ALL_METHODS) {
        return 'ANY';
    }

    return methods.join('|');
}

function colorClass(methods: string[]): string {
    if (methods.includes('DELETE')) {
        return 'text-rose-600 dark:text-rose-400';
    }

    if (methods.includes('PUT') || methods.includes('PATCH')) {
        return 'text-blue-500 dark:text-blue-400';
    }

    if (methods.includes('POST')) {
        return 'text-emerald-600 dark:text-emerald-500';
    }

    return 'text-neutral-500 dark:text-neutral-400';
}

interface HttpMethodBadgeProps {
    methods: string[];
}

export function HttpMethodBadge({ methods }: HttpMethodBadgeProps) {
    if (methods.length === 0) {
        return (
            <span className="truncate font-mono text-xs font-semibold whitespace-nowrap text-neutral-500 dark:text-neutral-400">
                ANY
            </span>
        );
    }

    return (
        <span
            className={`truncate font-mono text-xs font-semibold whitespace-nowrap ${colorClass(methods)}`}
        >
            {formatMethods(methods)}
        </span>
    );
}
