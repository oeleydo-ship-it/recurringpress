import DocumentEditor, { DateField, shiftPair } from '@/Components/documents/DocumentEditor';
import { FormField, Input } from '@/Components/ui';
import { route } from '@/lib/route';
import { today } from '@/lib/utils';

export default function QuotationForm({ quotation, nextNumber, selectedClientId, clients, products, units, defaults, customFields, clientCustomFields }) {
    const editing = Boolean(quotation);

    return (
        <DocumentEditor
            customFields={customFields}
            clientCustomFields={clientCustomFields}
            title={editing ? `Edit ${quotation.quotation_number}` : 'New quotation'}
            back={editing ? route('quotations.show', quotation.id) : route('quotations.index')}
            doc={quotation}
            clients={clients}
            selectedClientId={selectedClientId}
            products={products}
            units={units}
            defaults={defaults}
            initialData={{
                quotation_number: quotation?.quotation_number ?? nextNumber,
                reference: quotation?.reference ?? '',
                issue_date: quotation?.issue_date ?? today(),
                valid_until: quotation?.valid_until ?? today(defaults.validity_days),
            }}
            onSubmit={(form) => {
                if (editing) form.put(route('quotations.update', quotation.id), { preserveScroll: 'errors' });
                else form.post(route('quotations.store'), { preserveScroll: 'errors' });
            }}
            renderHeader={({ form }) => (
                <>
                    <FormField label="Quotation number" required error={form.errors.quotation_number}>
                        <Input
                            value={form.data.quotation_number}
                            onChange={(e) => form.setData('quotation_number', e.target.value)}
                            disabled={editing}
                            className="font-mono"
                            invalid={Boolean(form.errors.quotation_number)}
                        />
                    </FormField>
                    <FormField label="Reference" error={form.errors.reference}>
                        <Input value={form.data.reference} onChange={(e) => form.setData('reference', e.target.value)} placeholder="Optional" />
                    </FormField>
                    <DateField form={form} name="issue_date" label="Issue date" onChange={(v) => shiftPair(form, 'issue_date', 'valid_until', v)} />
                    <DateField form={form} name="valid_until" label="Valid until" min={form.data.issue_date} />
                </>
            )}
        />
    );
}
