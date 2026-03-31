import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

interface Props {
    selectedIds: number[];
    bulkUrl: string;
    onClear: () => void;
}

export function BulkActionToolbar({ selectedIds, bulkUrl, onClear }: Props) {
    if (selectedIds.length === 0) {
        return null;
    }

    function performAction(action: 'resolve' | 'ignore' | 'reopen') {
        router.post(
            bulkUrl,
            { issue_ids: selectedIds, action },
            {
                onSuccess: onClear,
                preserveScroll: true,
            },
        );
    }

    return (
        <div className="flex items-center gap-3 rounded-lg border bg-muted/60 px-4 py-2">
            <span className="text-sm font-medium">{selectedIds.length} selected</span>
            <div className="flex gap-2">
                <Button size="sm" variant="outline" onClick={() => performAction('resolve')}>
                    Resolve
                </Button>
                <Button size="sm" variant="outline" onClick={() => performAction('ignore')}>
                    Ignore
                </Button>
                <Button size="sm" variant="outline" onClick={() => performAction('reopen')}>
                    Reopen
                </Button>
            </div>
            <Button size="sm" variant="ghost" onClick={onClear}>
                Clear
            </Button>
        </div>
    );
}
