import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { Badge, Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Segmented, Switch } from '@/Components/ui';
import { route } from '@/lib/route';

export default function StripeSettings({ settings, saved, webhookUrl }) {
    const form = useForm({
        stripe_enabled: settings.stripe_enabled,
        stripe_mode: settings.stripe_mode,
        stripe_test_publishable_key: settings.stripe_test_publishable_key ?? '',
        stripe_test_secret_key: '',
        stripe_live_publishable_key: settings.stripe_live_publishable_key ?? '',
        stripe_live_secret_key: '',
        stripe_webhook_secret: '',
    });
    const d = form.data;
    const e = form.errors;
    const secret = (name, label, placeholder) => (
        <FormField label={label} error={e[name]} hint={saved[name] ? 'Saved — leave blank to keep it.' : undefined}>
            <Input type="password" autoComplete="off" value={d[name]} onChange={(ev) => form.setData(name, ev.target.value)} placeholder={saved[name] ? '•••••••••••• (saved)' : placeholder} invalid={Boolean(e[name])} />
        </FormField>
    );

    return (
        <>
            <PageHeader title="Platform Stripe account" description="Used for subscriptions and for tenants who collect card payments through the platform." />
            <form
                onSubmit={(ev) => {
                    ev.preventDefault();
                    form.put(route('superadmin.settings.stripe.update'), { preserveScroll: true, onSuccess: () => form.setData((x) => ({ ...x, stripe_test_secret_key: '', stripe_live_secret_key: '', stripe_webhook_secret: '' })) });
                }}
                noValidate
                className="max-w-3xl space-y-6"
            >
                <Card>
                    <CardBody className="space-y-5">
                        <Switch label="Enable Stripe" checked={d.stripe_enabled} onChange={(v) => form.setData('stripe_enabled', v)} />
                        <div className="flex items-center gap-3">
                            <Segmented
                                value={d.stripe_mode}
                                onChange={(v) => form.setData('stripe_mode', v)}
                                options={[
                                    { value: 'test', label: 'Test mode' },
                                    { value: 'live', label: 'Live mode' },
                                ]}
                            />
                            {d.stripe_mode === 'live' ? <Badge tone="red">Real payments</Badge> : <Badge tone="yellow">Test payments only</Badge>}
                        </div>
                    </CardBody>
                </Card>
                <Card>
                    <CardHeader title="Test keys" />
                    <CardBody className="grid gap-4 sm:grid-cols-2">
                        <FormField label="Publishable key" error={e.stripe_test_publishable_key}>
                            <Input value={d.stripe_test_publishable_key} onChange={(ev) => form.setData('stripe_test_publishable_key', ev.target.value)} placeholder="pk_test_…" className="font-mono text-xs" />
                        </FormField>
                        {secret('stripe_test_secret_key', 'Secret key', 'sk_test_…')}
                    </CardBody>
                </Card>
                <Card>
                    <CardHeader title="Live keys" />
                    <CardBody className="grid gap-4 sm:grid-cols-2">
                        <FormField label="Publishable key" error={e.stripe_live_publishable_key}>
                            <Input value={d.stripe_live_publishable_key} onChange={(ev) => form.setData('stripe_live_publishable_key', ev.target.value)} placeholder="pk_live_…" className="font-mono text-xs" />
                        </FormField>
                        {secret('stripe_live_secret_key', 'Secret key', 'sk_live_…')}
                    </CardBody>
                </Card>
                <Card>
                    <CardHeader title="Webhook" description="Records card payments even if the client closes the page before returning." />
                    <CardBody className="space-y-4">
                        <div className="rounded-lg bg-slate-50 p-3 text-sm text-slate-600">
                            Endpoint: <code className="rounded bg-white px-1.5 py-0.5 font-mono text-xs">{webhookUrl}</code>
                            <br />
                            Event: <code className="rounded bg-white px-1.5 py-0.5 font-mono text-xs">payment_intent.succeeded</code>
                        </div>
                        {secret('stripe_webhook_secret', 'Signing secret', 'whsec_…')}
                    </CardBody>
                    <CardFooter>
                        <Button type="submit" icon={Save} loading={form.processing}>
                            Save Stripe settings
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </>
    );
}
