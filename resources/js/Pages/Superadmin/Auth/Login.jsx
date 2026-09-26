import { Head, useForm } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';
import { AuthHeading } from '@/Layouts/AuthLayout';
import { Button, Checkbox, FormField, Input, PasswordInput } from '@/Components/ui';
import { route } from '@/lib/route';

export default function SuperadminLogin() {
    const form = useForm({ email: '', password: '', remember: false });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('superadmin.login'), { onFinish: () => form.reset('password') });
    };

    return (
        <>
            <Head title="Superadmin sign in" />
            <span className="mb-6 flex size-12 items-center justify-center rounded-2xl bg-slate-900 text-white">
                <ShieldCheck className="size-6" />
            </span>
            <AuthHeading title="Superadmin sign in" description="Restricted area. All sign-in attempts are logged." />
            <form onSubmit={submit} className="space-y-5" noValidate>
                <FormField label="Email address" htmlFor="email" error={form.errors.email}>
                    <Input id="email" type="email" autoComplete="username" autoFocus value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} invalid={Boolean(form.errors.email)} />
                </FormField>
                <FormField label="Password" htmlFor="password" error={form.errors.password}>
                    <PasswordInput id="password" autoComplete="current-password" value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} invalid={Boolean(form.errors.password)} />
                </FormField>
                <Checkbox label="Remember this device" checked={form.data.remember} onChange={(e) => form.setData('remember', e.target.checked)} />
                <Button type="submit" size="lg" loading={form.processing} className="w-full bg-slate-900 text-white hover:bg-slate-800">
                    Sign in
                </Button>
            </form>
        </>
    );
}
