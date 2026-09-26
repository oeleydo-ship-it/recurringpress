import { useForm, usePage } from '@inertiajs/react';
import { FileUp, Paperclip, Save, X } from 'lucide-react';
import { Button, Card, CardBody, CardFooter, CardHeader, Checkbox, Combobox, FieldError, FormField, Input, PageHeader, Select, Textarea } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatMoney, today } from '@/lib/utils';

export default function ExpenseForm({ expense, categories, paymentMethods, invoices }) {
    const { auth } = usePage().props;
    const editing = Boolean(expense);
    const symbol = auth.company?.currency_symbol;
    const form = useForm({
        title: expense?.title ?? '',
        category: expense?.category ?? 'other',
        description: expense?.description ?? '',
        amount: expense?.amount ?? '',
        tax_amount: expense?.tax_amount || '',
        vendor: expense?.vendor ?? '',
        payment_method: expense?.payment_method ?? 'bank_transfer',
        reference: expense?.reference ?? '',
        expense_date: expense?.expense_date ?? today(),
        invoice_id: expense?.invoice_id ?? '',
        is_billable: expense?.is_billable ?? false,
        is_reimbursed: expense?.is_reimbursed ?? false,
        receipt: null,
        remove_receipt: false,
    });
    const d = form.data;
    const e = form.errors;

    const submit = (ev) => {
        ev.preventDefault();
        // File uploads need multipart; PUT is spoofed via _method for updates.
        if (editing) {
            form.transform((data) => ({ ...data, _method: 'put' }));
            form.post(route('expenses.update', expense.id), { forceFormData: true, preserveScroll: 'errors' });
        } else {
            form.post(route('expenses.store'), { forceFormData: true, preserveScroll: 'errors' });
        }
    };

    const total = (parseFloat(d.amount) || 0) + (parseFloat(d.tax_amount) || 0);

    return (
        <>
            <PageHeader title={editing ? `Edit ${expense.expense_number}` : 'New expense'} back={editing ? route('expenses.show', expense.id) : route('expenses.index')} />
            <form onSubmit={submit} noValidate className="grid max-w-5xl gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardBody className="grid gap-5 sm:grid-cols-2">
                        <FormField label="What was it for?" required error={e.title} className="sm:col-span-2">
                            <Input value={d.title} onChange={(ev) => form.setData('title', ev.target.value)} invalid={Boolean(e.title)} placeholder="e.g. Adobe Creative Cloud" />
                        </FormField>
                        <FormField label="Amount" required error={e.amount}>
                            <Input type="number" min="0.01" step="0.01" prefix={symbol} value={d.amount} onChange={(ev) => form.setData('amount', ev.target.value)} invalid={Boolean(e.amount)} />
                        </FormField>
                        <FormField label="Tax paid" error={e.tax_amount}>
                            <Input type="number" min="0" step="0.01" prefix={symbol} value={d.tax_amount} onChange={(ev) => form.setData('tax_amount', ev.target.value)} />
                        </FormField>
                        <FormField label="Date" required error={e.expense_date}>
                            <Input type="date" value={d.expense_date} onChange={(ev) => form.setData('expense_date', ev.target.value)} invalid={Boolean(e.expense_date)} />
                        </FormField>
                        <FormField label="Category" required error={e.category}>
                            <Select value={d.category} onChange={(ev) => form.setData('category', ev.target.value)} options={categories} />
                        </FormField>
                        <FormField label="Vendor" error={e.vendor}>
                            <Input value={d.vendor} onChange={(ev) => form.setData('vendor', ev.target.value)} />
                        </FormField>
                        <FormField label="Paid with" required error={e.payment_method}>
                            <Select value={d.payment_method} onChange={(ev) => form.setData('payment_method', ev.target.value)} options={paymentMethods} />
                        </FormField>
                        <FormField label="Reference" error={e.reference} hint="Receipt or order number">
                            <Input value={d.reference} onChange={(ev) => form.setData('reference', ev.target.value)} />
                        </FormField>
                        <FormField label="Related invoice" error={e.invoice_id}>
                            <Combobox value={d.invoice_id} onChange={(id) => form.setData('invoice_id', id)} options={[{ id: '', label: 'None' }, ...invoices]} placeholder="None" />
                        </FormField>
                        <FormField label="Notes" error={e.description} className="sm:col-span-2">
                            <Textarea rows={3} value={d.description} onChange={(ev) => form.setData('description', ev.target.value)} />
                        </FormField>
                        <div className="flex flex-wrap gap-6 sm:col-span-2">
                            <Checkbox label="Billable to a client" checked={d.is_billable} onChange={(ev) => form.setData('is_billable', ev.target.checked)} />
                            <Checkbox label="Reimbursed" checked={d.is_reimbursed} onChange={(ev) => form.setData('is_reimbursed', ev.target.checked)} />
                        </div>
                    </CardBody>
                </Card>

                <div className="space-y-6">
                    <Card>
                        <CardHeader title="Receipt" description="Stored privately — only your team can open it." />
                        <CardBody>
                            {editing && expense.has_receipt && !d.remove_receipt && !d.receipt ? (
                                <div className="flex items-center justify-between gap-2 rounded-lg bg-slate-50 px-3 py-2 text-sm">
                                    <a href={expense.receipt_url} target="_blank" rel="noreferrer" className="inline-flex items-center gap-2 text-brand-600 hover:text-brand-700">
                                        <Paperclip className="size-4" /> Current receipt
                                    </a>
                                    <button type="button" onClick={() => form.setData('remove_receipt', true)} className="text-slate-400 hover:text-red-600" aria-label="Remove receipt">
                                        <X className="size-4" />
                                    </button>
                                </div>
                            ) : (
                                <label className="flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 border-dashed border-slate-200 px-4 py-6 text-center transition hover:border-brand-400 hover:bg-brand-50/40">
                                    <FileUp className="size-6 text-slate-400" />
                                    <span className="text-sm font-medium text-slate-700">{d.receipt ? d.receipt.name : 'Upload receipt'}</span>
                                    <span className="text-xs text-slate-500">JPG, PNG, WebP or PDF · max 5 MB</span>
                                    <input type="file" accept=".jpg,.jpeg,.png,.webp,.pdf" className="sr-only" onChange={(ev) => form.setData('receipt', ev.target.files?.[0] ?? null)} />
                                </label>
                            )}
                            <FieldError>{e.receipt}</FieldError>
                        </CardBody>
                    </Card>
                    <Card>
                        <CardBody className="flex items-center justify-between">
                            <span className="text-sm text-slate-500">Total</span>
                            <span className="text-2xl font-semibold text-slate-900 tabular-nums">{formatMoney(total, auth.company?.currency)}</span>
                        </CardBody>
                        <CardFooter>
                            <Button variant="secondary" href={editing ? route('expenses.show', expense.id) : route('expenses.index')}>
                                Cancel
                            </Button>
                            <Button type="submit" icon={Save} loading={form.processing}>
                                {editing ? 'Save' : 'Add expense'}
                            </Button>
                        </CardFooter>
                    </Card>
                </div>
            </form>
        </>
    );
}
