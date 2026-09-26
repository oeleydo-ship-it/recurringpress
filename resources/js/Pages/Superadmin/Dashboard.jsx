import { Link } from '@inertiajs/react';
import { Activity, Building2, FileStack, FileText, Landmark, Repeat, TrendingUp, Users } from 'lucide-react';
import { Bar, BarChart, CartesianGrid, Line, ComposedChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import ClientOnly from '@/Components/ClientOnly';
import { Badge, Card, CardHeader, EmptyState, PageHeader, StatCard, StatusBadge } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatDate, formatMoney } from '@/lib/utils';

export default function SuperadminDashboard({ stats, series, recentCompanies }) {
    const usd = (v) => formatMoney(v, 'USD');

    return (
        <>
            <PageHeader title="Platform overview" description="Tenants, revenue and payouts across the whole platform." />

            <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <StatCard label="Companies" value={stats.companies} icon={Building2} tone="brand" hint={`+${stats.companies_30d} in the last 30 days`} href={route('superadmin.users.index')} />
                <StatCard label="MRR" value={usd(stats.mrr)} icon={Repeat} tone="green" hint={`${stats.paying} paying · ${stats.trials} on trial`} href={route('superadmin.reports.index')} />
                <StatCard label="Card volume (30d)" value={usd(stats.volume_30d)} icon={TrendingUp} tone="blue" hint="Through the platform gateway" />
                <StatCard
                    label="Pending payouts"
                    value={usd(stats.pending_payouts)}
                    icon={Landmark}
                    tone={stats.pending_payouts_count ? 'yellow' : 'gray'}
                    hint={`${stats.pending_payouts_count} request${stats.pending_payouts_count === 1 ? '' : 's'} waiting`}
                    href={route('superadmin.payouts.index', { status: 'pending' })}
                />
            </div>

            <div className="mt-6 grid gap-6 xl:grid-cols-3">
                <Card className="xl:col-span-2">
                    <CardHeader title="Growth" description="New companies and platform card volume, last 12 months" />
                    <div className="h-72 px-2 pt-4 pb-2">
                        <ClientOnly>
                            <ResponsiveContainer width="100%" height="100%">
                                <ComposedChart data={series} margin={{ top: 5, right: 12, left: 0, bottom: 0 }}>
                                    <CartesianGrid strokeDasharray="3 3" stroke="#e2e8f0" vertical={false} />
                                    <XAxis dataKey="month" tickLine={false} axisLine={false} tick={{ fontSize: 12, fill: '#64748b' }} />
                                    <YAxis yAxisId="v" tickLine={false} axisLine={false} width={60} tick={{ fontSize: 12, fill: '#64748b' }} tickFormatter={(v) => formatMoney(v, 'USD', { compact: true })} />
                                    <YAxis yAxisId="s" orientation="right" allowDecimals={false} tickLine={false} axisLine={false} width={32} tick={{ fontSize: 12, fill: '#64748b' }} />
                                    <Tooltip
                                        formatter={(v, n) => (n === 'Card volume' ? [usd(v), n] : [v, n])}
                                        labelFormatter={(_, p) => p?.[0]?.payload?.label}
                                        contentStyle={{ borderRadius: 12, border: '1px solid #e2e8f0', fontSize: 12 }}
                                    />
                                    <Bar yAxisId="v" dataKey="volume" name="Card volume" fill="#6366f1" radius={[4, 4, 0, 0]} maxBarSize={28} />
                                    <Line yAxisId="s" type="monotone" dataKey="signups" name="New companies" stroke="#0ea5e9" strokeWidth={2.5} dot={{ r: 3 }} />
                                </ComposedChart>
                            </ResponsiveContainer>
                        </ClientOnly>
                    </div>
                </Card>

                <Card>
                    <CardHeader title="At a glance" icon={Activity} />
                    <dl className="divide-y divide-slate-100 text-sm">
                        {[
                            [Users, 'Users', stats.users, route('superadmin.users.index')],
                            [FileText, 'Invoices issued', stats.invoices, null],
                            [FileStack, 'CMS pages', stats.pages, route('superadmin.pages.index')],
                            [FileText, 'Blog posts', stats.posts, route('superadmin.posts.index')],
                        ].map(([Icon, label, value, href]) => (
                            <div key={label} className="flex items-center justify-between px-5 py-3.5">
                                <dt className="flex items-center gap-2 text-slate-600">
                                    <Icon className="size-4 text-slate-400" /> {label}
                                </dt>
                                <dd className="font-semibold text-slate-900">
                                    {href ? (
                                        <Link href={href} className="hover:text-indigo-600">
                                            {value}
                                        </Link>
                                    ) : (
                                        value
                                    )}
                                </dd>
                            </div>
                        ))}
                    </dl>
                </Card>
            </div>

            <Card className="mt-6">
                <CardHeader
                    title="Newest companies"
                    actions={
                        <Link href={route('superadmin.users.index')} className="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                            All tenants →
                        </Link>
                    }
                />
                {recentCompanies.length ? (
                    <ul className="divide-y divide-slate-100">
                        {recentCompanies.map((c) => {
                            const row = (
                                <div className="flex items-center gap-4 px-5 py-3.5">
                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-sm font-semibold text-indigo-700">{c.name.slice(0, 1).toUpperCase()}</span>
                                    <div className="min-w-0 flex-1">
                                        <p className="truncate text-sm font-medium text-slate-900">{c.name}</p>
                                        <p className="truncate text-xs text-slate-500">
                                            {c.email ?? '—'} · {c.users} user{c.users === 1 ? '' : 's'} · joined {formatDate(c.created_at)}
                                        </p>
                                    </div>
                                    {c.plan ? <Badge tone="brand">{c.plan}</Badge> : null}
                                    <StatusBadge status={c.status} />
                                </div>
                            );
                            return (
                                <li key={c.id}>
                                    {c.owner_id ? (
                                        <Link href={route('superadmin.users.show', c.owner_id)} className="block hover:bg-slate-50">
                                            {row}
                                        </Link>
                                    ) : (
                                        row
                                    )}
                                </li>
                            );
                        })}
                    </ul>
                ) : (
                    <EmptyState icon={Building2} title="No companies yet" />
                )}
            </Card>
        </>
    );
}
