import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Select, Switch } from '@/Components/ui';
import { route } from '@/lib/route';

export default function SuperadminUserEdit({ user, roles, plans }) {
    const form = useForm({
        name: user.name,
        email: user.email,
        phone: user.phone ?? '',
        role: user.role,
        is_active: user.is_active,
        plan_id: user.company?.plan_id ?? '',
        subscription_status: user.company?.status ?? 'active',
    });
    const e = form.errors;

    return (
        <>
            <PageHeader title={`Edit ${user.name}`} back={route('superadmin.users.show', user.id)} />
            <form
                onSubmit={(ev) => {
                    ev.preventDefault();
                    form.put(route('superadmin.users.update', user.id));
                }}
                noValidate
                className="max-w-3xl space-y-6"
            >
                <Card>
                    <CardHeader title="Account" />
                    <CardBody className="grid gap-4 sm:grid-cols-2">
                        <FormField label="Name" required error={e.name}>
                            <Input value={form.data.name} onChange={(ev) => form.setData('name', ev.target.value)} invalid={Boolean(e.name)} />
                        </FormField>
                        <FormField label="Email" required error={e.email}>
                            <Input type="email" value={form.data.email} onChange={(ev) => form.setData('email', ev.target.value)} invalid={Boolean(e.email)} />
                        </FormField>
                        <FormField label="Phone" error={e.phone}>
                            <Input value={form.data.phone} onChange={(ev) => form.setData('phone', ev.target.value)} />
                        </FormField>
                        <FormField label="Role" error={e.role}>
                            <Select value={form.data.role} onChange={(ev) => form.setData('role', ev.target.value)} options={roles} />
                        </FormField>
                        <Switch className="sm:col-span-2" label="Active" description="Disabled users cannot sign in." checked={form.data.is_active} onChange={(v) => form.setData('is_active', v)} />
                    </CardBody>
                </Card>
                {user.company ? (
                    <Card>
                        <CardHeader title={`Subscription — ${user.company.name}`} description="Move the company to another plan (e.g. to comp a customer). No charge is made." />
                        <CardBody className="grid gap-4 sm:grid-cols-2">
                            <FormField label="Plan" error={e.plan_id}>
                                <Select value={form.data.plan_id ?? ''} onChange={(ev) => form.setData('plan_id', ev.target.value)} options={plans} placeholder="— unchanged —" />
                            </FormField>
                            <FormField label="Status" error={e.subscription_status}>
                                <Select
                                    value={form.data.subscription_status}
                                    onChange={(ev) => form.setData('subscription_status', ev.target.value)}
                                    options={{ active: 'Active', trial: 'Trial', cancelled: 'Cancelled', expired: 'Expired' }}
                                />
                            </FormField>
                        </CardBody>
                    </Card>
                ) : null}
                <Card>
                    <CardFooter className="rounded-xl border-t-0 bg-white">
                        <Button variant="secondary" href={route('superadmin.users.show', user.id)}>
                            Cancel
                        </Button>
                        <Button type="submit" icon={Save} loading={form.processing}>
                            Save
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </>
    );
}
