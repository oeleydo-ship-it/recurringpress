import { Head, useForm, usePage } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { Button, Card, CardBody, FormField, Input } from '@/Components/ui';
import { route } from '@/lib/route';

export default function CompanyCreate() {
    const { auth } = usePage().props;
    const form = useForm({ name: '', email: auth?.user?.email ?? '', phone: '' });

    return (
        <div className="min-h-screen bg-slate-50 px-4 py-10 font-sans"><div className="mx-auto max-w-lg">
            <Head title="Create your company" />
            <span className="mx-auto flex size-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                <Building2 className="size-6" />
            </span>
            <h1 className="mt-5 text-center text-2xl font-semibold text-slate-900">Create your company</h1>
            <p className="mt-2 text-center text-sm text-slate-500">Invoices, clients and settings all belong to a company.</p>
            <Card className="mt-8">
                <CardBody>
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            form.post(route('company.store'));
                        }}
                        className="space-y-5"
                        noValidate
                    >
                        <FormField label="Company name" required error={form.errors.name}>
                            <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} invalid={Boolean(form.errors.name)} autoFocus />
                        </FormField>
                        <FormField label="Billing email" error={form.errors.email}>
                            <Input type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} />
                        </FormField>
                        <FormField label="Phone" error={form.errors.phone}>
                            <Input value={form.data.phone} onChange={(e) => form.setData('phone', e.target.value)} />
                        </FormField>
                        <Button type="submit" size="lg" loading={form.processing} className="w-full">
                            Create company
                        </Button>
                    </form>
                </CardBody>
            </Card>
        </div></div>
    );
}
