import { router } from '@inertiajs/react';
import { Loader2 } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';
import type { CreatedData } from './types';

const ENV_COLORS = [
    { label: 'Green', value: 'green', class: 'bg-emerald-500' },
    { label: 'Amber', value: 'amber', class: 'bg-amber-500' },
    { label: 'Blue', value: 'blue', class: 'bg-blue-500' },
    { label: 'Purple', value: 'purple', class: 'bg-violet-500' },
    { label: 'Red', value: 'red', class: 'bg-rose-500' },
    { label: 'Gray', value: 'gray', class: 'bg-zinc-500' },
];

export function WizardStep1({
    organizationId,
    created,
    onCreated,
}: {
    organizationId: number;
    created: CreatedData | null;
    onCreated: (data: CreatedData) => void;
}) {
    const [appName, setAppName] = useState('');
    const [envName, setEnvName] = useState('Production');
    const [envColor, setEnvColor] = useState('green');
    const [envUrl, setEnvUrl] = useState('');
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const isEditing = created !== null;

    function xsrfToken(): string {
        return decodeURIComponent(
            document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1] ?? '',
        );
    }

    async function handleCreate() {
        setLoading(true);
        setErrors({});

        try {
            const res = await fetch('/wizard/app', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': xsrfToken(),
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    organization_id: organizationId,
                    app_name: appName,
                    env_name: envName,
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
            router.reload({
                only: [
                    'activeOrganization',
                    'activeProject',
                    'activeEnvironment',
                    'projectGroups',
                    'organizations',
                ],
            });
            onCreated(data);
        } catch {
            setErrors({
                app_name: 'An unexpected error occurred. Please try again.',
            });
        } finally {
            setLoading(false);
        }
    }

    async function handleUpdate() {
        if (!created) return;
        setLoading(true);
        setErrors({});

        try {
            const res = await fetch(`/wizard/app/${created.project.slug}`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    'X-XSRF-TOKEN': xsrfToken(),
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    app_name: appName,
                    env_id: created.environment.id,
                    env_name: envName,
                    env_color: envColor,
                }),
            });

            if (!res.ok) {
                const data = await res.json();
                if (data.errors) setErrors(data.errors);
                return;
            }

            const data = await res.json();
            router.reload({
                only: [
                    'activeOrganization',
                    'activeProject',
                    'activeEnvironment',
                    'projectGroups',
                    'organizations',
                ],
            });
            onCreated({ ...data, token: created.token });
        } catch {
            setErrors({
                app_name: 'An unexpected error occurred. Please try again.',
            });
        } finally {
            setLoading(false);
        }
    }

    const colorClass =
        ENV_COLORS.find((c) => c.value === envColor)?.class ?? 'bg-emerald-500';

    return (
        <div className="mt-4 space-y-4">
            <div className="grid gap-1.5">
                <Label className="text-zinc-300">Application name</Label>
                <Input
                    value={appName}
                    onChange={(e) => setAppName(e.target.value)}
                    placeholder="My Application"
                    className="border-zinc-700 bg-zinc-900 text-zinc-100 placeholder:text-zinc-500 focus-visible:ring-zinc-600"
                />
                {errors.app_name && (
                    <p className="text-xs text-rose-400">{errors.app_name}</p>
                )}
            </div>

            <div className="space-y-3 rounded-lg border border-zinc-800 bg-zinc-900/50 p-4">
                <div>
                    <p className="text-sm font-medium text-zinc-200">
                        {isEditing
                            ? 'Environment'
                            : 'Add your first environment'}
                    </p>
                    {!isEditing && (
                        <p className="mt-0.5 text-xs text-zinc-500">
                            You can setup additional environments after your
                            initial setup.
                        </p>
                    )}
                </div>

                <div className="grid gap-1.5">
                    <Label className="text-xs text-zinc-400">
                        Environment name
                    </Label>
                    <Input
                        value={envName}
                        onChange={(e) => setEnvName(e.target.value)}
                        className="border-zinc-700 bg-zinc-900 text-zinc-100 placeholder:text-zinc-500 focus-visible:ring-zinc-600"
                    />
                    {errors.env_name && (
                        <p className="text-xs text-rose-400">
                            {errors.env_name}
                        </p>
                    )}
                </div>

                <div className="grid gap-1.5">
                    <Label className="text-xs text-zinc-400">
                        Environment color
                    </Label>
                    <div className="flex items-center gap-2">
                        <div
                            className={cn(
                                'size-4 shrink-0 rounded-full',
                                colorClass,
                            )}
                        />
                        <Select value={envColor} onValueChange={setEnvColor}>
                            <SelectTrigger className="flex-1 border-zinc-700 bg-zinc-900 text-zinc-100 focus:ring-zinc-600">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {ENV_COLORS.map((c) => (
                                    <SelectItem key={c.value} value={c.value}>
                                        {c.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="grid gap-1.5">
                    <Label className="text-xs text-zinc-400">
                        Environment URL{' '}
                        <span className="text-zinc-600">(optional)</span>
                    </Label>
                    <Input
                        value={envUrl}
                        onChange={(e) => setEnvUrl(e.target.value)}
                        placeholder="https://example.com"
                        className="border-zinc-700 bg-zinc-900 text-zinc-100 placeholder:text-zinc-500 focus-visible:ring-zinc-600"
                    />
                </div>
            </div>

            <div className="flex justify-end">
                <Button
                    onClick={isEditing ? handleUpdate : handleCreate}
                    disabled={loading || !appName}
                    className="bg-blue-600 text-white hover:bg-blue-700"
                >
                    {loading && (
                        <Loader2 className="mr-2 size-4 animate-spin" />
                    )}
                    {isEditing ? 'Update' : 'Create'}
                </Button>
            </div>
        </div>
    );
}
