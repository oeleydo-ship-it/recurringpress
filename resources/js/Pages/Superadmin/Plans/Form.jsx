import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Select, Switch, Textarea } from '@/Components/ui';
import { route } from '@/lib/route';

export default function PlanForm({ plan }) {
    const editing = Boolean(plan);
    const form = useForm({
        name: plan?.name ?? '',
        slug: plan?.slug ?? '',
        description: plan?.description ?? '',
        price: plan?.price ?? 0,
        billing_cycle: plan?.billing_cycle ?? 'monthly',
        trial_days: plan?.trial_days ?? 14,
        max_invoices: plan?.max_invoices ?? '',
        max_clients: plan?.max_clients ?? '',
        max_users: plan?.max_users ?? '',
        max_products: plan?.max_products ?? '',
        features: (plan?.features ?? []).join('\n'),
        stripe_enabled: plan?.stripe_enabled ?? true,
        custom_branding: plan?.custom_branding ?? false,
        white_label: plan?.white_label ?? false,
        custom_domain: plan?.custom_domain ?? false,
        subdomain: plan?.subdomain ?? false,
        api_access: plan?.api_access ?? false,
        is_active: plan?.is_active ?? true,
        is_popular: plan?.is_popular ?? false,
        order: plan?.order ?? 0,
    });
    const d = form.data;
    const e = form.errors;
    const num = (name, label, hint) => (
        <FormField label={label} error={e[name]} hint={hint}>
            <Input type="number" min="0" value={d[name] ?? ''} onChange={(ev) => form.setData(name, ev.target.value)} placeholder="Unlimited" />
        </FormField>
    );

    const submit = (ev) => {
        ev.preventDefault();
        form.transform((data) => ({
            ...data,
            features: data.features.split('\n').map((f) => f.trim()).filter(Boolean),
            ...Object.fromEntries(['max_invoices', 'max_clients', 'max_users', 'max_products'].map((k) => [k, data[k] === '' ? null : data[k]])),
        }));
        if (editing) form.put(route('superadmin.plans.update', plan.id));
        else form.post(route('superadmin.plans.store'));
    };

    return (
        <>
            <PageHeader title={editing ? `Edit ${plan.name}` : 'New plan'} back={route('superadmin.plans.index')} />
            <form onSubmit={submit} noValidate className="grid max-w-5xl gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader title="Plan" />
                        <CardBody className="grid gap-4 sm:grid-cols-2">
                            <FormField label="Name" required error={e.name}>
                                <Input value={d.name} onChange={(ev) => form.setData('name', ev.target.value)} invalid={Boolean(e.name)} />
                            </FormField>
                            <FormField label="Slug" error={e.slug} hint="Used in /register?plan=… — leave blank to generate.">
                                <Input value={d.slug} onChange={(ev) => form.setData('slug', ev.target.value)} className="font-mono" />
                            </FormField>
                            <FormField label="Price (USD)" required error={e.price}>
                                <Input type="number" min="0" step="0.01" prefix="$" value={d.price} onChange={(ev) => form.setData('price', ev.target.value)} invalid={Boolean(e.price)} />
                            </FormField>
                            <FormField label="Billing cycle" error={e.billing_cycle}>
                                <Select value={d.billing_cycle} onChange={(ev) => form.setData('billing_cycle', ev.target.value)} options={{ monthly: 'Monthly', yearly: 'Yearly' }} />
                            </FormField>
                            <FormField label="Trial days" error={e.trial_days} hint="0 disables trials for paid plans. Free plans stay active without a trial.">
                                <Input type="number" min="0" max="365" value={d.trial_days} onChange={(ev) => form.setData('trial_days', ev.target.value)} />
                            </FormField>
                            <FormField label="Description" error={e.description} className="sm:col-span-2">
                                <Textarea rows={2} value={d.description} onChange={(ev) => form.setData('description', ev.target.value)} />
                            </FormField>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardHeader title="Limits" description="Lifetime resource quotas. Leave blank for unlimited; use 0 to disable creation." />
                        <CardBody className="grid gap-4 sm:grid-cols-2">
                            {num('max_invoices', 'Invoices')}
                            {num('max_clients', 'Clients')}
                            {num('max_users', 'Team members')}
                            {num('max_products', 'Products')}
                        </CardBody>
                    </Card>
                    <Card>
                        <CardHeader title="Marketing bullet points" description="One per line, shown on pricing pages." />
                        <CardBody>
                            <Textarea rows={5} value={d.features} onChange={(ev) => form.setData('features', ev.target.value)} placeholder={'Priority support\nRemove branding'} />
                        </CardBody>
                    </Card>
                </div>
                <div className="space-y-6">
                    <Card>
                        <CardBody className="space-y-5">
                            <Switch label="Visible to customers" checked={d.is_active} onChange={(v) => form.setData('is_active', v)} />
                            <Switch label="Highlight as popular" checked={d.is_popular} onChange={(v) => form.setData('is_popular', v)} />
                            <Switch label="Online card payments" checked={d.stripe_enabled} onChange={(v) => form.setData('stripe_enabled', v)} />
                            <Switch label="Custom branding" checked={d.custom_branding} onChange={(v) => form.setData('custom_branding', v)} />
                            <Switch label="White label (remove platform attribution)" checked={d.white_label} onChange={(v) => form.setData('white_label', v)} />
                            <Switch label="Custom domain" checked={d.custom_domain} onChange={(v) => form.setData('custom_domain', v)} />
                            <Switch label="Platform subdomain" checked={d.subdomain} onChange={(v) => form.setData('subdomain', v)} />
                            <Switch label="API access" checked={d.api_access} onChange={(v) => form.setData('api_access', v)} />
                            <FormField label="Sort order" error={e.order}>
                                <Input type="number" min="0" value={d.order} onChange={(ev) => form.setData('order', ev.target.value)} />
                            </FormField>
                        </CardBody>
                        <CardFooter>
                            <Button variant="secondary" href={route('superadmin.plans.index')}>
                                Cancel
                            </Button>
                            <Button type="submit" icon={Save} loading={form.processing}>
                                {editing ? 'Save plan' : 'Create plan'}
                            </Button>
                        </CardFooter>
                    </Card>
                </div>
            </form>
        </>
    );
}
