import DocumentEditor, { DateField } from '@/Components/documents/DocumentEditor';
import { FormField, Input, Select, Switch } from '@/Components/ui';
import { route } from '@/lib/route';
import { today } from '@/lib/utils';

export default function RecurringForm({ recurring, selectedClientId, clients, products, units, billingCycles, defaults }) {
    const editing = Boolean(recurring);

    return (
        <DocumentEditor
            title={editing ? `Edit ${recurring.name}` : 'New recurring invoice'}
            back={editing ? route('recurring-invoices.show', recurring.id) : route('recurring-invoices.index')}
            doc={recurring}
            clients={clients}
            selectedClientId={selectedClientId}
            products={products}
            units={units}
            defaults={defaults}
            sendable={false}
            saveLabel={editing ? 'Save changes' : 'Create schedule'}
            initialData={{
                name: recurring?.name ?? '',
                billing_cycle: recurring?.billing_cycle ?? 'monthly',
                billing_cycle_count: recurring?.billing_cycle_count ?? '',
                start_date: recurring?.start_date ?? today(),
                end_date: recurring?.end_date ?? '',
                auto_send: recurring?.auto_send ?? true,
            }}
            onSubmit={(form) => {
                if (editing) form.put(route('recurring-invoices.update', recurring.id), { preserveScroll: 'errors' });
                else form.post(route('recurring-invoices.store'), { preserveScroll: 'errors' });
            }}
            renderHeader={({ form }) => (
                <>
                    <FormField label="Schedule name" required error={form.errors.name} className="md:col-span-2" hint="e.g. “Monthly retainer” — shown as the invoice reference.">
                        <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} invalid={Boolean(form.errors.name)} />
                    </FormField>
                    <FormField label="Bill every" required error={form.errors.billing_cycle}>
                        <Select value={form.data.billing_cycle} onChange={(e) => form.setData('billing_cycle', e.target.value)} options={billingCycles} />
                    </FormField>
                    <FormField label="Number of invoices" error={form.errors.billing_cycle_count} hint="Blank = until cancelled.">
                        <Input type="number" min="1" value={form.data.billing_cycle_count ?? ''} onChange={(e) => form.setData('billing_cycle_count', e.target.value)} />
                    </FormField>
                    {editing ? (
                        <FormField label="Started">
                            <Input type="date" value={form.data.start_date} disabled />
                        </FormField>
                    ) : (
                        <DateField form={form} name="start_date" label="First invoice on" min={today()} />
                    )}
                    <DateField form={form} name="end_date" label="End date" required={false} min={form.data.start_date} />
                    <div className="md:col-span-2">
                        <Switch
                            label="Email each invoice automatically"
                            description="Generated invoices are emailed to the client (via the queue) instead of waiting as drafts."
                            checked={form.data.auto_send}
                            onChange={(v) => form.setData('auto_send', v)}
                        />
                    </div>
                </>
            )}
        />
    );
}
