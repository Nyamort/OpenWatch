import { usePage } from '@inertiajs/react';
import AppLogoIcon from '@/components/layout/app-logo-icon';
import type { AuthLayoutProps } from '@/types';

const features = [
    'Monitor exceptions, queries, and jobs in real time',
    'Set up alert rules to get notified when things go wrong',
    'Analyse performance across all your environments',
    'Multi-tenant support for teams and organisations',
];

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

type Props = AuthLayoutProps & {
    eyebrow?: string;
    headline?: React.ReactNode;
    subHeadline?: string;
    footerNote?: string;
};

export default function AuthBrandedLayout({
    children,
    title,
    description,
    eyebrow,
    headline,
    subHeadline,
    footerNote,
}: Props) {
    const { name } = usePage().props;

    return (
        <div className="grid min-h-svh lg:grid-cols-2">
            {/* Left panel — branding */}
            <div className="relative hidden flex-col overflow-hidden bg-zinc-950 lg:flex">
                {/* Grid pattern */}
                <div
                    className="absolute inset-0 opacity-[0.04]"
                    style={{
                        backgroundImage:
                            'linear-gradient(to right, white 1px, transparent 1px), linear-gradient(to bottom, white 1px, transparent 1px)',
                        backgroundSize: '48px 48px',
                    }}
                />

                {/* Glows */}
                <div className="absolute -top-32 -left-32 size-[520px] rounded-full bg-white/5 blur-3xl" />
                <div className="absolute bottom-0 right-0 size-[400px] rounded-full bg-white/3 blur-3xl" />

                <div className="relative flex h-full flex-col p-12">
                    {/* Logo */}
                    <div className="flex items-center gap-3">
                        <div className="flex size-9 items-center justify-center rounded-lg bg-indigo-600">
                            <AppLogoIcon className="size-5" />
                        </div>
                        <span className="text-base font-semibold tracking-tight text-white">
                            {name}
                        </span>
                    </div>

                    {/* Headline */}
                    <div className="mt-auto">
                        {eyebrow && (
                            <p className="text-xs font-medium tracking-widest text-white/40 uppercase">
                                {eyebrow}
                            </p>
                        )}
                        <h1 className={`text-4xl font-semibold tracking-tight text-white ${eyebrow ? 'mt-3' : ''}`}>
                            {headline ?? (
                                <>
                                    Monitor your{' '}
                                    <span className="text-white/70">applications</span>
                                </>
                            )}
                        </h1>
                        <p className="mt-4 max-w-sm text-base leading-relaxed text-white/50">
                            {subHeadline ?? 'Gain full visibility into errors, performance, and behaviour across all your environments.'}
                        </p>

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

                    {footerNote && (
                        <p className="mt-12 text-xs text-white/25">{footerNote}</p>
                    )}
                </div>
            </div>

            {/* Right panel — content */}
            <div className="flex items-center justify-center bg-background px-6 py-16 sm:px-10">
                <div className="w-full max-w-sm">
                    {/* Mobile logo */}
                    <div className="mb-8 flex items-center gap-3 lg:hidden">
                        <div className="flex size-8 items-center justify-center rounded-md bg-foreground">
                            <AppLogoIcon className="size-4 fill-current text-background" />
                        </div>
                        <span className="text-sm font-semibold">{name}</span>
                    </div>

                    <div className="mb-8 space-y-1">
                        <h2 className="text-xl font-semibold tracking-tight">{title}</h2>
                        <p className="text-sm text-muted-foreground">{description}</p>
                    </div>

                    {children}
                </div>
            </div>
        </div>
    );
}
