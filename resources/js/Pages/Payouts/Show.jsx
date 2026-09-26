import { Link, usePage } from '@inertiajs/react';
import { Card, CardHeader, DescriptionItem, PageHeader, StatusBadge, Table, TBody, TD, TH, THead, TR } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDate, formatDateTime, formatMoney, titleCase } from '@/lib/utils';

export default function PayoutShow({ payout: p, payments }) {
    const { auth } = usePage().props;
    const currency = p.currency || auth.company?.currency;
    const money = (v) => formatMoney(v, currency);

    return (
        <>
            <PageHeader title={p.payout_number} back={route('payouts.index')} meta={<StatusBadge status={p.status} />} description={`Requested ${formatDateTime(p.requested_at)}`} />
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
                                <p className="text-sm text-slate-500">Net payout</p>
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
                                    <TR key={x.id} href={route('payments.show', x.id)}>
                                        <TD>
                                            <span className="font-medium text-slate-900">{x.payment_number}</span>
                                            <span className="block text-xs text-slate-500">{formatDate(x.payment_date, auth.company?.date_format)}</span>
                                        </TD>
                                        <TD>
                                            {x.invoice_id ? (
                                                <Link href={route('invoices.show', x.invoice_id)} className="text-brand-600 hover:text-brand-700">
                                                    {x.invoice_number}
                                                </Link>
                                            ) : (
                                                '—'
                                            )}
                                        </TD>
                                        <TD className="hidden sm:table-cell">{x.client_name}</TD>
                                        <TD align="right" className="tabular-nums">
                                            {money(x.amount)}
                                        </TD>
                                    </TR>
                                ))}
                            </TBody>
                        </Table>
                    </Card>
                </div>
                <Card className="h-fit">
                    <CardHeader title="Destination" />
                    <dl className="space-y-4 p-5">
                        <DescriptionItem label="Method">{titleCase(p.payout_method)}</DescriptionItem>
                        {p.payout_method === 'paypal' ? (
                            <DescriptionItem label="PayPal">{p.paypal_email}</DescriptionItem>
                        ) : (
                            <>
                                <DescriptionItem label="Bank">{p.bank_name}</DescriptionItem>
                                <DescriptionItem label="Account holder">{p.bank_account_name}</DescriptionItem>
                                <DescriptionItem label="Account">{p.bank_account_last4 ? `•••• ${p.bank_account_last4}` : p.bank_iban_last4 ? `IBAN •••• ${p.bank_iban_last4}` : null}</DescriptionItem>
                            </>
                        )}
                        {p.transaction_id ? <DescriptionItem label="Transfer reference">{p.transaction_id}</DescriptionItem> : null}
                        {p.processed_at ? <DescriptionItem label="Processed">{formatDateTime(p.processed_at)}</DescriptionItem> : null}
                        {p.admin_notes ? <DescriptionItem label="Message from support">{p.admin_notes}</DescriptionItem> : null}
                        {p.notes ? <DescriptionItem label="Your note">{p.notes}</DescriptionItem> : null}
                    </dl>
                </Card>
            </div>
        </>
    );
}
