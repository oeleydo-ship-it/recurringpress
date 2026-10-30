import { Link } from '@inertiajs/react';
import { useState } from 'react';
import {
    BarChart3,
    Bell,
    Building2,
    Calendar,
    Check,
    Clock,
    Cloud,
    CreditCard,
    FileSignature,
    FileText,
    Globe,
    Headphones,
    Heart,
    Layers,
    Lock,
    Mail,
    Palette,
    PieChart,
    Receipt,
    Repeat,
    Rocket,
    Settings,
    Shield,
    Smartphone,
    Sparkles,
    Star,
    Users,
    Wallet,
    Zap,
} from 'lucide-react';
import { Button } from '@/Components/ui';
import { route } from '@/lib/route';
import { cn, formatMoney } from '@/lib/utils';

// Admin-configured features store Font Awesome class names; map the common ones.
const FA = {
    'file-invoice': FileText,
    'file-invoice-dollar': FileText,
    'file-alt': FileSignature,
    'file-lines': FileSignature,
    'file-signature': FileSignature,
    users: Users,
    'user-friends': Users,
    'credit-card': CreditCard,
    'money-bill': Wallet,
    'money-bill-wave': Wallet,
    wallet: Wallet,
    'chart-line': BarChart3,
    'chart-bar': BarChart3,
    'chart-pie': PieChart,
    sync: Repeat,
    'sync-alt': Repeat,
    redo: Repeat,
    'arrows-rotate': Repeat,
    repeat: Repeat,
    receipt: Receipt,
    palette: Palette,
    'paint-brush': Palette,
    bell: Bell,
    envelope: Mail,
    'paper-plane': Mail,
    lock: Lock,
    'shield-alt': Shield,
    shield: Shield,
    'shield-halved': Shield,
    cloud: Cloud,
    globe: Globe,
    mobile: Smartphone,
    'mobile-alt': Smartphone,
    clock: Clock,
    calendar: Calendar,
    'calendar-alt': Calendar,
    cog: Settings,
    cogs: Settings,
    gear: Settings,
    bolt: Zap,
    rocket: Rocket,
    star: Star,
    heart: Heart,
    headset: Headphones,
    building: Building2,
    layer: Layers,
    layers: Layers,
};

export function faIcon(name = '') {
    const key = String(name).split(/\s+/).find((c) => c.startsWith('fa-') && !['fa-solid', 'fa-regular', 'fa-light', 'fa-brands'].includes(c))?.slice(3);
    return FA[key] ?? Sparkles;
}

const accents = {
    sky: 'bg-sky-50 text-sky-600',
    blue: 'bg-blue-50 text-blue-600',
    green: 'bg-emerald-50 text-emerald-600',
    emerald: 'bg-emerald-50 text-emerald-600',
    purple: 'bg-violet-50 text-violet-600',
    violet: 'bg-violet-50 text-violet-600',
    orange: 'bg-orange-50 text-orange-600',
    amber: 'bg-amber-50 text-amber-600',
    yellow: 'bg-amber-50 text-amber-600',
    red: 'bg-rose-50 text-rose-600',
    rose: 'bg-rose-50 text-rose-600',
    pink: 'bg-pink-50 text-pink-600',
    indigo: 'bg-indigo-50 text-indigo-600',
    teal: 'bg-teal-50 text-teal-600',
};
export const accent = (color) => accents[color] ?? accents.indigo;

export function SectionHeading({ eyebrow, title, subtitle, className, dark = false }) {
    return (
        <div className={cn('mx-auto max-w-2xl text-center', className)}>
            {eyebrow ? <p className={cn('text-xs font-semibold tracking-[0.14em] uppercase', dark ? 'text-blue-100' : 'text-blue-600')}>{eyebrow}</p> : null}
            <h2 className={cn('mt-3 text-3xl font-semibold tracking-tight text-balance sm:text-4xl', dark ? 'text-white' : 'text-slate-900')}>{title}</h2>
            {subtitle ? <p className={cn('mt-4 text-base text-pretty sm:text-lg', dark ? 'text-blue-100' : 'text-slate-500')}>{subtitle}</p> : null}
        </div>
    );
}

export function FeatureCard({ item }) {
    const Icon = faIcon(item.icon);
    return (
        <div className="group rounded-2xl bg-white p-6 ring-1 ring-slate-200 transition hover:-translate-y-0.5 hover:shadow-lg hover:ring-slate-300">
            <span className={cn('flex size-11 items-center justify-center rounded-xl', accent(item.color))}>
                <Icon className="size-5" />
            </span>
            <h3 className="mt-5 text-base font-semibold text-slate-900">{item.title}</h3>
            <p className="mt-2 text-sm leading-relaxed text-slate-600">{item.description}</p>
            {item.bullets?.length ? (
                <ul className="mt-4 space-y-1.5">
                    {item.bullets.map((b) => (
                        <li key={b} className="flex items-center gap-2 text-sm text-slate-600">
                            <Check className="size-4 text-blue-600" /> {b}
                        </li>
                    ))}
                </ul>
            ) : null}
        </div>
    );
}

const planLimits = (p) =>
    [
        p.max_invoices != null ? `${p.max_invoices} invoices` : 'Unlimited invoices',
        p.max_clients != null ? `${p.max_clients} clients` : 'Unlimited clients',
        p.max_users != null ? `${p.max_users} team member${p.max_users === 1 ? '' : 's'}` : 'Unlimited team members',
        p.stripe_enabled && 'Online card payments',
        p.price > 0 && p.trial_days > 0 && `${p.trial_days}-day free trial`,
        p.custom_branding && 'Custom branding',
        p.white_label && 'White label',
        p.custom_domain && 'Custom domain',
        p.subdomain && 'Platform subdomain',
        ...(p.features ?? []),
    ].filter(Boolean);

/**
 * Plan cards in three tones: white, black (popular) and solid blue (the top-priced plan).
 * The Monthly/Annual switch only appears when plans exist for both billing cycles.
 */
export function PricingCards({ plans }) {
    const hasYearly = plans.some((p) => p.billing_cycle === 'yearly');
    const hasMonthly = plans.some((p) => p.billing_cycle !== 'yearly');
    const both = hasYearly && hasMonthly;
    const [cycle, setCycle] = useState('monthly');
    // Free plans show under both cycles.
    const shown = both ? plans.filter((p) => p.price <= 0 || (cycle === 'yearly') === (p.billing_cycle === 'yearly')) : plans;
    const top = shown.length > 1 ? shown.reduce((best, p) => (p.price > best.price ? p : best), shown[0]) : null;

    return (
        <div>
            {both ? (
                <div className="mb-12 flex justify-center">
                    <div className="inline-flex rounded-full bg-slate-100 p-1 text-sm font-medium" role="group" aria-label="Billing period">
                        {[
                            ['monthly', 'Monthly'],
                            ['yearly', 'Annual'],
                        ].map(([key, label]) => (
                            <button
                                key={key}
                                type="button"
                                aria-pressed={cycle === key}
                                onClick={() => setCycle(key)}
                                className={cn('rounded-full px-5 py-1.5 transition', cycle === key ? 'bg-white text-blue-600 shadow-sm' : 'text-slate-500 hover:text-slate-800')}
                            >
                                {label}
                            </button>
                        ))}
                    </div>
                </div>
            ) : null}
            <div className={cn('mx-auto grid gap-6', shown.length >= 3 ? 'max-w-6xl lg:grid-cols-3' : 'max-w-3xl md:grid-cols-2')}>
                {shown.map((p) => {
                    const tone = top && p.id === top.id && top.price > 0 ? 'blue' : p.is_popular ? 'dark' : 'light';
                    const light = tone === 'light';
                    return (
                        <div
                            key={p.id}
                            className={cn(
                                'flex flex-col rounded-2xl p-7',
                                tone === 'blue' && 'bg-blue-600 text-white shadow-xl shadow-blue-600/25',
                                tone === 'dark' && 'bg-slate-950 text-white shadow-xl',
                                light && 'bg-white ring-1 ring-slate-200',
                            )}
                        >
                            <div className="flex items-start justify-between gap-3">
                                <h3 className={cn('text-lg font-semibold', light ? 'text-slate-900' : 'text-white')}>{p.name}</h3>
                                {p.is_popular ? <span className={cn('rounded-md px-2 py-0.5 text-xs font-semibold', light ? 'bg-blue-50 text-blue-600' : 'bg-white text-slate-900')}>Popular</span> : null}
                            </div>
                            {p.description ? <p className={cn('mt-2 text-sm', light ? 'text-slate-500' : 'text-white/75')}>{p.description}</p> : null}
                            <p className="mt-6 flex items-baseline gap-1">
                                <span className="text-4xl font-semibold tracking-tight">{p.price > 0 ? formatMoney(p.price, 'USD') : 'Free'}</span>
                                {p.price > 0 ? <span className={cn('text-sm', light ? 'text-slate-500' : 'text-white/70')}>/{p.billing_cycle === 'yearly' ? 'year' : 'month'}</span> : null}
                            </p>
                            <Button
                                href={route('register', { plan: p.slug })}
                                size="lg"
                                className={cn('mt-6 w-full text-sm', light ? 'bg-white text-slate-800 ring-1 ring-slate-200 hover:bg-slate-50' : 'bg-white hover:bg-slate-100', tone === 'blue' ? 'text-blue-700' : 'text-slate-900')}
                            >
                                {p.price > 0 ? (p.trial_days > 0 ? `Start ${p.trial_days}-day trial` : 'Get started') : 'Get started free'}
                            </Button>
                            <p className={cn('mt-8 text-xs font-semibold tracking-wider uppercase', light ? 'text-slate-500' : 'text-white/70')}>{p.name} plan includes</p>
                            <ul className="mt-4 flex-1 space-y-3 text-sm">
                                {planLimits(p).map((l) => (
                                    <li key={l} className="flex items-center gap-2.5">
                                        <Check className={cn('size-4 shrink-0', light ? 'text-blue-600' : 'text-white')} />
                                        <span className={light ? 'text-slate-600' : 'text-white/90'}>{l}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

export function CtaBand({ title = 'Ready to get paid faster?', subtitle = 'Create your free account in under a minute. No credit card required.' }) {
    return (
        <section className="px-4 py-20 sm:px-6 lg:px-8">
            <div className="relative mx-auto max-w-5xl overflow-hidden rounded-3xl bg-blue-600 px-6 py-16 text-center shadow-xl shadow-blue-600/20 sm:px-16">
                <div className="pointer-events-none absolute inset-0" style={{ background: 'radial-gradient(50% 60% at 20% 0%, rgb(255 255 255 / 0.16), transparent 70%)' }} />
                <h2 className="relative text-3xl font-semibold tracking-tight text-balance text-white sm:text-4xl">{title}</h2>
                <p className="relative mx-auto mt-4 max-w-xl text-lg text-blue-100">{subtitle}</p>
                <div className="relative mt-8 flex flex-wrap items-center justify-center gap-3">
                    <Button href={route('register')} size="lg" className="bg-white text-blue-700 hover:bg-blue-50">
                        Start for free
                    </Button>
                    <Link href={route('landing.pricing')} className="px-4 py-2 text-sm font-semibold text-white hover:text-blue-100">
                        See pricing →
                    </Link>
                </div>
            </div>
        </section>
    );
}
