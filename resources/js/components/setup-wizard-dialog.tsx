import { router, usePage } from '@inertiajs/react';
import {
    Check,
    CheckCircle2,
    ClipboardCopy,
    Info,
    Loader2,
    Wifi,
    WifiOff,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

interface CreatedData {
    project: { id: number; name: string; slug: string };
    environment: { id: number; name: string; slug: string };
    token: string;
}

const ENV_COLORS = [
    { label: 'Green', value: 'green', class: 'bg-emerald-500' },
    { label: 'Amber', value: 'amber', class: 'bg-amber-500' },
    { label: 'Blue', value: 'blue', class: 'bg-blue-500' },
    { label: 'Purple', value: 'purple', class: 'bg-violet-500' },
    { label: 'Red', value: 'red', class: 'bg-rose-500' },
    { label: 'Gray', value: 'gray', class: 'bg-zinc-500' },
];

const ENV_TYPES = [
    { label: 'Production', value: 'production', color: 'green' },
    { label: 'Staging', value: 'staging', color: 'amber' },
    { label: 'Development', value: 'development', color: 'blue' },
    { label: 'Custom', value: 'custom', color: 'gray' },
];

function slugify(value: string): string {
    return value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
}

function CodeBlock({
    children,
    onCopy,
}: {
    children: React.ReactNode;
    onCopy: string;
}) {
    const [copied, setCopied] = useState(false);

    function handleCopy() {
        navigator.clipboard.writeText(onCopy);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    }

    return (
        <div className="relative flex items-center rounded-md bg-zinc-900 px-4 py-3 font-mono text-sm min-w-0">
            <span className="flex-1 overflow-x-auto whitespace-nowrap scrollbar-none min-w-0 pr-2">{children}</span>
            <button
                onClick={handleCopy}
                className="ml-1 shrink-0 text-zinc-400 transition-colors hover:text-zinc-100"
                title="Copy"
            >
                {copied ? <Check className="size-4 text-emerald-400" /> : <ClipboardCopy className="size-4" />}
            </button>
        </div>
    );
}

function Token({ value }: { value: string }) {
    return (
        <span>
            <span className="text-emerald-400">NIGHTWATCH_TOKEN</span>
            <span className="text-zinc-100">={value}</span>
        </span>
    );
}

function EnvVar({ name, value }: { name: string; value: string }) {
    return (
        <span>
            <span className="text-emerald-400">{name}</span>
            <span className="text-zinc-100">={value}</span>
        </span>
    );
}

function StepHeader({
    number,
    title,
    done,
    active,
}: {
    number: number;
    title: string;
    done: boolean;
    active: boolean;
}) {
    return (
        <div className="flex items-center gap-3">
            <div
                className={cn(
                    'flex size-6 shrink-0 items-center justify-center rounded-full border text-xs font-semibold',
                    active
                        ? 'border-zinc-300 bg-zinc-300 text-zinc-900'
                        : 'border-zinc-600 text-zinc-500',
                )}
            >
                {number}
            </div>
            <span className={cn('text-sm font-medium', active ? 'text-zinc-100' : 'text-zinc-500')}>
                {title}
            </span>
            {done && (
                <span className="ml-auto flex items-center gap-1 text-xs font-semibold text-emerald-400">
                    <CheckCircle2 className="size-3.5" />
                    DONE
                </span>
            )}
        </div>
    );
}

export function SetupWizardDialog({ open, onOpenChange }: Props) {
    const { activeOrganization } = usePage().props as {
        activeOrganization?: { id: number; name: string; slug: string } | null;
    };

    const [step, setStep] = useState(1);
    const [completedSteps, setCompletedSteps] = useState<Set<number>>(new Set());
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [created, setCreated] = useState<CreatedData | null>(null);
    const [connected, setConnected] = useState(false);
    const pollRef = useRef<ReturnType<typeof setInterval> | null>(null);

    // Step 1 form
    const [appName, setAppName] = useState('');
    const [envName, setEnvName] = useState('Production');
    const [envSlug, setEnvSlug] = useState('production');
    const [envColor, setEnvColor] = useState('green');
    const [envUrl, setEnvUrl] = useState('');

    // Step 2 tab
    const [logTab, setLogTab] = useState<'single' | 'stack'>('single');

    function resetWizard() {
        setStep(1);
        setCompletedSteps(new Set());
        setCreated(null);
        setConnected(false);
        setLoading(false);
        setErrors({});
        setAppName('');
        setEnvName('Production');
        setEnvSlug('production');
        setEnvColor('green');
        setEnvUrl('');
        if (pollRef.current) clearInterval(pollRef.current);
    }

    function handleOpenChange(value: boolean) {
        if (!value) resetWizard();
        onOpenChange(value);
    }

    function handleAppNameChange(value: string) {
        setAppName(value);
    }

    function handleEnvNameChange(value: string) {
        setEnvName(value);
        setEnvSlug(slugify(value));
    }

    function complete(n: number) {
        setCompletedSteps((prev) => new Set([...prev, n]));
    }

    async function handleCreate() {
        if (!activeOrganization) return;
        setLoading(true);
        setErrors({});

        try {
            const xsrfToken = decodeURIComponent(
                document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
            );
            const res = await fetch('/wizard/app', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': xsrfToken,
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    organization_id: activeOrganization.id,
                    app_name: appName,
                    app_slug: slugify(appName),
                    env_name: envName,
                    env_slug: envSlug,
                    env_type: ENV_TYPES.find((t) => t.color === envColor)?.value ?? 'production',
                    env_color: envColor,
                    env_url: envUrl || null,
                }),
            });

            if (!res.ok) {
                const data = await res.json();
                if (data.errors) setErrors(data.errors);
                return;
            }

            const data: CreatedData = await res.json();
            setCreated(data);
            complete(1);
            setStep(2);
            router.reload({ only: ['activeOrganization', 'activeProject', 'activeEnvironment', 'projectGroups', 'organizations'] });
        } catch {
            setErrors({ app_name: 'An unexpected error occurred. Please try again.' });
        } finally {
            setLoading(false);
        }
    }

    // Poll for connection on step 4
    useEffect(() => {
        if (step !== 4 || !created) return;

        pollRef.current = setInterval(async () => {
            try {
                const res = await fetch(`/api/health`);
                if (res.ok) {
                    // In a real app, check for environment data here
                    // For now just check connectivity
                }
            } catch {
                // ignore
            }
        }, 3000);

        return () => {
            if (pollRef.current) clearInterval(pollRef.current);
        };
    }, [step, created]);

    const colorClass = ENV_COLORS.find((c) => c.value === envColor)?.class ?? 'bg-emerald-500';

    const orgName = activeOrganization?.name ?? '—';
    const projectName = created?.project.name ?? (appName || 'New Application');

    return (
        <Dialog open={open} onOpenChange={handleOpenChange}>
            <DialogContent className="max-w-2xl bg-zinc-950 text-zinc-100 border-zinc-800 p-0 gap-0 overflow-hidden">
                {/* Persistent header */}
                <DialogHeader className="border-b border-zinc-800 px-6 py-4">
                    <div className="flex items-center justify-between">
                        <DialogTitle asChild>
                            <div className="flex items-center gap-1.5 text-sm">
                                <span className="text-zinc-500">{orgName}</span>
                                <span className="text-zinc-600">/</span>
                                <span className="font-semibold text-zinc-100">{projectName}</span>
                            </div>
                        </DialogTitle>
                        {completedSteps.has(step - 1) && (
                            <span className="flex items-center gap-1 text-xs font-bold text-emerald-400 mr-8">
                                <CheckCircle2 className="size-3.5" /> DONE
                            </span>
                        )}
                    </div>
                </DialogHeader>

                <div className="flex flex-col divide-y divide-zinc-800 overflow-y-auto max-h-[70vh]">
                    {/* ── Step 1 ── */}
                    <div className="px-6 py-4">
                        <StepHeader
                            number={1}
                            title="Create Application"
                            done={completedSteps.has(1)}
                            active={step >= 1}
                        />

                        {step === 1 && (
                            <div className="mt-4 space-y-4">
                                {/* App name */}
                                <div className="grid gap-1.5">
                                    <Label className="text-zinc-300">Application name</Label>
                                    <Input
                                        value={appName}
                                        onChange={(e) => handleAppNameChange(e.target.value)}
                                        placeholder="My Application"
                                        className="bg-zinc-900 border-zinc-700 text-zinc-100 placeholder:text-zinc-500 focus-visible:ring-zinc-600"
                                    />
                                    {errors.app_name && <p className="text-xs text-rose-400">{errors.app_name}</p>}
                                </div>

                                {/* First environment */}
                                <div className="rounded-lg border border-zinc-800 bg-zinc-900/50 p-4 space-y-3">
                                    <div>
                                        <p className="text-sm font-medium text-zinc-200">Add your first environment</p>
                                        <p className="text-xs text-zinc-500 mt-0.5">
                                            You can setup additional environments after your initial setup.
                                        </p>
                                    </div>

                                    {/* Env name */}
                                    <div className="grid gap-1.5">
                                        <Label className="text-zinc-400 text-xs">Environment name</Label>
                                        <Input
                                            value={envName}
                                            onChange={(e) => handleEnvNameChange(e.target.value)}
                                            className="bg-zinc-900 border-zinc-700 text-zinc-100 placeholder:text-zinc-500 focus-visible:ring-zinc-600"
                                        />
                                        {errors.env_name && <p className="text-xs text-rose-400">{errors.env_name}</p>}
                                    </div>

                                    {/* Env color */}
                                    <div className="grid gap-1.5">
                                        <Label className="text-zinc-400 text-xs">Environment color</Label>
                                        <div className="flex items-center gap-2">
                                            <div className={cn('size-4 rounded-full shrink-0', colorClass)} />
                                            <select
                                                value={envColor}
                                                onChange={(e) => setEnvColor(e.target.value)}
                                                className="flex-1 rounded-md border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-sm text-zinc-100 focus:outline-none focus:ring-1 focus:ring-zinc-600"
                                            >
                                                {ENV_COLORS.map((c) => (
                                                    <option key={c.value} value={c.value}>{c.label}</option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>

                                    {/* Env URL */}
                                    <div className="grid gap-1.5">
                                        <Label className="text-zinc-400 text-xs">
                                            Environment URL{' '}
                                            <span className="text-zinc-600">(optional)</span>
                                        </Label>
                                        <Input
                                            value={envUrl}
                                            onChange={(e) => setEnvUrl(e.target.value)}
                                            placeholder="https://example.com"
                                            className="bg-zinc-900 border-zinc-700 text-zinc-100 placeholder:text-zinc-500 focus-visible:ring-zinc-600"
                                        />
                                    </div>
                                </div>

                                <div className="flex justify-end">
                                    <Button
                                        onClick={handleCreate}
                                        disabled={loading || !appName || !activeOrganization}
                                        className="bg-blue-600 hover:bg-blue-700 text-white"
                                    >
                                        {loading && <Loader2 className="mr-2 size-4 animate-spin" />}
                                        Create
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* ── Step 2 ── */}
                    <div className="px-6 py-4">
                        <StepHeader
                            number={2}
                            title="Install Agent"
                            done={completedSteps.has(2)}
                            active={step >= 2}
                        />

                        {step === 2 && (
                            <div className="mt-4 space-y-5">
                                {/* Install package */}
                                <div>
                                    <p className="text-xs font-semibold text-zinc-400 mb-2">
                                        Install the Nightwatch package
                                    </p>
                                    <CodeBlock onCopy="composer require laravel/nightwatch">
                                        <span className="text-zinc-100">composer require laravel/nightwatch</span>
                                    </CodeBlock>
                                </div>

                                {/* Token */}
                                <div>
                                    <p className="text-xs font-semibold text-zinc-400 mb-2">
                                        Add the token to your environment variables
                                    </p>
                                    <CodeBlock onCopy={`NIGHTWATCH_TOKEN=${created?.token ?? ''}`}>
                                        <Token value={created?.token ?? '...'} />
                                    </CodeBlock>
                                </div>

                                {/* Log channel */}
                                <div>
                                    <div className="flex items-center justify-between mb-2">
                                        <p className="text-xs font-semibold text-zinc-400">
                                            Configure Nightwatch to capture logs
                                        </p>
                                        <span className="text-xs text-zinc-600">Optional</span>
                                    </div>
                                    {/* Tab switcher */}
                                    <div className="flex gap-4 mb-2 border-b border-zinc-800">
                                        {(['single', 'stack'] as const).map((tab) => (
                                            <button
                                                key={tab}
                                                onClick={() => setLogTab(tab)}
                                                className={cn(
                                                    'pb-1.5 text-xs font-medium transition-colors border-b-2 -mb-px',
                                                    logTab === tab
                                                        ? 'border-zinc-300 text-zinc-100'
                                                        : 'border-transparent text-zinc-500 hover:text-zinc-300',
                                                )}
                                            >
                                                {tab === 'single' ? 'Single Channel' : 'Log Stack'}
                                            </button>
                                        ))}
                                    </div>
                                    {logTab === 'single' ? (
                                        <CodeBlock onCopy="LOG_CHANNEL=nightwatch">
                                            <EnvVar name="LOG_CHANNEL" value="nightwatch" />
                                        </CodeBlock>
                                    ) : (
                                        <CodeBlock onCopy="LOG_STACK=nightwatch">
                                            <EnvVar name="LOG_STACK" value="nightwatch" />
                                        </CodeBlock>
                                    )}
                                </div>

                                <div className="flex justify-between">
                                    <Button
                                        variant="ghost"
                                        onClick={() => setStep(1)}
                                        className="text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800"
                                    >
                                        Back
                                    </Button>
                                    <Button
                                        onClick={() => { complete(2); setStep(3); }}
                                        className="bg-blue-600 hover:bg-blue-700 text-white"
                                    >
                                        Next
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* ── Step 3 ── */}
                    <div className="px-6 py-4">
                        <StepHeader
                            number={3}
                            title="Sampling"
                            done={completedSteps.has(3)}
                            active={step >= 3}
                        />

                        {step === 3 && (
                            <div className="mt-4 space-y-4">
                                <p className="text-sm text-zinc-400">
                                    Sampling reduces the number of events Nightwatch records, helping control
                                    storage and performance costs. We recommend starting at{' '}
                                    <span className="text-zinc-200 font-medium">10%</span> and adjusting
                                    based on your traffic.
                                </p>

                                {/* Config */}
                                <div>
                                    <div className="flex gap-2 mb-2">
                                        <select className="rounded-md border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-sm text-zinc-100 focus:outline-none">
                                            <option>Global sampling rates</option>
                                        </select>
                                        <select className="rounded-md border border-zinc-700 bg-zinc-900 px-3 py-1.5 text-sm text-zinc-100 focus:outline-none">
                                            <option>All events included</option>
                                        </select>
                                    </div>
                                    <CodeBlock onCopy="NIGHTWATCH_REQUEST_SAMPLE_RATE=0.1">
                                        <EnvVar name="NIGHTWATCH_REQUEST_SAMPLE_RATE" value="0.1" />
                                    </CodeBlock>
                                </div>

                                {/* Info banner */}
                                <div className="flex items-center gap-2 rounded-md border border-blue-800 bg-blue-950/40 px-3 py-2">
                                    <Info className="size-4 shrink-0 text-blue-400" />
                                    <span className="text-xs text-blue-300">
                                        Learn more about additional sampling options
                                    </span>
                                </div>

                                <div className="flex justify-between">
                                    <Button
                                        variant="ghost"
                                        onClick={() => setStep(2)}
                                        className="text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800"
                                    >
                                        Back
                                    </Button>
                                    <Button
                                        onClick={() => { complete(3); setStep(4); }}
                                        className="bg-blue-600 hover:bg-blue-700 text-white"
                                    >
                                        Next
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>

                    {/* ── Step 4 ── */}
                    <div className="px-6 py-4">
                        <StepHeader
                            number={4}
                            title="Setup Agent"
                            done={completedSteps.has(4)}
                            active={step >= 4}
                        />

                        {step === 4 && (
                            <div className="mt-4 space-y-4">
                                <div>
                                    <p className="text-xs font-semibold text-zinc-400 mb-2">
                                        Run the Nightwatch Agent
                                    </p>
                                    <CodeBlock onCopy="php artisan nightwatch:agent">
                                        <span className="text-zinc-100">php artisan nightwatch:agent</span>
                                    </CodeBlock>
                                </div>

                                {/* Connection status */}
                                <div
                                    className={cn(
                                        'flex items-center gap-3 rounded-lg border px-4 py-3',
                                        connected
                                            ? 'border-emerald-800 bg-emerald-950/40'
                                            : 'border-zinc-700 bg-zinc-900',
                                    )}
                                >
                                    <span className="text-xs font-bold uppercase tracking-wider text-zinc-500">
                                        Connection
                                    </span>
                                    <div className="flex items-center gap-1.5">
                                        <span
                                            className={cn(
                                                'size-2 rounded-full',
                                                connected ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400 animate-pulse',
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
                                        onClick={() => setStep(3)}
                                        className="text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800"
                                    >
                                        Back
                                    </Button>
                                    <div className="flex gap-2">
                                        <Button
                                            variant="ghost"
                                            onClick={() => handleOpenChange(false)}
                                            className="text-zinc-400 hover:text-zinc-100 hover:bg-zinc-800"
                                        >
                                            Skip
                                        </Button>
                                        <Button
                                            onClick={() => { complete(4); handleOpenChange(false); }}
                                            className="bg-emerald-600 hover:bg-emerald-700 text-white"
                                        >
                                            <Check className="mr-2 size-4" /> Complete
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
