import { useForm, usePage } from '@inertiajs/react';
import { Save, Send, UserPlus } from 'lucide-react';
import { useMemo, useState } from 'react';
import LineItemsEditor from '@/Components/documents/LineItemsEditor';
import QuickAddClient from '@/Components/documents/QuickAddClient';
import TotalsPanel from '@/Components/documents/TotalsPanel';
import { blankItem, cleanItems, computeTotals, withKeys } from '@/Components/documents/totals';
import { Button, Card, CardBody, CardHeader, Combobox, FormField, Input, PageHeader, Textarea } from '@/Components/ui';

/**
 * Shared editor for invoices, quotations and recurring invoices: client picker with quick
 * add, header fields, line items, notes/terms and a live totals panel.
 *
 * - `initialData`: form fields (without items/discount/notes, which are handled here)
 * - `renderHeader({ form, setIssueDate })`: document-specific header fields
 * - `onSubmit(form, { sendNow })`: performs the Inertia request
 */
export default function DocumentEditor({
    title,
    back,
    doc,
    initialData,
    clients: initialClients,
    selectedClientId,
    products,
    units,
    defaults,
    renderHeader,
    onSubmit,
    sendable = true,
    sendLabel = 'Save & send',
    saveLabel,
}) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const symbol = auth.company?.currency_symbol;
    const [clients, setClients] = useState(initialClients);
    const [quickAdd, setQuickAdd] = useState({ open: false, name: '' });

    const form = useForm({
        client_id: doc?.client_id ?? selectedClientId ?? '',
        notes: doc?.notes ?? defaults.notes ?? '',
        terms: doc?.terms ?? defaults.terms ?? '',
        discount_amount: doc?.discount_amount || '',
        discount_type: doc?.discount_type ?? 'fixed',
        items: doc?.items?.length ? withKeys(doc.items) : [blankItem(defaults)],
        ...initialData,
    });
    const d = form.data;

    const totals = useMemo(() => computeTotals(d.items, d.discount_amount, d.discount_type), [d.items, d.discount_amount, d.discount_type]);
    const clientOptions = clients.map((c) => ({ id: c.id, label: c.label, sublabel: c.email }));
    const selectedClient = clients.find((c) => String(c.id) === String(d.client_id));
    const canSend = sendable && Boolean(selectedClient?.email);

    const submit = (sendNow) => (e) => {
        e?.preventDefault();
        form.transform((data) => ({ ...data, send_now: sendNow, items: cleanItems(data.items) }));
        onSubmit(form, { sendNow });
    };

    const primaryActions = (full) => (
        <>
            {sendable ? (
                <Button icon={Send} onClick={submit(true)} disabled={form.processing || !canSend} title={!canSend ? 'The client needs an email address' : undefined} className={full ? 'w-full' : ''}>
                    {sendLabel}
                </Button>
            ) : null}
            <Button variant={sendable ? 'secondary' : 'primary'} icon={Save} onClick={submit(false)} loading={form.processing} className={full ? 'w-full' : ''}>
                {saveLabel ?? (doc ? 'Save changes' : 'Save as draft')}
            </Button>
        </>
    );

    return (
        <>
            <PageHeader title={title} back={back} actions={primaryActions(false)} />

            <form onSubmit={submit(false)} noValidate className="grid gap-6 2xl:grid-cols-[minmax(0,1fr)_340px]">
                <div className="min-w-0 space-y-6">
                    <Card>
                        <CardBody className="grid gap-5 md:grid-cols-2">
                            <FormField label="Client" required error={form.errors.client_id} className="md:col-span-2">
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
                            {renderHeader({ form })}
                        </CardBody>
                    </Card>

                    <Card>
                        <CardHeader title="Line items" />
                        <CardBody className="pt-1">
                            <LineItemsEditor items={d.items} onChange={(items) => form.setData('items', items)} products={products} units={units} currency={currency} symbol={symbol} errors={form.errors} defaults={defaults} />
                        </CardBody>
                    </Card>

                    <Card>
                        <CardBody className="grid gap-5 md:grid-cols-2">
                            <FormField label="Notes to client" error={form.errors.notes} hint="Printed on the document.">
                                <Textarea rows={4} value={d.notes} onChange={(e) => form.setData('notes', e.target.value)} placeholder="Thanks for your business!" />
                            </FormField>
                            <FormField label="Terms & conditions" error={form.errors.terms}>
                                <Textarea rows={4} value={d.terms} onChange={(e) => form.setData('terms', e.target.value)} />
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
                            <div className="mt-6 grid gap-2">{primaryActions(true)}</div>
                            {sendable && selectedClient && !selectedClient.email ? <p className="mt-2 text-center text-xs text-amber-700">Add an email to this client to send it.</p> : null}
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

/** Keep the gap between two dates when the first one moves (e.g. issue → due). */
export function shiftPair(form, fromKey, toKey, value) {
    const d = form.data;
    const gap = Math.round((new Date(d[toKey]) - new Date(d[fromKey])) / 86400000);
    const next = new Date(value);
    if (!Number.isNaN(next.getTime()) && Number.isFinite(gap)) {
        next.setDate(next.getDate() + gap);
        form.setData((prev) => ({ ...prev, [fromKey]: value, [toKey]: next.toISOString().slice(0, 10) }));
    } else {
        form.setData(fromKey, value);
    }
}

export function DateField({ form, name, label, min, onChange, required = true }) {
    return (
        <FormField label={label} required={required} error={form.errors[name]}>
            <Input type="date" value={form.data[name] ?? ''} min={min} onChange={(e) => (onChange ? onChange(e.target.value) : form.setData(name, e.target.value))} invalid={Boolean(form.errors[name])} />
        </FormField>
    );
}
