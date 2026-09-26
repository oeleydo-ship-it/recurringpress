import { Clock, Coins, Landmark, Loader } from 'lucide-react';
import {
    Card,
    EmptyState,
    FilterBar,
    LinkTabs,
    PageHeader,
    Pagination,
    SearchInput,
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

export default function SuperadminPayouts({ payouts, filters: initial, stats }) {
    const [filters, setFilter] = useFilters(route('superadmin.payouts.index'), initial);
    const usd = (v) => formatMoney(v, 'USD');

    return (
        <>
            <PageHeader title="Payouts" description="Tenant withdrawal requests for card payments collected by the platform." />
            <div className="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Awaiting action" value={stats.pending + stats.processing} icon={Clock} tone={stats.pending ? 'yellow' : 'gray'} hint={`${stats.pending} pending · ${stats.processing} processing`} />
                <StatCard label="To pay out" value={usd(stats.pending_amount)} icon={Loader} tone="blue" />
                <StatCard label="Paid out" value={usd(stats.paid_amount)} icon={Landmark} tone="green" />
                <StatCard label="Fees earned" value={usd(stats.fees_earned)} icon={Coins} tone="brand" />
            </div>
            <LinkTabs
                className="mb-4"
                tabs={[
                    ['', 'All'],
                    ['pending', 'Pending'],
                    ['processing', 'Processing'],
                    ['completed', 'Completed'],
                    ['failed', 'Rejected'],
                ].map(([key, label]) => ({ label, active: (filters.status ?? '') === key, href: route('superadmin.payouts.index', key ? { status: key } : {}) }))}
            />
            <Card>
                <FilterBar>
                    <SearchInput value={filters.search} onChange={(v) => setFilter('search', v)} placeholder="Payout # or company…" className="sm:max-w-sm sm:flex-1" />
                </FilterBar>
                {payouts.data.length ? (
                    <>
                        <Table>
                            <THead>
                                <TH>Payout</TH>
                                <TH>Company</TH>
                                <TH className="hidden md:table-cell">Method</TH>
                                <TH>Status</TH>
                                <TH align="right">Net amount</TH>
                            </THead>
                            <TBody>
                                {payouts.data.map((p) => (
                                    <TR key={p.id} href={route('superadmin.payouts.show', p.id)}>
                                        <TD>
                                            <span className="font-medium text-slate-900">{p.payout_number}</span>
                                            <span className="block text-xs text-slate-500">{formatDate(p.requested_at)}</span>
                                        </TD>
                                        <TD>{p.company ?? '—'}</TD>
                                        <TD className="hidden md:table-cell">{titleCase(p.payout_method)}</TD>
                                        <TD>
                                            <StatusBadge status={p.status} label={p.status === 'failed' ? 'Rejected' : undefined} />
                                        </TD>
                                        <TD align="right">
                                            <span className="font-medium text-slate-900 tabular-nums">{formatMoney(p.amount, p.currency || 'USD')}</span>
                                            {p.fee_amount > 0 ? <span className="block text-xs text-slate-500">fee {formatMoney(p.fee_amount, p.currency || 'USD')}</span> : null}
                                        </TD>
                                    </TR>
                                ))}
                            </TBody>
                        </Table>
                        <Pagination paginator={payouts} />
                    </>
                ) : (
                    <EmptyState icon={Landmark} title="No payouts here" />
                )}
            </Card>
        </>
    );
}
