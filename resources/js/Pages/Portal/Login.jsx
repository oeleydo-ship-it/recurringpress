import { Link, useForm, usePage } from '@inertiajs/react';
import { LogIn } from 'lucide-react';
import { PortalAuthCard } from '@/Components/portal/PortalShell';
import { Button, FormField, Input, PasswordInput } from '@/Components/ui';

export default function PortalLogin() {
    const { portal } = usePage().props;
    const form = useForm({ email: '', password: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post(portal.urls.login, { onFinish: () => form.reset('password') });
    };

    return (
        <PortalAuthCard title="Client sign in" description={`See your invoices and quotations from ${portal.company.name}.`}>
            <form onSubmit={submit} className="space-y-5" noValidate>
                <FormField label="Email address" htmlFor="email" error={form.errors.email}>
                    <Input id="email" type="email" autoComplete="username" autoFocus value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} invalid={Boolean(form.errors.email)} />
                </FormField>
                <div>
                    <div className="mb-1.5 flex items-center justify-between">
                        <label htmlFor="password" className="text-sm font-medium text-slate-700">
                            Password
                        </label>
                        <Link href={portal.urls.forgot} className="text-sm font-medium text-brand-600 hover:text-brand-700">
                            Forgot password?
                        </Link>
                    </div>
                    <FormField error={form.errors.password}>
                        <PasswordInput id="password" autoComplete="current-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} invalid={Boolean(form.errors.password)} />
                    </FormField>
                </div>
                <Button type="submit" size="lg" icon={LogIn} loading={form.processing} className="w-full">
                    Sign in
                </Button>
            </form>
            <p className="mt-6 text-center text-xs text-slate-500">Access is by invitation. Contact {portal.company.name} if you need an account.</p>
        </PortalAuthCard>
    );
}
