import { Link, router, useForm, usePage } from '@inertiajs/react';
import { Banknote, Check, CheckCircle2, Copy, Download, ExternalLink, Link2, Mail, Pencil, Receipt, Repeat, Send, Sparkles, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import { toast } from 'sonner';
import ReminderModal from '@/Components/ai/ReminderModal';
import DocumentPreview from '@/Components/documents/DocumentPreview';
import { Badge, Button, Card, CardHeader, Dropdown, FormField, Input, Modal, PageHeader, Select, StatusBadge, Textarea, useConfirm } from '@/Components/ui';
import { route } from '@/lib/route';
import { cn, formatDate, formatDateTime, formatMoney, titleCase, today } from '@/lib/utils';

export default function InvoiceShow({ invoice, payments, paymentMethods }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const df = auth.company?.date_format;
    const confirm = useConfirm();
    const [payOpen, setPayOpen] = useState(false);
    const [reminderOpen, setReminderOpen] = useState(false);
    const canRemind = Boolean(auth.company?.ai) && invoice.balance > 0 && ['sent', 'viewed', 'partial', 'overdue'].includes(invoice.status);
    const money = (v) => formatMoney(v, currency);
    const paidPct = invoice.total > 0 ? Math.min(100, (invoice.paid_amount / invoice.total) * 100) : 0;
    const status = invoice.is_overdue && !['overdue', 'paid'].includes(invoice.status) ? 'overdue' : invoice.status;
    const canPay = !['paid', 'cancelled', 'refunded', 'draft'].includes(invoice.status) || (invoice.status === 'draft' && invoice.total > 0);

    const post = (name) => router.post(route(name, invoice.id), {}, { preserveScroll: true });

    const emailClient = async () => {
        if (!invoice.client?.email) return;
        if (await confirm({ title: 'Email this invoice?', message: `A PDF copy will be sent to ${invoice.client.email}.`, confirmText: 'Send email', tone: 'brand' })) {
            post('invoices.email');
        }
    };

    const destroy = async () => {
        if (await confirm({ title: `Delete ${invoice.invoice_number}?`, message: 'This permanently removes the invoice.', confirmText: 'Delete invoice' })) {
            router.delete(route('invoices.destroy', invoice.id));
        }
    };

    const copyLink = async () => {
        try {
            await navigator.clipboard.writeText(invoice.share_url);
            toast.success('Payment link copied');
        } catch {
            toast.error('Could not copy — select the link manually.');
        }
    };

    return (
        <>
            <PageHeader
                title={invoice.invoice_number}
                back={route('invoices.index')}
                meta={
                    <>
                        <StatusBadge status={status} />
                        {invoice.is_recurring ? (
                            <Badge tone="brand">
                                <Repeat className="size-3" /> Recurring
                            </Badge>
                        ) : null}
                    </>
                }
                description={
                    invoice.client ? (
                        <>
                            Billed to{' '}
                            <Link href={route('clients.show', invoice.client.id)} className="font-medium text-slate-700 hover:text-brand-600">
                                {invoice.client.name}
                            </Link>{' '}
                            · Due {formatDate(invoice.due_date, df)}
                        </>
                    ) : null
                }
                actions={
                    <>
                        {canPay ? (
                            <Button icon={Banknote} onClick={() => setPayOpen(true)}>
                                Record payment
                            </Button>
                        ) : null}
                        {canRemind ? (
                            <Button variant="secondary" icon={Sparkles} onClick={() => setReminderOpen(true)} disabled={!invoice.client?.email} title={!invoice.client?.email ? 'Client has no email address' : undefined}>
                                AI reminder
                            </Button>
                        ) : null}
                        <Button variant="secondary" icon={Send} onClick={emailClient} disabled={!invoice.client?.email} title={!invoice.client?.email ? 'Client has no email address' : undefined}>
                            Email
                        </Button>
                        <Button variant="secondary" icon={Download} href={route('invoices.download', invoice.id)} external>
                            PDF
                        </Button>
                        <Dropdown
                            items={[
                                { label: 'Edit', icon: Pencil, href: route('invoices.edit', invoice.id), hidden: invoice.status === 'paid' },
                                { label: 'Mark as sent', icon: CheckCircle2, onClick: () => post('invoices.send'), hidden: invoice.status !== 'draft' },
                                { label: 'Duplicate', icon: Copy, href: route('invoices.duplicate', invoice.id), method: 'post' },
                                { label: 'Open client view', icon: ExternalLink, href: invoice.share_url, external: true },
                                { separator: true, hidden: invoice.status === 'paid' },
                                { label: 'Delete', icon: Trash2, danger: true, onClick: destroy, hidden: invoice.status === 'paid' },
                            ]}
                        />
                    </>
                }
            />

            <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_360px]">
                <DocumentPreview src={route('invoices.preview', invoice.id)} version={`${invoice.status}-${invoice.paid_amount}-${invoice.total}`} />

                <div className="space-y-6">
                    <Card>
                        <div className="p-5">
                            <p className="text-sm text-slate-500">{invoice.balance > 0 ? 'Amount due' : 'Paid in full'}</p>
                            <p className={cn('mt-1 text-3xl font-semibold tracking-tight tabular-nums', invoice.balance > 0 ? 'text-slate-900' : 'text-emerald-600')}>
                                {money(invoice.balance > 0 ? invoice.balance : invoice.total)}
                            </p>
                            <div className="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div className="h-full rounded-full bg-emerald-500 transition-all" style={{ width: `${paidPct}%` }} />
                            </div>
                            <div className="mt-2 flex justify-between text-xs text-slate-500">
                                <span>{money(invoice.paid_amount)} paid</span>
                                <span>of {money(invoice.total)}</span>
                            </div>
                        </div>
                        <dl className="divide-y divide-slate-100 border-t border-slate-100 text-sm">
                            <Meta label="Issued" value={formatDate(invoice.issue_date, df)} />
                            <Meta label="Due" value={formatDate(invoice.due_date, df)} danger={invoice.is_overdue} />
                            {invoice.reference ? <Meta label="Reference" value={invoice.reference} /> : null}
                            {invoice.sent_at ? <Meta label="Sent" value={formatDateTime(invoice.sent_at)} /> : null}
                            {invoice.created_by ? <Meta label="Created by" value={invoice.created_by} /> : null}
                            {invoice.recurring_invoice_id ? (
                                <Meta
                                    label="Schedule"
                                    value={
                                        <Link href={route('recurring-invoices.show', invoice.recurring_invoice_id)} className="text-brand-600 hover:text-brand-700">
                                            View recurring
                                        </Link>
                                    }
                                />
                            ) : null}
                        </dl>
                    </Card>

                    <Card>
                        <CardHeader title="Payment link" description="Clients can view and pay online." icon={Link2} />
                        <div className="flex gap-2 p-5">
                            <Input readOnly value={invoice.share_url} onFocus={(e) => e.target.select()} className="font-mono text-xs" />
                            <Button variant="secondary" size="icon" onClick={copyLink} aria-label="Copy payment link">
                                <Copy className="size-4" />
                            </Button>
                        </div>
                    </Card>

                    <Card>
                        <CardHeader title="Payments" description={payments.length ? `${payments.length} recorded` : 'No payments yet'} icon={Banknote} />
                        {payments.length ? (
                            <ol className="divide-y divide-slate-100">
                                {payments.map((p) => (
                                    <li key={p.id} className="flex items-start gap-3 px-5 py-3.5">
                                        <span className={cn('mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-full', p.status === 'completed' ? 'bg-emerald-50 text-emerald-600' : 'bg-slate-100 text-slate-500')}>
                                            <Check className="size-3.5" />
                                        </span>
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center justify-between gap-2">
                                                <span className="text-sm font-semibold text-slate-900 tabular-nums">{money(p.amount)}</span>
                                                <StatusBadge status={p.status} />
                                            </div>
                                            <p className="text-xs text-slate-500">
                                                {titleCase(p.payment_method)} · {formatDate(p.payment_date, df)}
                                            </p>
                                            <div className="mt-1 flex gap-3 text-xs">
                                                <Link href={route('payments.show', p.id)} className="font-medium text-brand-600 hover:text-brand-700">
                                                    {p.payment_number}
                                                </Link>
                                                {p.receipt_id ? (
                                                    <Link href={route('receipts.show', p.receipt_id)} className="inline-flex items-center gap-1 font-medium text-slate-500 hover:text-slate-800">
                                                        <Receipt className="size-3" /> Receipt
                                                    </Link>
                                                ) : null}
                                            </div>
                                        </div>
                                    </li>
                                ))}
                            </ol>
                        ) : (
                            <div className="px-5 pb-5 text-sm text-slate-500">
                                {canPay ? (
                                    <button type="button" onClick={() => setPayOpen(true)} className="font-medium text-brand-600 hover:text-brand-700">
                                        Record a payment →
                                    </button>
                                ) : (
                                    'Payments will appear here.'
                                )}
                            </div>
                        )}
                    </Card>

                    {invoice.client?.email ? (
                        <p className="flex items-center gap-2 px-1 text-xs text-slate-500">
                            <Mail className="size-3.5" /> {invoice.client.email}
                        </p>
                    ) : null}
                </div>
            </div>

            {canRemind ? <ReminderModal open={reminderOpen} onClose={() => setReminderOpen(false)} invoice={invoice} /> : null}
            <RecordPaymentModal open={payOpen} onClose={() => setPayOpen(false)} invoice={invoice} methods={paymentMethods} currency={currency} symbol={auth.company?.currency_symbol} />
        </>
    );
}

function Meta({ label, value, danger }) {
    return (
        <div className="flex items-center justify-between px-5 py-2.5">
            <dt className="text-slate-500">{label}</dt>
            <dd className={cn('font-medium', danger ? 'text-red-600' : 'text-slate-800')}>{value}</dd>
        </div>
    );
}

function RecordPaymentModal({ open, onClose, invoice, methods, currency, symbol }) {
    const form = useForm({ amount: invoice.balance, payment_method: 'bank_transfer', payment_date: today(), notes: '' });

    // Default to the current balance every time the modal opens.
    useEffect(() => {
        if (open) {
            form.setData({ amount: invoice.balance, payment_method: 'bank_transfer', payment_date: today(), notes: '' });
            form.clearErrors();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [open]);

    const submit = (e) => {
        e?.preventDefault();
        form.post(route('invoices.paid', invoice.id), {
            preserveScroll: true,
            onSuccess: () => onClose(),
        });
    };

    return (
        <Modal
            open={open}
            onClose={onClose}
            title="Record a payment"
            description={`${formatMoney(invoice.balance, currency)} outstanding on ${invoice.invoice_number}`}
            footer={
                <>
                    <Button variant="secondary" onClick={onClose}>
                        Cancel
                    </Button>
                    <Button onClick={submit} loading={form.processing} icon={Check}>
                        Record payment
                    </Button>
                </>
            }
        >
            <form onSubmit={submit} className="space-y-4" noValidate>
                <div className="grid gap-4 sm:grid-cols-2">
                    <FormField label="Amount" required error={form.errors.amount}>
                        <Input type="number" min="0.01" step="0.01" max={invoice.balance} prefix={symbol} value={form.data.amount} onChange={(e) => form.setData('amount', e.target.value)} invalid={Boolean(form.errors.amount)} autoFocus />
                    </FormField>
                    <FormField label="Date received" error={form.errors.payment_date}>
                        <Input type="date" max={today()} value={form.data.payment_date} onChange={(e) => form.setData('payment_date', e.target.value)} />
                    </FormField>
                </div>
                <FormField label="Method" error={form.errors.payment_method}>
                    <Select value={form.data.payment_method} onChange={(e) => form.setData('payment_method', e.target.value)} options={methods} />
                </FormField>
                <FormField label="Note" error={form.errors.notes}>
                    <Textarea rows={2} value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)} placeholder="e.g. Bank ref 20391" />
                </FormField>
                <div className="flex gap-2">
                    {[1, 0.5].map((f) => (
                        <button key={f} type="button" onClick={() => form.setData('amount', Math.round(invoice.balance * f * 100) / 100)} className="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 hover:bg-slate-200">
                            {f === 1 ? 'Full balance' : 'Half'}
                        </button>
                    ))}
                </div>
                <button type="submit" className="hidden" />
            </form>
        </Modal>
    );
}
