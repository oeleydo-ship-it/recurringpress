import { Head, Link, useForm } from '@inertiajs/react';
import { Check, UserPlus } from 'lucide-react';
import { AuthHeading } from '@/Layouts/AuthLayout';
import Honeypot, { useHoneypot } from '@/Components/Honeypot';
import { Button, FieldError, FormField, Input, PasswordInput } from '@/Components/ui';
import { route } from '@/lib/route';
import { cn } from '@/lib/utils';

const rules = [
    { label: '8+ characters', test: (v) => v.length >= 8 },
    { label: 'Upper & lowercase', test: (v) => /[a-z]/.test(v) && /[A-Z]/.test(v) },
    { label: 'A number', test: (v) => /\d/.test(v) },
];

export default function Register({ plan, planSlug }) {
    const hp = useHoneypot();
    const form = useForm({
        ...hp.fields,
        name: '',
        email: '',
        company_name: '',
        password: '',
        password_confirmation: '',
        plan: planSlug ?? 'free',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('register'), { onFinish: () => form.reset('password', 'password_confirmation') });
    };

    const paid = plan && Number(plan.price) > 0;

    return (
        <>
            <Head title="Create your account" />
            <AuthHeading
                title={paid ? (plan.trial_days > 0 ? `Start your ${plan.trial_days}-day ${plan.name} trial` : `Sign up for ${plan.name}`) : 'Create your free account'}
                description={
                    <>
                        {paid && plan.trial_days > 0 ? 'No credit card required. ' : ''}Already have an account?{' '}
                        <Link href={route('login')} className="font-semibold text-indigo-600 hover:text-indigo-700">
                            Log in
                        </Link>
                    </>
                }
            />

            <form onSubmit={submit} className="space-y-4" noValidate>
                <Honeypot form={form} />
                <FieldError>{form.errors.form}</FieldError>

                <div className="grid gap-4 sm:grid-cols-2">
                    <FormField label="Your name" htmlFor="name" error={form.errors.name}>
                        <Input id="name" autoComplete="name" autoFocus value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} invalid={Boolean(form.errors.name)} />
                    </FormField>
                    <FormField label="Company" htmlFor="company_name" error={form.errors.company_name}>
                        <Input
                            id="company_name"
                            autoComplete="organization"
                            value={form.data.company_name}
                            onChange={(e) => form.setData('company_name', e.target.value)}
                            invalid={Boolean(form.errors.company_name)}
                        />
                    </FormField>
                </div>

                <FormField label="Work email" htmlFor="email" error={form.errors.email}>
                    <Input
                        id="email"
                        type="email"
                        autoComplete="email"
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                        invalid={Boolean(form.errors.email)}
                        placeholder="you@company.com"
                    />
                </FormField>

                <FormField label="Password" htmlFor="password" error={form.errors.password}>
                    <PasswordInput
                        id="password"
                        autoComplete="new-password"
                        value={form.data.password}
                        onChange={(e) => form.setData('password', e.target.value)}
                        invalid={Boolean(form.errors.password)}
                    />
                </FormField>
                <ul className="-mt-1 flex flex-wrap gap-x-4 gap-y-1">
                    {rules.map((r) => {
                        const ok = r.test(form.data.password);
                        return (
                            <li key={r.label} className={cn('flex items-center gap-1 text-xs', ok ? 'text-emerald-600' : 'text-slate-400')}>
                                <Check className={cn('size-3.5', !ok && 'opacity-40')} /> {r.label}
                            </li>
                        );
                    })}
                </ul>

                <FormField label="Confirm password" htmlFor="password_confirmation" error={form.errors.password_confirmation}>
                    <PasswordInput
                        id="password_confirmation"
                        autoComplete="new-password"
                        value={form.data.password_confirmation}
                        onChange={(e) => form.setData('password_confirmation', e.target.value)}
                        invalid={Boolean(form.errors.password_confirmation)}
                    />
                </FormField>

                <Button type="submit" size="lg" icon={UserPlus} loading={form.processing} className="mt-2 w-full bg-indigo-600 text-white hover:bg-indigo-700">
                    Create account
                </Button>
                <p className="text-center text-xs text-slate-500">By creating an account you agree to our terms and privacy policy.</p>
            </form>
        </>
    );
}
