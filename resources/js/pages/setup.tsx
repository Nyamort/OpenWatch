import { Form, Head, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import AuthBrandedLayout from '@/layouts/auth/auth-branded-layout';
import { store } from '@/routes/setup';

export default function Setup() {
    const { name } = usePage().props;

    return (
        <AuthBrandedLayout
            title="Create your account"
            description="You'll be the administrator of this instance."
            eyebrow="First-time setup"
            headline={
                <>
                    Welcome to <span className="text-white/70">{name}</span>
                </>
            }
            subHeadline="Create your administrator account to get started. You can invite teammates after logging in."
            footerNote="This page is only accessible until the first account is created."
        >
            <Head title="Setup" />

            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-5"
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
                            <Label htmlFor="email">Email address</Label>
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
                            <Label htmlFor="password">Password</Label>
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
        </AuthBrandedLayout>
    );
}
