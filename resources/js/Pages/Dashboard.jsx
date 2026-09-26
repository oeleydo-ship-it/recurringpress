import { Link, usePage } from '@inertiajs/react';
import { AlertCircle, ArrowRight, Banknote, Clock, FilePlus2, FileText, Receipt, TrendingDown, Users, Wallet } from 'lucide-react';
import { Area, AreaChart, CartesianGrid, Cell, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import ClientOnly from '@/Components/ClientOnly';
import { Badge, Button, Card, CardHeader, EmptyState, PageHeader, StatCard, StatusBadge } from '@/Components/ui';
import { route } from '@/lib/route';
import { cn, formatDate, formatMoney, titleCase } from '@/lib/utils';

const STATUS_COLORS = {
    paid: '#10b981',
    partial: '#f97316',
    sent: '#0ea5e9',
    viewed: '#8b5cf6',
    overdue: '#ef4444',
    draft: '#94a3b8',
    cancelled: '#cbd5e1',
    refunded: '#a78bfa',
};

export default function Dashboard({ stats, chart, statusBreakdown, recentInvoices, overdueInvoices, usage, greeting }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency ?? 'USD';
    const dateFormat = auth.company?.date_format;
    const brand = auth.theme?.primary ?? '#4f46e5';
    const money = (v) => formatMoney(v, currency);

    return (
        <>
            <PageHeader
                title="Dashboard"
                description={`${greeting}, ${auth.user.name.split(' ')[0]}. Here's how ${auth.company?.name ?? 'your business'} is doing.`}
                actions={
                    <>
                        <Button href={route('quotations.create')} variant="secondary" icon={FileText}>
                            New quotation
                        </Button>
                        <Button href={route('invoices.create')} icon={FilePlus2}>
                            New invoice
                        </Button>
                    </>
                }
            />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Collected this month" value={money(stats.collected_month)} icon={Banknote} tone="green" trend={stats.collected_trend} hint="vs last month" href={route('payments.index')} />
                <StatCard label="Outstanding" value={money(stats.outstanding)} icon={Clock} tone="blue" hint={`${stats.open_invoices} open invoice${stats.open_invoices === 1 ? '' : 's'}`} href={route('invoices.index')} />
                <StatCard
                    label="Overdue"
                    value={money(stats.overdue_amount)}
                    icon={AlertCircle}
                    tone={stats.overdue_count ? 'red' : 'gray'}
                    hint={`${stats.overdue_count} invoice${stats.overdue_count === 1 ? '' : 's'} past due`}
                    href={route('invoices.index', { status: 'overdue' })}
                />
                <StatCard label="Expenses this month" value={money(stats.expenses_month)} icon={TrendingDown} tone="yellow" trend={stats.expenses_trend === null ? undefined : -stats.expenses_trend} hint="vs last month" href={route('expenses.index')} />
            </div>

            <div className="mt-6 grid gap-6 xl:grid-cols-3">
                <Card className="xl:col-span-2">
                    <CardHeader
                        title="Cash flow"
                        description="Payments received vs expenses, last 12 months"
                        actions={
                            <div className="hidden items-center gap-4 text-xs text-slate-500 sm:flex">
                                <Legend color={brand} label="Revenue" />
                                <Legend color="#f59e0b" label="Expenses" />
                            </div>
                        }
                    />
                    <div className="h-72 px-2 pt-4 pb-2">
                        <ClientOnly>
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={chart} margin={{ top: 5, right: 16, left: 0, bottom: 0 }}>
                                    <defs>
                                        <linearGradient id="rev" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stopColor={brand} stopOpacity={0.25} />
                                            <stop offset="100%" stopColor={brand} stopOpacity={0} />
                                        </linearGradient>
                                        <linearGradient id="exp" x1="0" y1="0" x2="0" y2="1">
                                            <stop offset="0%" stopColor="#f59e0b" stopOpacity={0.18} />
                                            <stop offset="100%" stopColor="#f59e0b" stopOpacity={0} />
                                        </linearGradient>
                                    </defs>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" vertical={false} />
                                    <XAxis dataKey="month" tickLine={false} axisLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                                    <YAxis tickLine={false} axisLine={false} width={64} tick={{ fontSize: 12, fill: '#64748b' }} tickFormatter={(v) => formatMoney(v, currency, { compact: true })} />
                                    <Tooltip content={<ChartTooltip currency={currency} />} cursor={{ stroke: '#cbd5e1', strokeDasharray: 4 }} />
                                    <Area type="monotone" dataKey="revenue" name="Revenue" stroke={brand} strokeWidth={2.5} fill="url(#rev)" activeDot={{ r: 5 }} />
                                    <Area type="monotone" dataKey="expenses" name="Expenses" stroke="#f59e0b" strokeWidth={2} fill="url(#exp)" activeDot={{ r: 4 }} />
                                </AreaChart>
                            </ResponsiveContainer>
                        </ClientOnly>
                    </div>
                </Card>

                <Card>
                    <CardHeader title="Invoices by status" description={`${statusBreakdown.reduce((s, r) => s + r.count, 0)} invoices in total`} />
                    {statusBreakdown.length ? (
                        <div className="px-5 pb-5">
                            <div className="relative h-44">
                                <ClientOnly>
                                    <ResponsiveContainer width="100%" height="100%">
                                        <PieChart>
                                            <Pie data={statusBreakdown} dataKey="count" nameKey="status" innerRadius="68%" outerRadius="95%" paddingAngle={2} strokeWidth={0}>
                                                {statusBreakdown.map((r) => (
                                                    <Cell key={r.status} fill={STATUS_COLORS[r.status] ?? '#94a3b8'} />
                                                ))}
                                            </Pie>
                                            <Tooltip formatter={(v, n) => [v, titleCase(n)]} />
                                        </PieChart>
                                    </ResponsiveContainer>
                                </ClientOnly>
                                <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                                    <span className="text-xs text-slate-500">Collected</span>
                                    <span className="text-base font-semibold text-slate-900">{formatMoney(stats.collected_total, currency, { compact: true })}</span>
                                </div>
                            </div>
                            <ul className="mt-4 space-y-2">
                                {statusBreakdown.map((r) => (
                                    <li key={r.status} className="flex items-center justify-between text-sm">
                                        <span className="flex items-center gap-2 text-slate-600">
                                            <span className="size-2.5 rounded-full" style={{ background: STATUS_COLORS[r.status] ?? '#94a3b8' }} />
                                            {titleCase(r.status)}
                                        </span>
                                        <span className="font-medium text-slate-900">
                                            {r.count} <span className="font-normal text-slate-400">· {money(r.amount)}</span>
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ) : (
                        <EmptyState icon={FileText} title="No invoices yet" description="Your invoice mix will appear here." />
                    )}
                </Card>
            </div>

            <div className="mt-6 grid gap-6 xl:grid-cols-3">
                <Card className="xl:col-span-2">
                    <CardHeader
                        title="Recent invoices"
                        actions={
                            <Link href={route('invoices.index')} className="inline-flex items-center gap-1 text-sm font-medium text-brand-600 hover:text-brand-700">
                                View all <ArrowRight className="size-4" />
                            </Link>
                        }
                    />
                    {recentInvoices.length ? (
                        <ul className="divide-y divide-slate-100">
                            {recentInvoices.map((inv) => (
                                <li key={inv.id}>
                                    <Link href={route('invoices.show', inv.id)} className="flex items-center gap-4 px-5 py-3.5 transition-colors hover:bg-slate-50">
                                        <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                            <FileText className="size-4" />
                                        </span>
                                        <span className="min-w-0 flex-1">
                                            <span className="block truncate text-sm font-medium text-slate-900">{inv.client ?? 'No client'}</span>
                                            <span className="block text-xs text-slate-500">
                                                {inv.invoice_number} · {formatDate(inv.issue_date, dateFormat)}
                                            </span>
                                        </span>
                                        <span className="text-right">
                                            <span className="block text-sm font-semibold text-slate-900">{money(inv.total)}</span>
                                            <StatusBadge status={inv.status} className="mt-1" />
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <EmptyState
                            icon={FilePlus2}
                            title="Create your first invoice"
                            description="Bill a client in under a minute — they can pay online straight from the email."
                            action={
                                <Button href={route('invoices.create')} icon={FilePlus2}>
                                    New invoice
                                </Button>
                            }
                        />
                    )}
                </Card>

                <div className="space-y-6">
                    <Card>
                        <CardHeader title="Needs attention" description="Overdue invoices" />
                        {overdueInvoices.length ? (
                            <ul className="divide-y divide-slate-100">
                                {overdueInvoices.map((inv) => (
                                    <li key={inv.id}>
                                        <Link href={route('invoices.show', inv.id)} className="flex items-center justify-between gap-3 px-5 py-3 hover:bg-slate-50">
                                            <span className="min-w-0">
                                                <span className="block truncate text-sm font-medium text-slate-900">{inv.client}</span>
                                                <span className="block text-xs text-red-600">Due {formatDate(inv.due_date, dateFormat)}</span>
                                            </span>
                                            <span className="text-sm font-semibold text-slate-900">{money(inv.balance)}</span>
                                        </Link>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <div className="flex items-center gap-3 px-5 py-6 text-sm text-slate-500">
                                <span className="flex size-8 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">✓</span>
                                Nothing overdue — nice work.
                            </div>
                        )}
                    </Card>

                    <Card>
                        <CardHeader title="Plan usage" actions={<Badge tone="brand">{usage.plan}</Badge>} />
                        <div className="space-y-4 px-5 py-4">
                            <UsageBar icon={Receipt} label="Invoices" data={usage.invoices} />
                            <UsageBar icon={Users} label="Clients" data={usage.clients} />
                            {auth.user.is_owner ? (
                                <Button href={route('settings.subscription')} variant="soft" size="sm" className="w-full" icon={Wallet}>
                                    Manage subscription
                                </Button>
                            ) : null}
                        </div>
                    </Card>
                </div>
            </div>
        </>
    );
}

function UsageBar({ icon: Icon, label, data }) {
    const pct = data.limit ? data.percent : 0;
    return (
        <div>
            <div className="mb-1.5 flex items-center justify-between text-sm">
                <span className="flex items-center gap-2 text-slate-600">
                    <Icon className="size-4 text-slate-400" /> {label}
                </span>
                <span className="font-medium text-slate-900">
                    {data.used}
                    <span className="text-slate-400"> / {data.limit ?? '∞'}</span>
                </span>
            </div>
            <div className="h-2 overflow-hidden rounded-full bg-slate-100">
                <div className={cn('h-full rounded-full transition-all', pct >= 90 ? 'bg-red-500' : pct >= 70 ? 'bg-amber-500' : 'bg-brand-500')} style={{ width: `${data.limit ? Math.max(pct, 2) : 4}%` }} />
            </div>
        </div>
    );
}

function Legend({ color, label }) {
    return (
        <span className="flex items-center gap-1.5">
            <span className="size-2.5 rounded-full" style={{ background: color }} />
            {label}
        </span>
    );
}

function ChartTooltip({ active, payload, currency }) {
    if (!active || !payload?.length) return null;
    const row = payload[0].payload;
    return (
        <div className="rounded-lg bg-white px-3 py-2 text-xs shadow-pop ring-1 ring-slate-900/5">
            <p className="mb-1 font-semibold text-slate-900">{row.label}</p>
            {payload.map((p) => (
                <p key={p.dataKey} className="flex items-center gap-2 text-slate-600">
                    <span className="size-2 rounded-full" style={{ background: p.stroke }} />
                    {p.name}: <span className="font-medium text-slate-900">{formatMoney(p.value, currency)}</span>
                </p>
            ))}
        </div>
    );
}

