import { Check, Wifi, WifiOff } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { CodeBlock } from './shared';

export function WizardStep4({
    onComplete,
    onSkip,
    onBack,
}: {
    onComplete: () => void;
    onSkip: () => void;
    onBack: () => void;
}) {
    const [connected, setConnected] = useState(false);
    const pollRef = useRef<ReturnType<typeof setInterval> | null>(null);

    useEffect(() => {
        pollRef.current = setInterval(async () => {
            try {
                const res = await fetch('/api/health');
                if (res.ok) setConnected(true);
            } catch {
                // ignore
            }
        }, 3000);

        return () => {
            if (pollRef.current) clearInterval(pollRef.current);
        };
    }, []);

    return (
        <div className="mt-4 space-y-4">
            <div>
                <p className="mb-2 text-xs font-semibold text-zinc-400">
                    Run the Nightwatch Agent
                </p>
                <CodeBlock onCopy="php artisan nightwatch:agent">
                    <span className="text-zinc-100">
                        php artisan nightwatch:agent
                    </span>
                </CodeBlock>
            </div>

            <div
                className={cn(
                    'flex items-center gap-3 rounded-lg border px-4 py-3',
                    connected
                        ? 'border-emerald-800 bg-emerald-950/40'
                        : 'border-zinc-700 bg-zinc-900',
                )}
            >
                <span className="text-xs font-bold tracking-wider text-zinc-500 uppercase">
                    Connection
                </span>
                <div className="flex items-center gap-1.5">
                    <span
                        className={cn(
                            'size-2 animate-pulse rounded-full',
                            connected ? 'bg-emerald-400' : 'bg-amber-400',
                        )}
                    />
                    {connected ? (
                        <Wifi className="size-4 text-emerald-400" />
                    ) : (
                        <WifiOff className="size-4 text-amber-400" />
                    )}
                    <span
                        className={cn(
                            'text-xs font-semibold uppercase',
                            connected ? 'text-emerald-400' : 'text-amber-400',
                        )}
                    >
                        {connected ? 'Connected' : 'Listening'}
                    </span>
                </div>
            </div>

            <div className="flex justify-between">
                <Button
                    variant="ghost"
                    onClick={onBack}
                    className="text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100"
                >
                    Back
                </Button>
                <div className="flex gap-2">
                    <Button
                        variant="ghost"
                        onClick={onSkip}
                        className="text-zinc-400 hover:bg-zinc-800 hover:text-zinc-100"
                    >
                        Skip
                    </Button>
                    <Button
                        onClick={onComplete}
                        className="bg-emerald-600 text-white hover:bg-emerald-700"
                    >
                        <Check className="mr-2 size-4" /> Complete
                    </Button>
                </div>
            </div>
        </div>
    );
}
