import { usePage } from '@inertiajs/react';
import { ArrowUpRight, Clock, Landmark, Wallet } from 'lucide-react';
import { Button, Card, EmptyState, PageHeader, Pagination, StatCard, StatusBadge, Table, TBody, TD, TH, THead, TR } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDate, formatMoney, titleCase } from '@/lib/utils';

export default function PayoutsIndex({ payouts, balance, minimum }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const df = auth.company?.date_format;
    const canRequest = balance.available >= minimum && balance.available > 0;

    return (
        <>
            <PageHeader
                title="Payouts"
                description="Card payments collected through the platform, paid out to your bank or PayPal."
                actions={
                    <Button href={route('payouts.create')} icon={ArrowUpRight} disabled={!canRequest} className={!canRequest ? 'pointer-events-none opacity-50' : ''}>
                        Request payout
                    </Button>
                }
            />
            <div className="mb-6 grid gap-4 sm:grid-cols-3">
                <StatCard label="Available balance" value={formatMoney(balance.available, currency)} icon={Wallet} tone="green" hint={canRequest ? 'Ready to withdraw' : `Minimum payout ${formatMoney(minimum, currency)}`} />
                <StatCard label="In progress" value={formatMoney(balance.pending, currency)} icon={Clock} tone="yellow" />
                <StatCard label="Paid out" value={formatMoney(balance.paid_out, currency)} icon={Landmark} tone="brand" />
            </div>
            <Card>
                {payouts.data.length ? (
                    <>
                        <Table>
                            <THead>
                                <TH>Payout</TH>
                                <TH className="hidden sm:table-cell">Requested</TH>
                                <TH className="hidden md:table-cell">Method</TH>
                                <TH>Status</TH>
                                <TH align="right">Amount</TH>
                            </THead>
                            <TBody>
                                {payouts.data.map((p) => (
                                    <TR key={p.id} href={route('payouts.show', p.id)}>
                                        <TD>
                                            <span className="font-medium text-slate-900">{p.payout_number}</span>
                                            <span className="block text-xs text-slate-500">{p.payments_count} payments</span>
                                        </TD>
                                        <TD className="hidden sm:table-cell">{formatDate(p.requested_at, df)}</TD>
                                        <TD className="hidden md:table-cell">{titleCase(p.payout_method)}</TD>
                                        <TD>
                                            <StatusBadge status={p.status} />
                                        </TD>
                                        <TD align="right" className="font-medium text-slate-900 tabular-nums">
                                            {formatMoney(p.amount, p.currency || currency)}
                                        </TD>
                                    </TR>
                                ))}
                            </TBody>
                        </Table>
                        <Pagination paginator={payouts} />
                    </>
                ) : (
                    <EmptyState icon={Landmark} title="No payouts yet" description="When clients pay by card through the platform, the balance becomes available to withdraw here." />
                )}
            </Card>
        </>
    );
}
