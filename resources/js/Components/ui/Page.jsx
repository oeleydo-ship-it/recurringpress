import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, ArrowUpRight, ArrowDownRight } from 'lucide-react';
import { cn, initials } from '@/lib/utils';

/** Page title bar with optional back link, description and action buttons. Also sets <title>. */
export function PageHeader({ title, description, back, actions, meta, children, className }) {
    return (
        <>
            <Head title={typeof title === 'string' ? title : undefined} />
            <div className={cn('mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between', className)}>
                <div className="min-w-0">
                    {back ? (
                        <Link href={back} className="mb-2 inline-flex items-center gap-1.5 text-sm font-medium text-slate-500 hover:text-slate-800">
                            <ArrowLeft className="size-4" /> Back
                        </Link>
                    ) : null}
                    <div className="flex flex-wrap items-center gap-3">
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-900">{title}</h1>
                        {meta}
                    </div>
                    {description ? <p className="mt-1 text-sm text-slate-500">{description}</p> : null}
                    {children}
                </div>
                {actions ? <div className="flex shrink-0 flex-wrap items-center gap-2">{actions}</div> : null}
            </div>
        </>
    );
}

export function EmptyState({ icon: Icon, title, description, action, className }) {
    return (
        <div className={cn('flex flex-col items-center justify-center px-6 py-14 text-center', className)}>
            {Icon ? (
                <span className="mb-4 flex size-12 items-center justify-center rounded-2xl bg-brand-50 text-brand-600 ring-8 ring-brand-50/50">
                    <Icon className="size-6" />
                </span>
            ) : null}
            <h3 className="text-sm font-semibold text-slate-900">{title}</h3>
            {description ? <p className="mt-1 max-w-sm text-sm text-slate-500">{description}</p> : null}
            {action ? <div className="mt-5">{action}</div> : null}
        </div>
    );
}

const statTones = {
    brand: 'bg-brand-50 text-brand-600',
    green: 'bg-emerald-50 text-emerald-600',
    yellow: 'bg-amber-50 text-amber-600',
    red: 'bg-red-50 text-red-600',
    blue: 'bg-sky-50 text-sky-600',
    purple: 'bg-violet-50 text-violet-600',
    gray: 'bg-slate-100 text-slate-600',
};

export function StatCard({ label, value, icon: Icon, tone = 'brand', hint, trend, href, className }) {
    const body = (
        <div className={cn('rounded-xl bg-white p-5 shadow-card ring-1 ring-slate-900/5 transition', href && 'hover:shadow-md hover:ring-slate-900/10', className)}>
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="truncate text-sm font-medium text-slate-500">{label}</p>
                    <p className="mt-2 truncate text-2xl font-semibold tracking-tight text-slate-900">{value}</p>
                </div>
                {Icon ? (
                    <span className={cn('flex size-10 shrink-0 items-center justify-center rounded-xl', statTones[tone])}>
                        <Icon className="size-5" />
                    </span>
                ) : null}
            </div>
            {hint || trend !== undefined ? (
                <div className="mt-3 flex items-center gap-2 text-xs">
                    {trend !== undefined && trend !== null ? (
                        <span className={cn('inline-flex items-center gap-0.5 font-semibold', trend >= 0 ? 'text-emerald-600' : 'text-red-600')}>
                            {trend >= 0 ? <ArrowUpRight className="size-3.5" /> : <ArrowDownRight className="size-3.5" />}
                            {Math.abs(trend).toFixed(1)}%
                        </span>
                    ) : null}
                    {hint ? <span className="text-slate-500">{hint}</span> : null}
                </div>
            ) : null}
        </div>
    );
    return href ? <Link href={href}>{body}</Link> : body;
}

export function Avatar({ src, name, size = 'md', className }) {
    const sizes = { xs: 'size-6 text-[10px]', sm: 'size-8 text-xs', md: 'size-9 text-sm', lg: 'size-12 text-base', xl: 'size-16 text-lg' };
    if (src) {
        return <img src={src} alt={name ?? ''} className={cn('shrink-0 rounded-full object-cover ring-2 ring-white', sizes[size], className)} />;
    }
    return (
        <span className={cn('inline-flex shrink-0 items-center justify-center rounded-full bg-brand-100 font-semibold text-brand-700 ring-2 ring-white', sizes[size], className)}>
            {initials(name) || '?'}
        </span>
    );
}

/** Horizontal filter/tab strip that links to URLs (server-side filtering). */
export function LinkTabs({ tabs, className }) {
    return (
        <div className={cn('flex gap-1 overflow-x-auto scrollbar-thin', className)}>
            {tabs.map((t) => (
                <Link
                    key={t.label}
                    href={t.href}
                    preserveScroll
                    className={cn(
                        'inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-medium whitespace-nowrap transition-colors',
                        t.active ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
                    )}
                >
                    {t.label}
                    {t.count !== undefined ? (
                        <span className={cn('rounded-full px-1.5 text-xs', t.active ? 'bg-white/20' : 'bg-slate-100 text-slate-500')}>{t.count}</span>
                    ) : null}
                </Link>
            ))}
        </div>
    );
}
