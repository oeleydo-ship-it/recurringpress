import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, MailCheck, Send } from 'lucide-react';
import { AuthHeading } from '@/Layouts/AuthLayout';
import Honeypot, { useHoneypot } from '@/Components/Honeypot';
import { Button, FieldError, FormField, Input } from '@/Components/ui';
import { route } from '@/lib/route';

export default function ForgotPassword() {
    const { flash } = usePage().props;
    const hp = useHoneypot();
    const form = useForm({ ...hp.fields, email: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('password.email'));
    };

    return (
        <>
            <Head title="Reset password" />
            <AuthHeading title="Forgot your password?" description="Enter your account email and we'll send you a secure link to choose a new one." />

            {flash?.status ? (
                <div className="mb-6 flex gap-3 rounded-xl bg-emerald-50 p-4 text-sm text-emerald-800 ring-1 ring-emerald-600/15">
                    <MailCheck className="size-5 shrink-0" />
                    {flash.status}
                </div>
            ) : null}

            <form onSubmit={submit} className="space-y-5" noValidate>
                <Honeypot form={form} />
                <FieldError>{form.errors.form}</FieldError>
                <FormField label="Email address" htmlFor="email" error={form.errors.email}>
                    <Input
                        id="email"
                        type="email"
                        autoComplete="email"
                        autoFocus
                        value={form.data.email}
                        onChange={(e) => form.setData('email', e.target.value)}
                        invalid={Boolean(form.errors.email)}
                    />
                </FormField>
                <Button type="submit" size="lg" icon={Send} loading={form.processing} className="w-full bg-indigo-600 text-white hover:bg-indigo-700">
                    Email me a reset link
                </Button>
            </form>

            <Link href={route('login')} className="mt-8 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
                <ArrowLeft className="size-4" /> Back to log in
            </Link>
        </>
    );
}
