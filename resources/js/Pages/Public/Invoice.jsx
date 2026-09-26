import { CheckCircle2, Clock } from 'lucide-react';
import DocumentPreview from '@/Components/documents/DocumentPreview';
import PaymentPanel from '@/Components/PaymentPanel';
import PublicShell from '@/Components/PublicShell';
import { Card, StatusBadge } from '@/Components/ui';
import { formatDate, formatMoney } from '@/lib/utils';

export default function PublicInvoice({ invoice, company, payment, urls }) {
    const money = (v) => formatMoney(v, invoice.currency);
    const paid = invoice.amount_due <= 0 || invoice.status === 'paid';

    return (
        <PublicShell title={`Invoice ${invoice.number} from ${company.name}`} company={company} pdfUrl={urls.pdf}>
            <div className="grid gap-6 lg:grid-cols-[minmax(0,1fr)_380px]">
                <div className="order-2 lg:order-1">
                    <DocumentPreview src={urls.document} version={`${invoice.status}-${invoice.paid}`} />
                </div>

                <div className="order-1 space-y-4 lg:order-2">
                    <Card className="overflow-hidden lg:sticky lg:top-6">
                        <div className="p-6">
                            <div className="flex items-center justify-between">
                                <p className="text-sm text-slate-500">Invoice {invoice.number}</p>
                                <StatusBadge status={invoice.is_overdue ? 'overdue' : invoice.status} />
                            </div>
                            {paid ? (
                                <div className="mt-4 flex items-center gap-3">
                                    <CheckCircle2 className="size-10 text-emerald-500" />
                                    <div>
                                        <p className="text-2xl font-semibold text-slate-900">Paid in full</p>
                                        <p className="text-sm text-slate-500">{money(invoice.total)} — thank you!</p>
                                    </div>
                                </div>
                            ) : (
                                <>
                                    <p className="mt-3 text-sm text-slate-500">Amount due</p>
                                    <p className="text-4xl font-semibold tracking-tight text-slate-900 tabular-nums">{money(invoice.amount_due)}</p>
                                    <p className={invoice.is_overdue ? 'mt-1 flex items-center gap-1 text-sm font-medium text-red-600' : 'mt-1 flex items-center gap-1 text-sm text-slate-500'}>
                                        <Clock className="size-4" /> {invoice.is_overdue ? 'Overdue since' : 'Due'} {formatDate(invoice.due_date)}
                                    </p>
                                    {invoice.paid > 0 ? (
                                        <p className="mt-2 text-sm text-slate-500">
                                            {money(invoice.paid)} of {money(invoice.total)} already paid
                                        </p>
                                    ) : null}
                                </>
                            )}
                        </div>
                        {!paid && payment ? (
                            <div className="border-t border-slate-100 bg-slate-50/50 p-6">
                                <PaymentPanel payment={payment} amount={invoice.amount_due} currency={invoice.currency} brand={company.brand} intentUrl={urls.intent} returnUrl={urls.return} />
                            </div>
                        ) : null}
                    </Card>
                    {invoice.client_name ? <p className="px-1 text-xs text-slate-500">Billed to {invoice.client_name} · Issued {formatDate(invoice.issue_date)}</p> : null}
                </div>
            </div>
        </PublicShell>
    );
}
