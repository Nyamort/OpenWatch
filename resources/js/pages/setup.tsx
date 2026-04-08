import { Form, Head, usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/layout/app-logo-icon';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/setup';

function CheckIcon() {
    return (
        <svg
            viewBox="0 0 16 16"
            fill="none"
            className="size-4 shrink-0"
            aria-hidden="true"
        >
            <circle cx="8" cy="8" r="7.5" stroke="currentColor" strokeOpacity="0.3" />
            <path
                d="M5 8.5l2 2 4-4"
                stroke="currentColor"
                strokeWidth="1.5"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}

const features = [
    'Monitor exceptions, queries, and jobs in real time',
    'Set up alert rules to get notified when things go wrong',
    'Analyse performance across all your environments',
    'Multi-tenant support for teams and organisations',
];

export default function Setup() {
    const { name } = usePage().props;

    return (
        <>
            <Head title="Setup" />

            <div className="grid min-h-svh lg:grid-cols-2">
                {/* Left panel — branding */}
                <div className="relative hidden flex-col overflow-hidden bg-zinc-950 lg:flex">
                    {/* Subtle grid pattern */}
                    <div
                        className="absolute inset-0 opacity-[0.04]"
                        style={{
                            backgroundImage:
                                'linear-gradient(to right, white 1px, transparent 1px), linear-gradient(to bottom, white 1px, transparent 1px)',
                            backgroundSize: '48px 48px',
                        }}
                    />

                    {/* Radial glow */}
                    <div className="absolute -top-32 -left-32 size-[520px] rounded-full bg-white/5 blur-3xl" />
                    <div className="absolute bottom-0 right-0 size-[400px] rounded-full bg-white/3 blur-3xl" />

                    <div className="relative flex h-full flex-col p-12">
                        {/* Logo + name */}
                        <div className="flex items-center gap-3">
                            <div className="flex size-9 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/15">
                                <AppLogoIcon className="size-5 fill-current text-white" />
                            </div>
                            <span className="text-base font-semibold tracking-tight text-white">
                                {name}
                            </span>
                        </div>

                        {/* Headline */}
                        <div className="mt-auto">
                            <p className="text-xs font-medium tracking-widest text-white/40 uppercase">
                                First-time setup
                            </p>
                            <h1 className="mt-3 text-4xl font-semibold tracking-tight text-white">
                                Welcome to{' '}
                                <span className="text-white/70">{name}</span>
                            </h1>
                            <p className="mt-4 max-w-sm text-base leading-relaxed text-white/50">
                                Create your administrator account to get started.
                                You can invite teammates after logging in.
                            </p>

                            {/* Feature list */}
                            <ul className="mt-10 space-y-3">
                                {features.map((feature) => (
                                    <li
                                        key={feature}
                                        className="flex items-center gap-3 text-sm text-white/60"
                                    >
                                        <CheckIcon />
                                        {feature}
                                    </li>
                                ))}
                            </ul>
                        </div>

                        {/* Footer note */}
                        <p className="mt-12 text-xs text-white/25">
                            This page is only accessible until the first account
                            is created.
                        </p>
                    </div>
                </div>

                {/* Right panel — form */}
                <div className="flex items-center justify-center bg-background px-6 py-16 sm:px-10">
                    <div className="w-full max-w-sm">
                        {/* Mobile logo */}
                        <div className="mb-8 flex items-center gap-3 lg:hidden">
                            <div className="flex size-8 items-center justify-center rounded-md bg-foreground">
                                <AppLogoIcon className="size-4 fill-current text-background" />
                            </div>
                            <span className="text-sm font-semibold">{name}</span>
                        </div>

                        <div className="space-y-1">
                            <h2 className="text-xl font-semibold tracking-tight">
                                Create your account
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                You'll be the administrator of this instance.
                            </p>
                        </div>

                        <Form
                            {...store.form()}
                            resetOnSuccess={['password', 'password_confirmation']}
                            disableWhileProcessing
                            className="mt-8 flex flex-col gap-5"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Full name</Label>
                                        <Input
                                            id="name"
                                            type="text"
                                            name="name"
                                            required
                                            autoFocus
                                            autoComplete="name"
                                            placeholder="Jane Doe"
                                            tabIndex={1}
                                        />
                                        <InputError message={errors.name} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="email">
                                            Email address
                                        </Label>
                                        <Input
                                            id="email"
                                            type="email"
                                            name="email"
                                            required
                                            autoComplete="email"
                                            placeholder="admin@example.com"
                                            tabIndex={2}
                                        />
                                        <InputError message={errors.email} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password">
                                            Password
                                        </Label>
                                        <Input
                                            id="password"
                                            type="password"
                                            name="password"
                                            required
                                            autoComplete="new-password"
                                            placeholder="Choose a strong password"
                                            tabIndex={3}
                                        />
                                        <InputError message={errors.password} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="password_confirmation">
                                            Confirm password
                                        </Label>
                                        <Input
                                            id="password_confirmation"
                                            type="password"
                                            name="password_confirmation"
                                            required
                                            autoComplete="new-password"
                                            placeholder="Repeat your password"
                                            tabIndex={4}
                                        />
                                        <InputError
                                            message={errors.password_confirmation}
                                        />
                                    </div>

                                    <Button
                                        type="submit"
                                        className="mt-2 w-full"
                                        tabIndex={5}
                                    >
                                        {processing && <Spinner />}
                                        Create account &amp; continue
                                    </Button>
                                </>
                            )}
                        </Form>
                    </div>
                </div>
            </div>
        </>
    );
}
