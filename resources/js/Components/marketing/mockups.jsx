import { BarChart3, Bell, Check, CreditCard, FileSignature, FileText, LayoutDashboard, Package, Receipt, Repeat, Search, Settings, Users, Wallet } from 'lucide-react';
import { cn } from '@/lib/utils';

/* ---------------------------------------------------------------------------
 * Product previews: plain markup that mirrors the app, so there are no
 * screenshots to keep in sync and it renders the same under SSR.
 * ------------------------------------------------------------------------- */

const NAV = [
    [LayoutDashboard, 'Dashboard', true],
    [FileText, 'Invoices'],
    [FileSignature, 'Quotations'],
    [Repeat, 'Recurring'],
    [Users, 'Clients'],
    [Package, 'Products'],
    [Wallet, 'Expenses'],
    [Receipt, 'Receipts'],
    [BarChart3, 'Reports'],
    [Settings, 'Settings'],
];

const KPIS = [
    ['Revenue (30d)', '$24,580', '+12.4%', 'bg-blue-50 text-blue-600'],
    ['Outstanding', '$8,140', '14 invoices', 'bg-amber-50 text-amber-600'],
    ['Overdue', '$1,920', '3 invoices', 'bg-rose-50 text-rose-600'],
    ['Paid this month', '$16,440', '+8.1%', 'bg-emerald-50 text-emerald-600'],
];

const BARS = [38, 52, 44, 61, 57, 70, 64, 78, 72, 86, 81, 94];

const STATUS = {
    Paid: 'bg-emerald-50 text-emerald-700',
    Sent: 'bg-blue-50 text-blue-700',
    Overdue: 'bg-rose-50 text-rose-700',
    Draft: 'bg-slate-100 text-slate-600',
};

const INVOICES = [
    ['INV-0142', 'Northwind Studio', '$2,400.00', 'Paid'],
    ['INV-0141', 'Bluebird Cafe', '$680.00', 'Sent'],
    ['INV-0140', 'Atlas Logistics', '$1,920.00', 'Overdue'],
    ['INV-0139', 'Greenleaf Dental', '$1,150.00', 'Draft'],
];

export function DashboardMock() {
    return (
        <div className="overflow-hidden rounded-2xl bg-white text-left shadow-xl shadow-blue-900/10 ring-1 ring-slate-200" aria-hidden="true">
            <div className="flex">
                <aside className="hidden w-48 shrink-0 border-r border-slate-100 p-4 md:block">
                    <div className="flex items-center gap-2">
                        <span className="flex size-7 items-center justify-center rounded-md bg-blue-600">
                            <FileText className="size-3.5 text-white" />
                        </span>
                        <span className="text-sm font-semibold text-slate-900">Northwind</span>
                    </div>
                    <ul className="mt-6 space-y-0.5">
                        {NAV.map(([Icon, label, active]) => (
                            <li key={label} className={cn('flex items-center gap-2.5 rounded-md px-2 py-1.5 text-xs', active ? 'bg-blue-50 font-medium text-blue-700' : 'text-slate-500')}>
                                <Icon className="size-3.5" /> {label}
                            </li>
                        ))}
                    </ul>
                </aside>
                <div className="min-w-0 flex-1 p-4 sm:p-6">
                    <div className="flex items-center justify-between gap-3">
                        <div>
                            <p className="text-sm font-semibold text-slate-900">Dashboard</p>
                            <p className="text-[11px] text-slate-400">Welcome back — here is how billing looks today.</p>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="hidden items-center gap-1.5 rounded-md bg-slate-50 px-2 py-1 text-[11px] text-slate-400 ring-1 ring-slate-200 sm:flex">
                                <Search className="size-3" /> Search
                            </span>
                            <Bell className="size-4 text-slate-400" />
                            <span className="flex size-6 items-center justify-center rounded-full bg-blue-600 text-[10px] font-semibold text-white">NS</span>
                        </div>
                    </div>

                    <div className="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
                        {KPIS.map(([label, value, delta, tone]) => (
                            <div key={label} className="rounded-xl p-3 ring-1 ring-slate-100">
                                <p className="truncate text-[11px] text-slate-500">{label}</p>
                                <p className="mt-1 text-base font-semibold text-slate-900 sm:text-lg">{value}</p>
                                <span className={cn('mt-1 inline-block rounded px-1.5 py-0.5 text-[10px] font-medium', tone)}>{delta}</span>
                            </div>
                        ))}
                    </div>

                    <div className="mt-4 grid gap-4 lg:grid-cols-[1.4fr_1fr]">
                        <div className="rounded-xl p-4 ring-1 ring-slate-100">
                            <div className="flex items-center justify-between">
                                <p className="text-xs font-semibold text-slate-900">Revenue</p>
                                <span className="text-[10px] text-slate-400">Last 12 months</span>
                            </div>
                            <div className="mt-4 flex h-28 items-end gap-1.5 sm:h-36">
                                {BARS.map((h, i) => (
                                    <span key={i} className={cn('flex-1 rounded-t', i === BARS.length - 1 ? 'bg-blue-600' : 'bg-blue-200')} style={{ height: `${h}%` }} />
                                ))}
                            </div>
                        </div>
                        <div className="rounded-xl p-4 ring-1 ring-slate-100">
                            <p className="text-xs font-semibold text-slate-900">Recent invoices</p>
                            <ul className="mt-3 divide-y divide-slate-100">
                                {INVOICES.map(([no, client, amount, status]) => (
                                    <li key={no} className="flex items-center justify-between gap-2 py-2 text-[11px]">
                                        <span className="min-w-0">
                                            <span className="block truncate font-medium text-slate-800">{client}</span>
                                            <span className="text-slate-400">{no}</span>
                                        </span>
                                        <span className="flex shrink-0 items-center gap-2">
                                            <span className="font-medium text-slate-800">{amount}</span>
                                            <span className={cn('rounded px-1.5 py-0.5 text-[10px] font-medium', STATUS[status])}>{status}</span>
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}

export function InvoiceMock() {
    return (
        <div className="relative" aria-hidden="true">
            <div className="rounded-2xl bg-white p-5 shadow-xl shadow-blue-900/10 ring-1 ring-slate-200 sm:p-7">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <span className="flex size-8 items-center justify-center rounded-lg bg-blue-600">
                            <FileText className="size-4 text-white" />
                        </span>
                        <p className="mt-3 text-sm font-semibold text-slate-900">Northwind Studio</p>
                        <p className="text-[11px] text-slate-400">hello@northwind.studio</p>
                    </div>
                    <div className="text-right">
                        <p className="text-xs font-semibold tracking-[0.2em] text-blue-600">INVOICE</p>
                        <p className="mt-1 text-[11px] text-slate-400">INV-0142 · Due Nov 14</p>
                    </div>
                </div>
                <div className="mt-6 grid grid-cols-[1fr_auto] gap-x-4 border-b border-slate-100 pb-2 text-[10px] font-semibold tracking-wider text-slate-400 uppercase">
                    <span>Item</span>
                    <span>Amount</span>
                </div>
                {[
                    ['Brand identity design', '$1,200.00'],
                    ['Website development', '$950.00'],
                    ['Monthly hosting & care', '$250.00'],
                ].map(([a, b]) => (
                    <div key={a} className="grid grid-cols-[1fr_auto] gap-x-4 border-b border-slate-100 py-2.5 text-xs">
                        <span className="truncate text-slate-700">{a}</span>
                        <span className="font-medium text-slate-900">{b}</span>
                    </div>
                ))}
                <div className="mt-4 flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                    <span className="text-xs text-slate-500">Total due</span>
                    <span className="text-lg font-semibold text-slate-900">$2,400.00</span>
                </div>
                <div className="mt-4 flex h-10 items-center justify-center gap-2 rounded-lg bg-blue-600 text-sm font-medium text-white">
                    <CreditCard className="size-4" /> Pay now
                </div>
            </div>
            <div className="absolute -right-1 -bottom-4 flex items-center gap-2.5 rounded-xl bg-white px-3 py-2 shadow-lg ring-1 ring-slate-200 sm:-right-4">
                <span className="flex size-7 items-center justify-center rounded-full bg-emerald-50">
                    <Check className="size-4 text-emerald-600" strokeWidth={3} />
                </span>
                <span>
                    <span className="block text-xs font-semibold text-slate-900">Payment received</span>
                    <span className="block text-[10px] text-slate-500">$2,400.00 · receipt sent</span>
                </span>
            </div>
        </div>
    );
}
