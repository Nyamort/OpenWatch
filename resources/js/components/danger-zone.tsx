export function DangerZone({ children }: { children: React.ReactNode }) {
    return (
        <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
            <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                <p className="font-medium">Warning</p>
                <p className="text-sm">Please proceed with caution, this cannot be undone.</p>
            </div>
            {children}
        </div>
    );
}
