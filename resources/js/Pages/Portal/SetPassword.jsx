import { Link, useForm, usePage } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { PortalAuthCard } from '@/Components/portal/PortalShell';
import { Button, FormField, Input, PasswordInput } from '@/Components/ui';

export default function PortalSetPassword({ valid, email, action }) {
    const { portal } = usePage().props;
    const form = useForm({ password: '', password_confirmation: '' });

    if (!valid) {
        return (
            <PortalAuthCard title="This link has expired" description="Links work once and expire after a while. You can request a new one.">
                <Button href={portal.urls.forgot} size="lg" className="w-full">
                    Request a new link
                </Button>
            </PortalAuthCard>
        );
    }

    const submit = (e) => {
        e.preventDefault();
        form.post(action, { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <PortalAuthCard title="Choose your password" description="Use at least 8 characters with upper and lowercase letters and a number.">
            <form onSubmit={submit} className="space-y-5" noValidate>
                <FormField label="Email address" htmlFor="email">
                    <Input id="email" type="email" autoComplete="username" value={email} readOnly />
                </FormField>
                <FormField label="New password" htmlFor="password" error={form.errors.password}>
                    <PasswordInput id="password" autoComplete="new-password" autoFocus value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} invalid={Boolean(form.errors.password)} />
                </FormField>
                <FormField label="Confirm new password" htmlFor="password_confirmation" error={form.errors.password_confirmation}>
                    <PasswordInput id="password_confirmation" autoComplete="new-password" value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} invalid={Boolean(form.errors.password_confirmation)} />
                </FormField>
                <Button type="submit" size="lg" icon={KeyRound} loading={form.processing} className="w-full">
                    Save password and sign in
                </Button>
            </form>
            <p className="mt-6 text-center text-sm">
                <Link href={portal.urls.login} className="font-medium text-brand-600 hover:text-brand-700">
                    Back to sign in
                </Link>
            </p>
        </PortalAuthCard>
    );
}
