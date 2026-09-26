import { Link, useForm, usePage } from '@inertiajs/react';
import { Download, FileText, Receipt, RotateCcw } from 'lucide-react';
import { useState } from 'react';
import { Button, Card, CardHeader, DescriptionItem, FormField, Input, Modal, PageHeader, StatusBadge, Textarea } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDateTime, formatMoney, titleCase } from '@/lib/utils';

export default function PaymentShow({ payment: p }) {
    const { auth } = usePage().props;
    const currency = p.currency || auth.company?.currency;
    const [refundOpen, setRefundOpen] = useState(false);
    const money = (v) => formatMoney(v, currency);

    return (
        <>
            <PageHeader
                title={p.payment_number}
                back={route('payments.index')}
                meta={<StatusBadge status={p.status} label={titleCase(p.status)} />}
                description={`${p.payment_method_label} · ${formatDateTime(p.payment_date)}`}
                actions={
                    <>
                        {p.receipt_id ? (
                            <Button href={route('payments.receipt', p.id)} external variant="secondary" icon={Download}>
                                Receipt PDF
                            </Button>
                        ) : null}
                        {p.can_refund ? (
                            <Button variant="danger-soft" icon={RotateCcw} onClick={() => setRefundOpen(true)}>
                                Record refund
                            </Button>
                        ) : null}
                    </>
                }
            />

            <div className="grid gap-6 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <div className="flex flex-wrap items-end justify-between gap-4 border-b border-slate-100 p-6">
                        <div>
                            <p className="text-sm text-slate-500">Amount received</p>
                            <p className="mt-1 text-4xl font-semibold tracking-tight text-slate-900 tabular-nums">{money(p.amount)}</p>
                        </div>
                        {p.refunded_amount > 0 ? (
                            <div className="text-right">
                                <p className="text-sm text-slate-500">Refunded</p>
                                <p className="text-xl font-semibold text-violet-600 tabular-nums">−{money(p.refunded_amount)}</p>
                            </div>
                        ) : null}
                    </div>
                    <dl className="grid gap-6 p-6 sm:grid-cols-2">
                        <DescriptionItem label="Client">
                            {p.client_id ? (
                                <Link href={route('clients.show', p.client_id)} className="text-brand-600 hover:text-brand-700">
                                    {p.client_name}
                                </Link>
                            ) : null}
                        </DescriptionItem>
                        <DescriptionItem label="Invoice">
                            {p.invoice_id ? (
                                <Link href={route('invoices.show', p.invoice_id)} className="inline-flex items-center gap-1.5 text-brand-600 hover:text-brand-700">
                                    <FileText className="size-4" /> {p.invoice_number}
                                </Link>
                            ) : null}
                        </DescriptionItem>
                        <DescriptionItem label="Method">{p.payment_method_label}</DescriptionItem>
                        <DescriptionItem label="Transaction ID">{p.transaction_id ? <span className="font-mono text-xs break-all">{p.transaction_id}</span> : null}</DescriptionItem>
                        {p.via_app_gateway ? <DescriptionItem label="Processed by">Platform gateway (paid out to you)</DescriptionItem> : null}
                        <DescriptionItem label="Receipt">
                            {p.receipt_id ? (
                                <Link href={route('receipts.show', p.receipt_id)} className="inline-flex items-center gap-1.5 text-brand-600 hover:text-brand-700">
                                    <Receipt className="size-4" /> {p.receipt_number}
                                </Link>
                            ) : null}
                        </DescriptionItem>
                        {p.notes ? (
                            <DescriptionItem label="Notes" className="sm:col-span-2">
                                <span className="whitespace-pre-line">{p.notes}</span>
                            </DescriptionItem>
                        ) : null}
                        {p.refund_reason ? (
                            <DescriptionItem label="Refund reason" className="sm:col-span-2">
                                <span className="whitespace-pre-line">{p.refund_reason}</span>
                                {p.refunded_at ? <span className="block text-xs text-slate-500">{formatDateTime(p.refunded_at)}</span> : null}
                            </DescriptionItem>
                        ) : null}
                    </dl>
                </Card>

                {p.invoice_id ? (
                    <Card className="h-fit">
                        <CardHeader title="Invoice balance" icon={FileText} />
                        <dl className="space-y-3 p-5 text-sm">
                            <div className="flex justify-between">
                                <dt className="text-slate-500">Invoice total</dt>
                                <dd className="font-medium">{money(p.invoice_total)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-slate-500">Still due</dt>
                                <dd className={p.invoice_balance > 0 ? 'font-semibold text-amber-700' : 'font-semibold text-emerald-600'}>{money(p.invoice_balance)}</dd>
                            </div>
                        </dl>
                    </Card>
                ) : null}
            </div>

            <RefundModal open={refundOpen} onClose={() => setRefundOpen(false)} payment={p} currency={currency} />
        </>
    );
}

function RefundModal({ open, onClose, payment, currency }) {
    const form = useForm({ amount: payment.remaining_refundable, reason: '' });
    const submit = (e) => {
        e?.preventDefault();
        form.post(route('payments.refund', payment.id), { preserveScroll: true, onSuccess: onClose });
    };
    return (
        <Modal
            open={open}
            onClose={onClose}
            title="Record a refund"
            description="Record money you've returned to the client (e.g. via your Stripe dashboard or bank). The invoice balance is updated."
            footer={
                <>
                    <Button variant="secondary" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button variant="danger" onClick={submit} loading={form.processing}>
                        Record refund
                    </Button>
                </>
            }
        >
            <form onSubmit={submit} className="space-y-4" noValidate>
                <FormField label="Amount" required error={form.errors.amount} hint={`Up to ${formatMoney(payment.remaining_refundable, currency)}`}>
                    <Input type="number" min="0.01" step="0.01" max={payment.remaining_refundable} value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} invalid={Boolean(form.errors.amount)} />
                </FormField>
                <FormField label="Reason" required error={form.errors.reason}>
                    <Textarea rows={3} value={form.data.reason} onChange={(e) => form.setData('reason', e.target.value)} invalid={Boolean(form.errors.reason)} />
                </FormField>
                <button type="submit" className="hidden" />
            </form>
        </Modal>
    );
}
