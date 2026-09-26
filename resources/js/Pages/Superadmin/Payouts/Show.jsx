import { router, useForm } from '@inertiajs/react';
import { Check, Copy, Loader, X } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import { Button, Card, CardHeader, DescriptionItem, FormField, Input, Modal, PageHeader, StatusBadge, Table, TBody, TD, TH, THead, TR, Textarea, useConfirm } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDate, formatDateTime, formatMoney, titleCase } from '@/lib/utils';

const BANK_LABELS = {
    bank_name: 'Bank',
    bank_account_name: 'Account holder',
    bank_account_number: 'Account number',
    bank_routing_number: 'Routing / sort code',
    bank_iban: 'IBAN',
    bank_swift: 'SWIFT / BIC',
};

export default function SuperadminPayoutShow({ payout: p, payments }) {
    const confirm = useConfirm();
    const [dialog, setDialog] = useState(null);
    const money = (v) => formatMoney(v, p.currency || 'USD');
    const open = ['pending', 'processing'].includes(p.status);
    const complete = useForm({ transaction_id: '', admin_notes: '' });
    const reject = useForm({ admin_notes: '' });

    const markProcessing = async () => {
        if (await confirm({ title: 'Mark as processing?', message: 'Let the tenant know the transfer is underway.', confirmText: 'Mark processing', tone: 'brand' })) {
            router.post(route('superadmin.payouts.process', p.id), {}, { preserveScroll: true });
        }
    };

    const copy = async (v) => {
        try {
            await navigator.clipboard.writeText(String(v));
            toast.success('Copied');
        } catch {
            toast.error('Copy failed');
        }
    };

    return (
        <>
            <PageHeader
                title={p.payout_number}
                back={route('superadmin.payouts.index')}
                meta={<StatusBadge status={p.status} label={p.status === 'failed' ? 'Rejected' : undefined} />}
                description={`${p.company?.name ?? 'Unknown company'} · requested ${formatDateTime(p.requested_at)}`}
                actions={
                    open ? (
                        <>
                            {p.status === 'pending' ? (
                                <Button variant="secondary" icon={Loader} onClick={markProcessing}>
                                    Mark processing
                                </Button>
                            ) : null}
                            <Button variant="danger-soft" icon={X} onClick={() => setDialog('reject')}>
                                Reject
                            </Button>
                            <Button variant="success" icon={Check} onClick={() => setDialog('complete')}>
                                Mark as paid
                            </Button>
                        </>
                    ) : null
                }
            />

            <div className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <div className="grid gap-6 p-6 sm:grid-cols-3">
                            <div>
                                <p className="text-sm text-slate-500">Gross</p>
                                <p className="mt-1 text-xl font-semibold tabular-nums">{money(p.gross_amount)}</p>
                            </div>
                            <div>
                                <p className="text-sm text-slate-500">Fee ({p.fee_percentage}%)</p>
                                <p className="mt-1 text-xl font-semibold text-slate-500 tabular-nums">− {money(p.fee_amount)}</p>
                            </div>
                            <div>
                                <p className="text-sm text-slate-500">Send to tenant</p>
                                <p className="mt-1 text-2xl font-semibold text-emerald-600 tabular-nums">{money(p.amount)}</p>
                            </div>
                        </div>
                    </Card>
                    <Card>
                        <CardHeader title="Included payments" description={`${payments.length} payments`} />
                        <Table>
                            <THead>
                                <TH>Payment</TH>
                                <TH>Invoice</TH>
                                <TH className="hidden sm:table-cell">Client</TH>
                                <TH align="right">Amount</TH>
                            </THead>
                            <TBody>
                                {payments.map((x) => (
                                    <TR key={x.id}>
                                        <TD>
                                            <span className="font-medium text-slate-900">{x.payment_number}</span>
                                            <span className="block text-xs text-slate-500">{formatDate(x.payment_date)}</span>
                                        </TD>
                                        <TD>{x.invoice_number ?? '—'}</TD>
                                        <TD className="hidden sm:table-cell">{x.client ?? '—'}</TD>
                                        <TD align="right" className="tabular-nums">
                                            {money(x.amount)}
                                        </TD>
                                    </TR>
                                ))}
                            </TBody>
                        </Table>
                    </Card>
                </div>

                <div className="space-y-6">
                    <Card>
                        <CardHeader title="Pay to" description={titleCase(p.payout_method)} />
                        <dl className="divide-y divide-slate-100 text-sm">
                            {(p.payout_method === 'paypal' ? [['PayPal', p.paypal_email]] : Object.entries(p.bank).map(([k, v]) => [BANK_LABELS[k] ?? k, v]))
                                .filter(([, v]) => v)
                                .map(([k, v]) => (
                                    <div key={k} className="flex items-center justify-between gap-3 px-5 py-2.5">
                                        <dt className="text-slate-500">{k}</dt>
                                        <dd className="flex min-w-0 items-center gap-2">
                                            <span className="truncate font-medium text-slate-900">{v}</span>
                                            <button type="button" onClick={() => copy(v)} className="rounded p-1 text-slate-400 hover:text-slate-700" aria-label={`Copy ${k}`}>
                                                <Copy className="size-3.5" />
                                            </button>
                                        </dd>
                                    </div>
                                ))}
                        </dl>
                    </Card>
                    <Card>
                        <dl className="space-y-4 p-5">
                            <DescriptionItem label="Company">
                                {p.company?.name}
                                {p.company?.email ? <span className="block text-slate-500">{p.company.email}</span> : null}
                            </DescriptionItem>
                            {p.notes ? <DescriptionItem label="Tenant note">{p.notes}</DescriptionItem> : null}
                            {p.transaction_id ? <DescriptionItem label="Transfer reference">{p.transaction_id}</DescriptionItem> : null}
                            {p.admin_notes ? <DescriptionItem label="Admin notes">{p.admin_notes}</DescriptionItem> : null}
                            {p.processed_at ? <DescriptionItem label="Processed">{formatDateTime(p.processed_at)}</DescriptionItem> : null}
                        </dl>
                    </Card>
                </div>
            </div>

            <Modal
                open={dialog === 'complete'}
                onClose={() => setDialog(null)}
                title="Mark payout as paid"
                description={`Confirm you've sent ${money(p.amount)} to ${p.company?.name}.`}
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setDialog(null)}>
                            Cancel
                        </Button>
                        <Button variant="success" icon={Check} loading={complete.processing} onClick={() => complete.post(route('superadmin.payouts.complete', p.id))}>
                            Mark as paid
                        </Button>
                    </>
                }
            >
                <div className="space-y-4">
                    <FormField label="Transfer reference" error={complete.errors.transaction_id}>
                        <Input value={complete.data.transaction_id} onChange={(e) => complete.setData('transaction_id', e.target.value)} />
                    </FormField>
                    <FormField label="Note for the tenant" error={complete.errors.admin_notes}>
                        <Textarea rows={2} value={complete.data.admin_notes} onChange={(e) => complete.setData('admin_notes', e.target.value)} />
                    </FormField>
                </div>
            </Modal>

            <Modal
                open={dialog === 'reject'}
                onClose={() => setDialog(null)}
                title="Reject payout"
                description="The payments return to the tenant's available balance."
                footer={
                    <>
                        <Button variant="secondary" onClick={() => setDialog(null)}>
                            Cancel
                        </Button>
                        <Button variant="danger" loading={reject.processing} onClick={() => reject.post(route('superadmin.payouts.reject', p.id))}>
                            Reject payout
                        </Button>
                    </>
                }
            >
                <FormField label="Reason (shown to the tenant)" required error={reject.errors.admin_notes}>
                    <Textarea rows={3} value={reject.data.admin_notes} onChange={(e) => reject.setData('admin_notes', e.target.value)} invalid={Boolean(reject.errors.admin_notes)} />
                </FormField>
            </Modal>
        </>
    );
}
