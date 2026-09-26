import { router } from '@inertiajs/react';
import { Building2, Coins, Landmark, Repeat, TrendingUp } from 'lucide-react';
import { Bar, CartesianGrid, ComposedChart, Legend, Line, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import ClientOnly from '@/Components/ClientOnly';
import { Card, CardHeader, EmptyState, PageHeader, Select, StatCard, Table, TBody, TD, TH, THead, TR } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDate, formatMoney } from '@/lib/utils';

export default function SuperadminReports({ range, ranges, period, kpis, monthly, plans, topCompanies }) {
    const usd = (v) => formatMoney(v, 'USD');

    return (
        <>
            <PageHeader
                title="Platform reports"
                description={`${formatDate(period.start)} – ${formatDate(period.end)}`}
                actions={<Select value={range} onChange={(e) => router.get(route('superadmin.reports.index'), { date_range: e.target.value }, { preserveScroll: true })} options={ranges} className="w-44" />}
            />
            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="MRR" value={usd(kpis.mrr)} icon={Repeat} tone="green" hint={`ARR ${usd(kpis.arr)}`} />
                <StatCard label="New companies" value={kpis.new_companies} icon={Building2} tone="brand" hint={`${kpis.companies} total · ${kpis.active_companies} active`} />
                <StatCard label="Card volume" value={usd(kpis.volume)} icon={TrendingUp} tone="blue" hint={`${kpis.payments} payments in period`} />
                <StatCard label="Payout fees earned" value={usd(kpis.fees)} icon={Coins} tone="purple" hint={`${usd(kpis.paid_out)} paid out · ${usd(kpis.pending_payouts)} pending`} />
            </div>

            <Card className="mt-6">
                <CardHeader title="Last 12 months" description="Card volume through the platform and new companies" />
                <div className="h-80 px-2 pt-4 pb-2">
                    <ClientOnly>
                        <ResponsiveContainer width="100%" height="100%">
                            <ComposedChart data={monthly} margin={{ top: 5, right: 12, left: 0, bottom: 0 }}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" vertical={false} />
                                <XAxis dataKey="month" tickLine={false} axisLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                                <YAxis yAxisId="v" tickLine={false} axisLine={false} width={60} tick={{ fontSize: 12, fill: '#64748b' }} tickFormatter={(v) => formatMoney(v, 'USD', { compact: true })} />
                                <YAxis yAxisId="s" orientation="right" allowDecimals={false} tickLine={false} axisLine={false} width={32} tick={{ fontSize: 12, fill: '#64748b' }} />
                                <Tooltip formatter={(v, n) => (n === 'Card volume' ? [usd(v), n] : [v, n])} labelFormatter={(_, p) => p?.[0]?.payload?.label} contentStyle={{ borderRadius: 12, border: '1px solid #e2e8f0', fontSize: 12 }} />
                                <Legend iconType="circle" wrapperStyle={{ fontSize: 12 }} />
                                <Bar yAxisId="v" dataKey="volume" name="Card volume" fill="#6366f1" radius={[4, 4, 0, 0]} maxBarSize={28} />
                                <Line yAxisId="s" type="monotone" dataKey="signups" name="New companies" stroke="#0ea5e9" strokeWidth={2.5} dot={{ r: 3 }} />
                            </ComposedChart>
                        </ResponsiveContainer>
                    </ClientOnly>
                </div>
            </Card>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader title="Subscriptions by plan" icon={Repeat} />
                    <Table>
                        <THead>
                            <TH>Plan</TH>
                            <TH align="right">Active</TH>
                            <TH align="right">All</TH>
                            <TH align="right">MRR</TH>
                        </THead>
                        <TBody>
                            {plans.map((p) => (
                                <TR key={p.name}>
                                    <TD>
                                        <span className="font-medium text-slate-900">{p.name}</span>
                                        <span className="block text-xs text-slate-500">{p.price > 0 ? `${usd(p.price)} / ${p.billing_cycle === 'yearly' ? 'yr' : 'mo'}` : 'Free'}</span>
                                    </TD>
                                    <TD align="right">{p.active}</TD>
                                    <TD align="right">{p.companies}</TD>
                                    <TD align="right" className="font-medium text-slate-900">
                                        {usd(p.mrr)}
                                    </TD>
                                </TR>
                            ))}
                        </TBody>
                    </Table>
                </Card>
                <Card>
                    <CardHeader title="Top companies by card volume" icon={Landmark} description="In the selected period" />
                    {topCompanies.length ? (
                        <Table>
                            <THead>
                                <TH>Company</TH>
                                <TH align="right">Payments</TH>
                                <TH align="right">Volume</TH>
                            </THead>
                            <TBody>
                                {topCompanies.map((c) => (
                                    <TR key={c.name}>
                                        <TD className="font-medium text-slate-900">{c.name}</TD>
                                        <TD align="right">{c.count}</TD>
                                        <TD align="right">{usd(c.total)}</TD>
                                    </TR>
                                ))}
                            </TBody>
                        </Table>
                    ) : (
                        <EmptyState icon={Landmark} title="No platform payments in this period" className="py-8" />
                    )}
                </Card>
            </div>
        </>
    );
}
