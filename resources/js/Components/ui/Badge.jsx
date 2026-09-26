import { cn, titleCase } from '@/lib/utils';

const tones = {
    gray: 'bg-slate-100 text-slate-700 ring-slate-500/15',
    brand: 'bg-brand-50 text-brand-700 ring-brand-600/20',
    blue: 'bg-sky-50 text-sky-700 ring-sky-600/20',
    green: 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    yellow: 'bg-amber-50 text-amber-800 ring-amber-600/25',
    red: 'bg-red-50 text-red-700 ring-red-600/15',
    purple: 'bg-violet-50 text-violet-700 ring-violet-600/20',
    orange: 'bg-orange-50 text-orange-700 ring-orange-600/20',
};

const dots = {
    gray: 'bg-slate-400',
    brand: 'bg-brand-500',
    blue: 'bg-sky-500',
    green: 'bg-emerald-500',
    yellow: 'bg-amber-500',
    red: 'bg-red-500',
    purple: 'bg-violet-500',
    orange: 'bg-orange-500',
};

export default function Badge({ tone = 'gray', dot = false, className, children }) {
    return (
        <span className={cn('inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-xs font-medium whitespace-nowrap ring-1 ring-inset', tones[tone], className)}>
            {dot ? <span className={cn('size-1.5 rounded-full', dots[tone])} /> : null}
            {children}
        </span>
    );
}

/** Status → colour for invoices, quotations, payments, payouts, subscriptions, etc. */
const statusTones = {
    draft: 'gray',
    sent: 'blue',
    viewed: 'purple',
    pending: 'yellow',
    processing: 'blue',
    partial: 'orange',
    paid: 'green',
    completed: 'green',
    accepted: 'green',
    active: 'green',
    approved: 'green',
    published: 'green',
    overdue: 'red',
    rejected: 'red',
    failed: 'red',
    cancelled: 'gray',
    canceled: 'gray',
    expired: 'gray',
    refunded: 'purple',
    paused: 'yellow',
    trial: 'brand',
    inactive: 'gray',
    converted: 'purple',
};

export function StatusBadge({ status, label, className }) {
    const key = String(status ?? '').toLowerCase();
    return (
        <Badge tone={statusTones[key] ?? 'gray'} dot className={className}>
            {label ?? titleCase(key || 'unknown')}
        </Badge>
    );
}
