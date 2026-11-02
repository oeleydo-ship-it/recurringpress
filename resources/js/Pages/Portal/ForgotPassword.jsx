import { Link, useForm, usePage } from '@inertiajs/react';
import { Send } from 'lucide-react';
import { PortalAuthCard } from '@/Components/portal/PortalShell';
import { Button, FormField, Input } from '@/Components/ui';

export default function PortalForgotPassword() {
    const { portal, flash } = usePage().props;
    const form = useForm({ email: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post(portal.urls.forgot, { preserveScroll: true });
    };

    return (
        <PortalAuthCard title="Reset your password" description="We'll email you a link to choose a new password.">
            {flash?.status ? <p className="mb-5 rounded-lg bg-emerald-50 p-3 text-sm text-emerald-800">{flash.status}</p> : null}
            <form onSubmit={submit} className="space-y-5" noValidate>
                <FormField label="Email address" htmlFor="email" error={form.errors.email}>
                    <Input id="email" type="email" autoComplete="username" autoFocus value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} invalid={Boolean(form.errors.email)} />
                </FormField>
                <Button type="submit" size="lg" icon={Send} loading={form.processing} className="w-full">
                    Email me a link
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
