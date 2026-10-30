import DocumentEditor, { DateField, shiftPair } from '@/Components/documents/DocumentEditor';
import { FormField, Input } from '@/Components/ui';
import { route } from '@/lib/route';
import { today } from '@/lib/utils';

export default function InvoiceForm({ invoice, nextNumber, selectedClientId, clients, products, units, defaults, customFields, clientCustomFields }) {
    const editing = Boolean(invoice);

    return (
        <DocumentEditor
            customFields={customFields}
            clientCustomFields={clientCustomFields}
            title={editing ? `Edit ${invoice.invoice_number}` : 'New invoice'}
            back={editing ? route('invoices.show', invoice.id) : route('invoices.index')}
            doc={invoice}
            clients={clients}
            selectedClientId={selectedClientId}
            products={products}
            units={units}
            defaults={defaults}
            sendLabel="Save & send"
            initialData={{
                invoice_number: invoice?.invoice_number ?? nextNumber,
                reference: invoice?.reference ?? '',
                issue_date: invoice?.issue_date ?? today(),
                due_date: invoice?.due_date ?? today(defaults.payment_terms),
            }}
            onSubmit={(form) => {
                if (editing) form.put(route('invoices.update', invoice.id), { preserveScroll: 'errors' });
                else form.post(route('invoices.store'), { preserveScroll: 'errors' });
            }}
            renderHeader={({ form }) => (
                <>
                    <FormField label="Invoice number" required error={form.errors.invoice_number}>
                        <Input value={form.data.invoice_number} onChange={(e) => form.setData('invoice_number', e.target.value)} disabled={editing} className="font-mono" invalid={Boolean(form.errors.invoice_number)} />
                    </FormField>
                    <FormField label="Reference / PO number" error={form.errors.reference}>
                        <Input value={form.data.reference} onChange={(e) => form.setData('reference', e.target.value)} placeholder="Optional" />
                    </FormField>
                    <DateField form={form} name="issue_date" label="Issue date" onChange={(v) => shiftPair(form, 'issue_date', 'due_date', v)} />
                    <DateField form={form} name="due_date" label="Due date" min={form.data.issue_date} />
                </>
            )}
        />
    );
}
