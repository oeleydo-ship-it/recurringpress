import { Head, useForm } from '@inertiajs/react';
import { KeyRound } from 'lucide-react';
import { AuthHeading } from '@/Layouts/AuthLayout';
import { Button, FormField, Input, PasswordInput } from '@/Components/ui';
import { route } from '@/lib/route';

export default function ResetPassword({ token, email }) {
    const form = useForm({ token, email: email ?? '', password: '', password_confirmation: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('password.update'), { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    return (
        <>
            <Head title="Choose a new password" />
            <AuthHeading title="Choose a new password" description="Use at least 8 characters with upper and lowercase letters and a number." />
            <form onSubmit={submit} className="space-y-5" noValidate>
                <FormField label="Email address" htmlFor="email" error={form.errors.email}>
                    <Input id="email" type="email" autoComplete="username" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} invalid={Boolean(form.errors.email)} />
                </FormField>
                <FormField label="New password" htmlFor="password" error={form.errors.password}>
                    <PasswordInput id="password" autoComplete="new-password" autoFocus value={form.data.password} onChange={(e) => form.setData('password', e.target.value)} invalid={Boolean(form.errors.password)} />
                </FormField>
                <FormField label="Confirm new password" htmlFor="password_confirmation" error={form.errors.password_confirmation}>
                    <PasswordInput
                        id="password_confirmation"
                        autoComplete="new-password"
                        value={form.data.password_confirmation}
                        onChange={(e) => form.setData('password_confirmation', e.target.value)}
                        invalid={Boolean(form.errors.password_confirmation)}
                    />
                </FormField>
                <Button type="submit" size="lg" icon={KeyRound} loading={form.processing} className="w-full bg-indigo-600 text-white hover:bg-indigo-700">
                    Reset password
                </Button>
            </form>
        </>
    );
}
