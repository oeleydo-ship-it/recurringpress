import { router, usePage } from '@inertiajs/react';
import { Banknote, CalendarRange, FileText, PiggyBank, TrendingDown, UserPlus } from 'lucide-react';
import { useState } from 'react';
import { Bar, CartesianGrid, ComposedChart, Legend, Line, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import ClientOnly from '@/Components/ClientOnly';
import { Card, CardHeader, EmptyState, Input, PageHeader, Select, StatCard } from '@/Components/ui';
import { route } from '@/lib/route';
import { cn, formatDate, formatMoney, formatNumber } from '@/lib/utils';

const AGING = [
    ['current', 'Not yet due', 'bg-emerald-500'],
    ['1_30', '1–30 days', 'bg-amber-400'],
    ['31_60', '31–60 days', 'bg-orange-500'],
    ['61_90', '61–90 days', 'bg-red-500'],
    ['90_plus', '90+ days', 'bg-red-700'],
];

export default function ReportsIndex({ range, ranges, period, kpis, monthly, aging, topClients, topProducts, paymentMethods, expensesByCategory }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const df = auth.company?.date_format;
    const brand = auth.theme?.primary ?? '#4f46e5';
    const money = (v) => formatMoney(v, currency);
    const [custom, setCustom] = useState({ from: period.start, to: period.end });

    const go = (params) => router.get(route('reports.index'), params, { preserveScroll: true, preserveState: true });
    const agingTotal = Object.values(aging).reduce((a, b) => a + b, 0);

    return (
        <>
            <PageHeader
                title="Reports"
                description={`${formatDate(period.start, df)} – ${formatDate(period.end, df)}`}
                actions={
                    <div className="flex flex-wrap items-center gap-2">
                        <Select value={range} onChange={(e) => (e.target.value === 'custom' ? go({ date_range: 'custom', ...custom }) : go({ date_range: e.target.value }))} options={ranges} className="w-44" />
                        {range === 'custom' ? (
                            <>
                                <Input type="date" value={custom.from} onChange={(e) => setCustom((c) => ({ ...c, from: e.target.value }))} className="w-40" aria-label="From" />
                                <Input type="date" value={custom.to} onChange={(e) => setCustom((c) => ({ ...c, to: e.target.value }))} className="w-40" aria-label="To" />
                                <button type="button" onClick={() => go({ date_range: 'custom', ...custom })} className="inline-flex h-9 items-center gap-1.5 rounded-lg bg-slate-900 px-3 text-sm font-medium text-white">
                                    <CalendarRange className="size-4" /> Apply
                                </button>
                            </>
                        ) : null}
                    </div>
                }
            />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Collected" value={money(kpis.revenue)} icon={Banknote} tone="green" trend={kpis.revenue_trend ?? undefined} hint="vs previous period" />
                <StatCard label="Expenses" value={money(kpis.expenses)} icon={TrendingDown} tone="yellow" trend={kpis.expenses_trend === null ? undefined : -kpis.expenses_trend} hint="vs previous period" />
                <StatCard label="Profit" value={money(kpis.profit)} icon={PiggyBank} tone={kpis.profit >= 0 ? 'brand' : 'red'} hint={kpis.margin !== null ? `${kpis.margin}% margin` : 'No revenue in period'} />
                <StatCard label="Invoiced" value={money(kpis.invoiced)} icon={FileText} tone="blue" hint={`${kpis.invoices_issued} invoices · ${kpis.new_clients} new clients`} />
            </div>

            <Card className="mt-6">
                <CardHeader title="Profit & loss" description="Collected revenue vs expenses, last 12 months" />
                <div className="h-80 px-2 pt-4 pb-2">
                    <ClientOnly>
                        <ResponsiveContainer width="100%" height="100%">
                            <ComposedChart data={monthly} margin={{ top: 5, right: 16, left: 0, bottom: 0 }}>
                                <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" vertical={false} />
                                <XAxis dataKey="month" tickLine={false} axisLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                                <YAxis tickLine={false} axisLine={false} width={64} tick={{ fontSize: 12, fill: '#64748b' }} tickFormatter={(v) => formatMoney(v, currency, { compact: true })} />
                                <Tooltip formatter={(v, n) => [money(v), n]} labelFormatter={(_, p) => p?.[0]?.payload?.label} contentStyle={{ borderRadius: 12, border: '1px solid #e2e8f0', fontSize: 12 }} />
                                <Legend iconType="circle" wrapperStyle={{ fontSize: 12 }} />
                                <Bar dataKey="revenue" name="Revenue" fill={brand} radius={[4, 4, 0, 0]} maxBarSize={28} />
                                <Bar dataKey="expenses" name="Expenses" fill="#f59e0b" radius={[4, 4, 0, 0]} maxBarSize={28} />
                                <Line type="monotone" dataKey="profit" name="Profit" stroke="#0f172a" strokeWidth={2} dot={false} />
                            </ComposedChart>
                        </ResponsiveContainer>
                    </ClientOnly>
                </div>
            </Card>

            <div className="mt-6 grid gap-6 lg:grid-cols-2">
                <Card>
                    <CardHeader title="Receivables aging" description={`${money(kpis.outstanding)} outstanding · ${money(kpis.overdue)} overdue`} />
                    <div className="p-5">
                        {agingTotal > 0 ? (
                            <>
                                <div className="flex h-3 overflow-hidden rounded-full bg-slate-100">
                                    {AGING.map(([key, , color]) => (aging[key] > 0 ? <div key={key} className={color} style={{ width: `${(aging[key] / agingTotal) * 100}%` }} title={money(aging[key])} /> : null))}
                                </div>
                                <ul className="mt-5 space-y-2.5">
                                    {AGING.map(([key, label, color]) => (
                                        <li key={key} className="flex items-center justify-between text-sm">
                                            <span className="flex items-center gap-2 text-slate-600">
                                                <span className={cn('size-2.5 rounded-full', color)} />
                                                {label}
                                            </span>
                                            <span className="font-medium text-slate-900 tabular-nums">{money(aging[key])}</span>
                                        </li>
                                    ))}
                                </ul>
                            </>
                        ) : (
                            <p className="text-sm text-slate-500">Nothing outstanding. 🎉</p>
                        )}
                    </div>
                </Card>

                <RankCard title="Top clients" rows={topClients.map((c) => ({ key: c.id, label: c.name, sub: `${c.payments} payment${c.payments === 1 ? '' : 's'}`, value: c.revenue }))} money={money} emptyIcon={UserPlus} />
                <RankCard title="Top products & services" rows={topProducts.map((p) => ({ key: p.name, label: p.name, sub: `${formatNumber(p.quantity, 2)} sold`, value: p.revenue }))} money={money} emptyIcon={FileText} />
                <RankCard title="Payment methods" rows={paymentMethods.map((m) => ({ key: m.method, label: m.method, sub: `${m.count} payments`, value: m.total }))} money={money} emptyIcon={Banknote} />
                <RankCard title="Expenses by category" rows={expensesByCategory.map((e) => ({ key: e.category, label: e.category, value: e.total }))} money={money} emptyIcon={TrendingDown} barClass="bg-amber-400" className="lg:col-span-2" />
            </div>
        </>
    );
}

function RankCard({ title, rows, money, emptyIcon, barClass = 'bg-brand-500', className }) {
    const max = Math.max(1, ...rows.map((r) => r.value));
    return (
        <Card className={className}>
            <CardHeader title={title} />
            {rows.length ? (
                <ul className="space-y-4 p-5">
                    {rows.map((r) => (
                        <li key={r.key}>
                            <div className="mb-1 flex items-baseline justify-between gap-3 text-sm">
                                <span className="min-w-0 truncate text-slate-700">
                                    {r.label}
                                    {r.sub ? <span className="ml-2 text-xs text-slate-400">{r.sub}</span> : null}
                                </span>
                                <span className="shrink-0 font-medium text-slate-900 tabular-nums">{money(r.value)}</span>
                            </div>
                            <div className="h-1.5 overflow-hidden rounded-full bg-slate-100">
                                <div className={cn('h-full rounded-full', barClass)} style={{ width: `${(r.value / max) * 100}%` }} />
                            </div>
                        </li>
                    ))}
                </ul>
            ) : (
                <EmptyState icon={emptyIcon} title="No data for this period" className="py-8" />
            )}
        </Card>
    );
}
