import { useForm, usePage } from '@inertiajs/react';
import { Save, Send, UserPlus } from 'lucide-react';
import { useMemo, useState } from 'react';
import LineItemsEditor from '@/Components/documents/LineItemsEditor';
import QuickAddClient from '@/Components/documents/QuickAddClient';
import TotalsPanel from '@/Components/documents/TotalsPanel';
import { blankItem, cleanItems, computeTotals, withKeys } from '@/Components/documents/totals';
import { Button, Card, CardBody, CardHeader, Combobox, FormField, Input, PageHeader, Textarea } from '@/Components/ui';
import { route } from '@/lib/route';
import { today } from '@/lib/utils';

export default function InvoiceForm({ invoice, nextNumber, selectedClientId, clients: initialClients, products, units, defaults }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const symbol = auth.company?.currency_symbol;
    const editing = Boolean(invoice);
    const [clients, setClients] = useState(initialClients);
    const [quickAdd, setQuickAdd] = useState({ open: false, name: '' });

    const form = useForm({
        invoice_number: invoice?.invoice_number ?? nextNumber,
        client_id: invoice?.client_id ?? selectedClientId ?? '',
        reference: invoice?.reference ?? '',
        issue_date: invoice?.issue_date ?? today(),
        due_date: invoice?.due_date ?? today(defaults.payment_terms),
        notes: invoice?.notes ?? defaults.notes ?? '',
        terms: invoice?.terms ?? defaults.terms ?? '',
        discount_amount: invoice?.discount_amount || '',
        discount_type: invoice?.discount_type ?? 'fixed',
        items: invoice?.items?.length ? withKeys(invoice.items) : [blankItem(defaults)],
        send_now: false,
    });
    const d = form.data;

    const totals = useMemo(() => computeTotals(d.items, d.discount_amount, d.discount_type), [d.items, d.discount_amount, d.discount_type]);
    const clientOptions = clients.map((c) => ({ id: c.id, label: c.label, sublabel: c.email }));

    const setIssueDate = (value) => {
        // Keep the same payment terms when the issue date moves.
        const shift = Math.round((new Date(d.due_date) - new Date(d.issue_date)) / 86400000);
        const due = new Date(value);
        if (!Number.isNaN(due.getTime()) && Number.isFinite(shift)) {
            due.setDate(due.getDate() + shift);
            form.setData((prev) => ({ ...prev, issue_date: value, due_date: due.toISOString().slice(0, 10) }));
        } else {
            form.setData('issue_date', value);
        }
    };

    const submit = (sendNow) => (e) => {
        e?.preventDefault();
        form.transform((data) => ({ ...data, send_now: sendNow, items: cleanItems(data.items) }));
        if (editing) form.put(route('invoices.update', invoice.id), { preserveScroll: 'errors' });
        else form.post(route('invoices.store'), { preserveScroll: 'errors' });
    };

    const selectedClient = clients.find((c) => String(c.id) === String(d.client_id));

    return (
        <>
            <PageHeader
                title={editing ? `Edit ${invoice.invoice_number}` : 'New invoice'}
                back={editing ? route('invoices.show', invoice.id) : route('invoices.index')}
                actions={
                    <>
                        <Button variant="secondary" icon={Save} onClick={submit(false)} loading={form.processing && !d.send_now}>
                            {editing ? 'Save' : 'Save draft'}
                        </Button>
                        <Button icon={Send} onClick={submit(true)} disabled={form.processing || !selectedClient?.email} title={!selectedClient?.email ? 'The client needs an email address' : undefined}>
                            Save & send
                        </Button>
                    </>
                }
            />

            <form onSubmit={submit(false)} noValidate className="grid gap-6 2xl:grid-cols-[minmax(0,1fr)_340px]">
                <div className="min-w-0 space-y-6">
                    <Card>
                        <CardBody className="grid gap-5 md:grid-cols-2">
                            <FormField label="Bill to" required error={form.errors.client_id} className="md:col-span-2">
                                <div className="flex gap-2">
                                    <Combobox
                                        value={d.client_id}
                                        onChange={(id) => form.setData('client_id', id)}
                                        options={clientOptions}
                                        placeholder="Search clients…"
                                        invalid={Boolean(form.errors.client_id)}
                                        onCreate={(q) => setQuickAdd({ open: true, name: q })}
                                        createLabel="New client"
                                        className="flex-1"
                                    />
                                    <Button variant="secondary" icon={UserPlus} onClick={() => setQuickAdd({ open: true, name: '' })} className="shrink-0">
                                        <span className="hidden sm:inline">New</span>
                                    </Button>
                                </div>
                            </FormField>
                            <FormField label="Invoice number" required error={form.errors.invoice_number}>
                                <Input value={d.invoice_number} onChange={(e) => form.setData('invoice_number', e.target.value)} disabled={editing} className="font-mono" invalid={Boolean(form.errors.invoice_number)} />
                            </FormField>
                            <FormField label="Reference / PO number" error={form.errors.reference}>
                                <Input value={d.reference} onChange={(e) => form.setData('reference', e.target.value)} placeholder="Optional" />
                            </FormField>
                            <FormField label="Issue date" required error={form.errors.issue_date}>
                                <Input type="date" value={d.issue_date} onChange={(e) => setIssueDate(e.target.value)} invalid={Boolean(form.errors.issue_date)} />
                            </FormField>
                            <FormField label="Due date" required error={form.errors.due_date}>
                                <Input type="date" value={d.due_date} min={d.issue_date} onChange={(e) => form.setData('due_date', e.target.value)} invalid={Boolean(form.errors.due_date)} />
                            </FormField>
                        </CardBody>
                    </Card>

                    <Card>
                        <CardHeader title="Line items" />
                        <CardBody className="pt-1">
                            <LineItemsEditor
                                items={d.items}
                                onChange={(items) => form.setData('items', items)}
                                products={products}
                                units={units}
                                currency={currency}
                                symbol={symbol}
                                errors={form.errors}
                                defaults={defaults}
                            />
                        </CardBody>
                    </Card>

                    <Card>
                        <CardBody className="grid gap-5 md:grid-cols-2">
                            <FormField label="Notes to client" error={form.errors.notes} hint="Shown on the invoice.">
                                <Textarea rows={4} value={d.notes} onChange={(e) => form.setData('notes', e.target.value)} placeholder="Thanks for your business!" />
                            </FormField>
                            <FormField label="Terms & conditions" error={form.errors.terms}>
                                <Textarea rows={4} value={d.terms} onChange={(e) => form.setData('terms', e.target.value)} placeholder="Payment due within 30 days." />
                            </FormField>
                        </CardBody>
                    </Card>
                </div>

                <div className="space-y-6 md:ml-auto md:w-96 2xl:ml-0 2xl:w-auto">
                    <Card className="2xl:sticky 2xl:top-24">
                        <CardHeader title="Summary" />
                        <CardBody>
                            <TotalsPanel
                                totals={totals}
                                currency={currency}
                                symbol={symbol}
                                discountAmount={d.discount_amount}
                                discountType={d.discount_type}
                                onDiscountAmount={(v) => form.setData('discount_amount', v)}
                                onDiscountType={(v) => form.setData('discount_type', v)}
                                error={form.errors.discount_amount}
                            />
                            <div className="mt-6 grid gap-2">
                                <Button icon={Send} onClick={submit(true)} disabled={form.processing || !selectedClient?.email} className="w-full">
                                    Save & send to client
                                </Button>
                                <Button variant="secondary" icon={Save} onClick={submit(false)} loading={form.processing} className="w-full">
                                    {editing ? 'Save changes' : 'Save as draft'}
                                </Button>
                                {selectedClient && !selectedClient.email ? <p className="text-center text-xs text-amber-700">Add an email to this client to send invoices.</p> : null}
                            </div>
                        </CardBody>
                    </Card>
                </div>
            </form>

            <QuickAddClient
                open={quickAdd.open}
                initialName={quickAdd.name}
                onClose={() => setQuickAdd({ open: false, name: '' })}
                onCreated={(c) => {
                    setClients((list) => [...list, c].sort((a, b) => a.label.localeCompare(b.label)));
                    form.setData('client_id', c.id);
                }}
            />
        </>
    );
}
