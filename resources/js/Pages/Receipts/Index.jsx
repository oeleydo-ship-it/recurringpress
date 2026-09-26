import { Link, usePage } from '@inertiajs/react';
import { Download, Receipt } from 'lucide-react';
import { Card, EmptyState, FilterBar, PageHeader, Pagination, SearchInput, Table, TBody, TD, TH, THead, TR, useFilters } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDate, formatMoney } from '@/lib/utils';

export default function ReceiptsIndex({ receipts, filters: initial }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const df = auth.company?.date_format;
    const [filters, setFilter] = useFilters(route('receipts.index'), initial);

    return (
        <>
            <PageHeader title="Receipts" description="Issued automatically for every payment." />
            <Card>
                <FilterBar>
                    <SearchInput value={filters.search} onChange={(v) => setFilter('search', v)} placeholder="Receipt #, invoice # or client…" className="sm:max-w-sm sm:flex-1" />
                </FilterBar>
                {receipts.data.length ? (
                    <>
                        <Table>
                            <THead>
                                <TH>Receipt</TH>
                                <TH>Client</TH>
                                <TH className="hidden md:table-cell">Invoice</TH>
                                <TH className="hidden md:table-cell">Method</TH>
                                <TH align="right">Amount</TH>
                                <TH className="w-10" />
                            </THead>
                            <TBody>
                                {receipts.data.map((r) => (
                                    <TR key={r.id} href={route('receipts.show', r.id)}>
                                        <TD>
                                            <span className="font-medium text-slate-900">{r.receipt_number}</span>
                                            <span className="block text-xs text-slate-500">{formatDate(r.issued_at, df)}</span>
                                        </TD>
                                        <TD>
                                            <span className="block max-w-[200px] truncate">{r.client_name ?? '—'}</span>
                                        </TD>
                                        <TD className="hidden md:table-cell">
                                            {r.invoice_id ? (
                                                <Link href={route('invoices.show', r.invoice_id)} className="text-brand-600 hover:text-brand-700">
                                                    {r.invoice_number}
                                                </Link>
                                            ) : (
                                                '—'
                                            )}
                                        </TD>
                                        <TD className="hidden text-slate-600 md:table-cell">{r.payment_method_label}</TD>
                                        <TD align="right" className="font-medium text-slate-900 tabular-nums">
                                            {formatMoney(r.amount, currency)}
                                        </TD>
                                        <TD>
                                            <a href={route('receipts.download', r.id)} className="inline-flex rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Download receipt" title="Download PDF">
                                                <Download className="size-4" />
                                            </a>
                                        </TD>
                                    </TR>
                                ))}
                            </TBody>
                        </Table>
                        <Pagination paginator={receipts} />
                    </>
                ) : (
                    <EmptyState icon={Receipt} title="No receipts yet" description="A receipt is generated each time a payment is recorded." />
                )}
            </Card>
        </>
    );
}
