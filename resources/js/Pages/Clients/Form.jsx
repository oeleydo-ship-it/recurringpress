import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import ClientFields, { emptyClient } from '@/Components/ClientFields';
import { Button, Card, CardBody, CardFooter, PageHeader, Switch } from '@/Components/ui';
import { route } from '@/lib/route';

export default function ClientForm({ client }) {
    const editing = Boolean(client);
    const form = useForm(editing ? { ...emptyClient, ...nullsToEmpty(client) } : { ...emptyClient });

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
        </>
    );
}

function nullsToEmpty(obj) {
    return Object.fromEntries(Object.entries(obj).map(([k, v]) => [k, v === null ? '' : v]));
}
