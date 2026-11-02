import { useForm, usePage } from '@inertiajs/react';
import { Save } from 'lucide-react';
import ClientFields, { emptyClient } from '@/Components/ClientFields';
import ClientPortalCard, { ClientPortalNotice } from '@/Components/ClientPortalCard';
import { CustomFieldInputs, initialCustomValues } from '@/Components/CustomFields';
import { Button, Card, CardBody, CardFooter, PageHeader, Switch } from '@/Components/ui';
import { route } from '@/lib/route';

export default function ClientForm({ client, customFields = [], portal }) {
    const { auth } = usePage().props;
    const editing = Boolean(client);
    const form = useForm({
        ...(editing ? { ...emptyClient, ...nullsToEmpty(client) } : emptyClient),
        custom_values: initialCustomValues(customFields, client?.custom_values),
        ...(editing ? {} : { invite_to_portal: false }),
    });

    const submit = (e) => {
        e.preventDefault();
        if (editing) form.put(route('clients.update', client.id));
        else form.post(route('clients.store'));
    };

    return (
        <>
            <PageHeader title={editing ? `Edit ${client.company_name || client.name}` : 'New client'} back={editing ? route('clients.show', client.id) : route('clients.index')} />
            <form onSubmit={submit} noValidate className="max-w-3xl">
                <Card>
                    <CardBody className="space-y-6">
                        <ClientFields data={form.data} setData={form.setData} errors={form.errors} />
                        {customFields.length ? (
                            <div className="border-t border-slate-100 pt-6">
                                <h3 className="mb-4 text-sm font-semibold text-slate-900">Additional details</h3>
                                <CustomFieldInputs fields={customFields} values={form.data.custom_values} onChange={(v) => form.setData('custom_values', v)} errors={form.errors} />
                            </div>
                        ) : null}
                        {!editing && portal ? (
                            <div className="border-t border-slate-100 pt-6">
                                <h3 className="mb-4 text-sm font-semibold text-slate-900">Client portal</h3>
                                {portal.included && portal.open ? (
                                    auth.user.is_admin ? (
                                        <Switch
                                            label="Invite to the client portal"
                                            description="Emails them a link to set a password, so they can sign in and see their invoices and quotations."
                                            checked={form.data.invite_to_portal}
                                            onChange={(v) => form.setData('invite_to_portal', v)}
                                        />
                                    ) : (
                                        <p className="text-sm text-slate-600">An owner or admin can invite this client from their page.</p>
                                    )
                                ) : (
                                    <ClientPortalNotice portal={portal} />
                                )}
                            </div>
                        ) : null}
                        {editing ? (
                            <div className="border-t border-slate-100 pt-6">
                                <Switch
                                    label="Active"
                                    description="Inactive clients are hidden from invoice client pickers."
                                    checked={form.data.is_active}
                                    onChange={(v) => form.setData('is_active', v)}
                                />
                            </div>
                        ) : null}
                    </CardBody>
                    <CardFooter>
                        <Button variant="secondary" href={editing ? route('clients.show', client.id) : route('clients.index')}>
                            Cancel
                        </Button>
                        <Button type="submit" icon={Save} loading={form.processing}>
                            {editing ? 'Save changes' : 'Create client'}
                        </Button>
                    </CardFooter>
                </Card>
            </form>
            {editing ? (
                <div className="mt-6 max-w-3xl">
                    <ClientPortalCard client={client} portal={portal} />
                </div>
            ) : null}
        </>
    );
}

function nullsToEmpty(obj) {
    return Object.fromEntries(Object.entries(obj).map(([k, v]) => [k, v === null ? '' : v]));
}
