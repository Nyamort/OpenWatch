import { Check, Copy } from 'lucide-react';
import { useClipboard } from '@/hooks/use-clipboard';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

export function TokenDialog({
    open,
    onOpenChange,
    token,
    environmentName,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    token: string;
    environmentName: string;
}) {
    const [copiedText, copy] = useClipboard();

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-w-md">
                <DialogHeader>
                    <DialogTitle>Ingest token — {environmentName}</DialogTitle>
                    <DialogDescription>
                        Copy this token now. It will not be shown again.
                    </DialogDescription>
                </DialogHeader>

                <div className="flex items-center gap-2 rounded-md border bg-muted px-3 py-2">
                    <code className="flex-1 truncate font-mono text-sm">
                        {token}
                    </code>
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        className="size-7 shrink-0"
                        onClick={() => copy(token)}
                    >
                        {copiedText !== null ? (
                            <Check className="size-3.5 text-green-500" />
                        ) : (
                            <Copy className="size-3.5" />
                        )}
                    </Button>
                </div>

                <DialogFooter>
                    <Button onClick={() => onOpenChange(false)}>Done</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
