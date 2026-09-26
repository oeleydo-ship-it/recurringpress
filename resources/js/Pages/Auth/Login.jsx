import { Head, Link, useForm } from '@inertiajs/react';
import { LogIn } from 'lucide-react';
import { AuthHeading } from '@/Layouts/AuthLayout';
import { Button, Checkbox, FormField, Input, PasswordInput } from '@/Components/ui';
import { route } from '@/lib/route';

export default function Login() {
    const form = useForm({ email: '', password: '', remember: false });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('login'), { onFinish: () => form.reset('password') });
    };

    return (
        <>
            <Head title="Log in" />
            <AuthHeading
                title="Welcome back"
                description={
                    <>
                        New here?{' '}
                        <Link href={route('register')} className="font-semibold text-indigo-600 hover:text-indigo-700">
                            Create a free account
                        </Link>
                    </>
                }
            />

            <form onSubmit={submit} className="space-y-5" noValidate>
                <FormField label="Email address" htmlFor="email" error={form.errors.email}>
                    <Input
                        id="email"
                        type="email"
                        autoComplete="username"
                        autoFocus
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                        invalid={Boolean(form.errors.email)}
                        placeholder="you@company.com"
                    />
                </FormField>

                <div>
                    <div className="mb-1.5 flex items-center justify-between">
                        <label htmlFor="password" className="text-sm font-medium text-slate-700">
                            Password
                        </label>
                        <Link href={route('password.request')} className="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                            Forgot password?
                        </Link>
                    </div>
                    <FormField error={form.errors.password}>
                        <PasswordInput
                            id="password"
                            autoComplete="current-password"
                            value={form.data.password}
                            onChange={(e) => form.setData('password', e.target.value)}
                            invalid={Boolean(form.errors.password)}
                        />
                    </FormField>
                </div>

                <Checkbox label="Keep me signed in" checked={form.data.remember} onChange={(e) => form.setData('remember', e.target.checked)} />

                <Button type="submit" size="lg" icon={LogIn} loading={form.processing} className="w-full bg-indigo-600 text-white hover:bg-indigo-700">
                    Log in
                </Button>
            </form>
        </>
    );
}
