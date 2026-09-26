import { Link, usePage } from '@inertiajs/react';
import { ArrowDownLeft, Banknote, CreditCard, Receipt, RotateCcw } from 'lucide-react';
import {
    Card,
    EmptyState,
    FilterBar,
    PageHeader,
    Pagination,
    SearchInput,
    Select,
    StatCard,
    StatusBadge,
    Table,
    TBody,
    TD,
    TH,
    THead,
    TR,
    useFilters,
} from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDate, formatMoney, titleCase } from '@/lib/utils';

const STATUSES = { '': 'Any status', completed: 'Completed', pending: 'Pending', partially_refunded: 'Partially refunded', refunded: 'Refunded', failed: 'Failed' };

export default function PaymentsIndex({ payments, filters: initial, paymentMethods, summary }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const df = auth.company?.date_format;
    const [filters, setFilter] = useFilters(route('payments.index'), initial);

    return (
        <>
            <PageHeader title="Transactions" description="Every payment received against your invoices." />

            <div className="mb-6 grid gap-4 sm:grid-cols-3">
                <StatCard label="Received (30 days)" value={formatMoney(summary.received_30d, currency)} icon={ArrowDownLeft} tone="green" hint={`${summary.count_30d} payments`} />
                <StatCard label="Refunded (all time)" value={formatMoney(summary.refunded, currency)} icon={RotateCcw} tone="purple" />
                <StatCard label="Receipts" value="Auto-issued" icon={Receipt} tone="gray" hint="A receipt is created for every payment" href={route('receipts.index')} />
            </div>

            <Card>
                <FilterBar>
                    <SearchInput value={filters.search} onChange={(v) => setFilter('search', v)} placeholder="Payment #, invoice #, client or transaction ID…" className="sm:max-w-sm sm:flex-1" />
                    <Select value={filters.status ?? ''} onChange={(e) => setFilter('status', e.target.value)} className="sm:w-48" options={STATUSES} />
                    <Select value={filters.payment_method ?? ''} onChange={(e) => setFilter('payment_method', e.target.value)} className="sm:w-44" options={{ '': 'Any method', ...paymentMethods }} />
                </FilterBar>
                {payments.data.length ? (
                    <>
                        <Table>
                            <THead>
                                <TH>Payment</TH>
                                <TH>Client</TH>
                                <TH className="hidden md:table-cell">Invoice</TH>
                                <TH className="hidden lg:table-cell">Method</TH>
                                <TH>Status</TH>
                                <TH align="right">Amount</TH>
                            </THead>
                            <TBody>
                                {payments.data.map((p) => (
                                    <TR key={p.id} href={route('payments.show', p.id)}>
                                        <TD>
                                            <span className="font-medium text-slate-900">{p.payment_number}</span>
                                            <span className="block text-xs text-slate-500">{formatDate(p.payment_date, df)}</span>
                                        </TD>
                                        <TD>
                                            <span className="block max-w-[200px] truncate">{p.client_name ?? '—'}</span>
                                        </TD>
                                        <TD className="hidden md:table-cell">
                                            {p.invoice_id ? (
                                                <Link href={route('invoices.show', p.invoice_id)} className="text-brand-600 hover:text-brand-700">
                                                    {p.invoice_number}
                                                </Link>
                                            ) : (
                                                '—'
                                            )}
                                        </TD>
                                        <TD className="hidden lg:table-cell">
                                            <span className="inline-flex items-center gap-1.5 text-slate-600">
                                                {p.payment_method === 'stripe' || p.payment_method === 'credit_card' ? <CreditCard className="size-4 text-slate-400" /> : <Banknote className="size-4 text-slate-400" />}
                                                {p.payment_method_label}
                                            </span>
                                        </TD>
                                        <TD>
                                            <StatusBadge status={p.status} label={titleCase(p.status)} />
                                        </TD>
                                        <TD align="right">
                                            <span className="font-medium text-slate-900 tabular-nums">{formatMoney(p.amount, currency)}</span>
                                            {p.refunded_amount > 0 ? <span className="block text-xs text-violet-600">−{formatMoney(p.refunded_amount, currency)} refunded</span> : null}
                                        </TD>
                                    </TR>
                                ))}
                            </TBody>
                        </Table>
                        <Pagination paginator={payments} />
                    </>
                ) : (
                    <EmptyState icon={Banknote} title="No payments yet" description="Payments appear here when clients pay online or you record them on an invoice." />
                )}
            </Card>
        </>
    );
}
