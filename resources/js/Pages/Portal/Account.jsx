import { useForm, usePage } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import PortalShell from '@/Components/portal/PortalShell';
import { Button, Card, CardBody, CardHeader, FormField, PasswordInput } from '@/Components/ui';

export default function PortalAccount() {
    const { portal } = usePage().props;
    const form = useForm({ current_password: '', password: '', password_confirmation: '' });

    const submit = (e) => {
        e.preventDefault();
        form.put(`${portal.urls.account}/password`, { preserveScroll: true, onSuccess: () => form.reset() });
    };

    return (
        <PortalShell title="Account">
            <h1 className="text-2xl font-semibold tracking-tight text-slate-900">Account</h1>
            <Card className="mt-6 max-w-xl">
                <CardHeader title="Change password" description={`Signed in as ${portal.client?.email}`} />
                <CardBody>
                    <form onSubmit={submit} className="space-y-5" noValidate>
                        <FormField label="Current password" htmlFor="current_password" error={form.errors.current_password}>
                            <PasswordInput id="current_password" autoComplete="current-password" value={form.data.current_password} onChange={(e) => form.setData('current_password', e.target.value)} invalid={Boolean(form.errors.current_password)} />
                        </FormField>
                        <FormField label="New password" htmlFor="password" error={form.errors.password} hint="At least 8 characters with upper and lowercase letters and a number.">
                            <PasswordInput id="password" autoComplete="new-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} invalid={Boolean(form.errors.password)} />
                        </FormField>
                        <FormField label="Confirm new password" htmlFor="password_confirmation" error={form.errors.password_confirmation}>
                            <PasswordInput id="password_confirmation" autoComplete="new-password" value={form.data.password_confirmation} onChange={(e) => form.setData('password_confirmation', e.target.value)} invalid={Boolean(form.errors.password_confirmation)} />
                        </FormField>
                        <Button type="submit" icon={KeyRound} loading={form.processing}>
                            Change password
                        </Button>
                    </form>
                </CardBody>
            </Card>
        </PortalShell>
    );
}
