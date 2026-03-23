import { Head, useForm } from '@inertiajs/react';
import { ImageIcon, X } from 'lucide-react';
import { useRef, useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { TimezoneSelect } from '@/components/timezone-select';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem } from '@/types';

interface Organization {
    id: number;
    name: string;
    slug: string;
    timezone: string;
    logo_url: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organization settings', href: '#' },
];

export default function OrganizationGeneral({
    organization,
    timezones,
}: {
    organization: Organization;
    timezones: string[];
}) {
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [preview, setPreview] = useState<string | null>(organization.logo_url || null);

    const form = useForm<{
        name: string;
        timezone: string;
        logo: File | null;
    }>({
        name: organization.name,
        timezone: organization.timezone ?? '',
        logo: null,
    });

    function handleFileChange(e: React.ChangeEvent<HTMLInputElement>) {
        const file = e.target.files?.[0] ?? null;
        form.setData('logo', file);
        if (file) {
            setPreview(URL.createObjectURL(file));
        }
    }

    function removeLogo() {
        form.setData('logo', null);
        setPreview(null);
        if (fileInputRef.current) {
            fileInputRef.current.value = '';
        }
    }

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(`/settings/organizations/${organization.slug}`, {
            forceFormData: true,
            onSuccess: () => toast.success('Organization updated'),
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Organization settings" />

            <h1 className="sr-only">Organization Settings</h1>

            <SettingsLayout>
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="General"
                        description="Update your organization's name and details"
                    />

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Logo */}
                        <div className="grid gap-2">
                            <Label>Logo</Label>
                            <div className="flex items-center gap-4">
                                <div
                                    className="flex size-16 shrink-0 cursor-pointer items-center justify-center overflow-hidden rounded-lg border border-dashed border-input bg-muted transition-colors hover:bg-accent"
                                    onClick={() => fileInputRef.current?.click()}
                                >
                                    {preview ? (
                                        <img
                                            src={preview}
                                            alt="Logo preview"
                                            className="size-full object-cover"
                                        />
                                    ) : (
                                        <ImageIcon className="size-6 text-muted-foreground" />
                                    )}
                                </div>

                                <div className="flex flex-col gap-1.5">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => fileInputRef.current?.click()}
                                    >
                                        {preview ? 'Change logo' : 'Upload logo'}
                                    </Button>
                                    {preview && (
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            className="text-muted-foreground"
                                            onClick={removeLogo}
                                        >
                                            <X className="mr-1 size-3.5" />
                                            Remove
                                        </Button>
                                    )}
                                    <p className="text-xs text-muted-foreground">
                                        PNG, JPG up to 2 MB
                                    </p>
                                </div>
                            </div>

                            <input
                                ref={fileInputRef}
                                type="file"
                                accept="image/*"
                                className="hidden"
                                onChange={handleFileChange}
                            />
                            <InputError message={form.errors.logo} />
                        </div>

                        {/* Name */}
                        <div className="grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                required
                            />
                            <InputError message={form.errors.name} />
                        </div>

                        {/* Timezone */}
                        <div className="grid gap-2">
                            <Label>Timezone</Label>
                            <TimezoneSelect
                                timezones={timezones}
                                value={form.data.timezone}
                                onChange={(tz) => form.setData('timezone', tz)}
                            />
                            <InputError message={form.errors.timezone} />
                        </div>

                        <div className="flex items-center gap-4">
                            <Button disabled={form.processing}>Save</Button>
                        </div>
                    </form>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
