import { Link } from '@inertiajs/react';
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

export function SectionHeading({ eyebrow, title, subtitle, className }) {
    return (
        <div className={cn('mx-auto max-w-2xl text-center', className)}>
            {eyebrow ? <p className="text-sm font-semibold tracking-wide text-indigo-600 uppercase">{eyebrow}</p> : null}
            <h2 className="mt-2 text-3xl font-semibold tracking-tight text-balance text-slate-900 sm:text-4xl">{title}</h2>
            {subtitle ? <p className="mt-4 text-lg text-pretty text-slate-600">{subtitle}</p> : null}
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
                            <Check className="size-4 text-emerald-500" /> {b}
                        </li>
                    ))}
                </ul>
            ) : null}
        </div>
    );
}

export function PricingCards({ plans }) {
    return (
        <div className={cn('mx-auto grid max-w-5xl gap-6', plans.length >= 3 ? 'lg:grid-cols-3' : 'md:grid-cols-2 max-w-3xl')}>
            {plans.map((p) => {
                const popular = p.is_popular;
                const limits = [
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
                return (
                    <div key={p.id} className={cn('relative flex flex-col rounded-3xl p-8', popular ? 'bg-slate-900 text-white shadow-2xl ring-1 ring-slate-900' : 'bg-white ring-1 ring-slate-200')}>
                        {popular ? (
                            <span className="absolute -top-3 left-8 inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-indigo-500 to-sky-500 px-3 py-1 text-xs font-semibold text-white">
                                <Sparkles className="size-3" /> Most popular
                            </span>
                        ) : null}
                        <h3 className={cn('text-lg font-semibold', popular ? 'text-white' : 'text-slate-900')}>{p.name}</h3>
                        {p.description ? <p className={cn('mt-2 text-sm', popular ? 'text-slate-300' : 'text-slate-500')}>{p.description}</p> : null}
                        <p className="mt-6 flex items-baseline gap-1">
                            <span className="text-5xl font-semibold tracking-tight">{p.price > 0 ? formatMoney(p.price, 'USD').replace(/\.00$/, '') : 'Free'}</span>
                            {p.price > 0 ? <span className={popular ? 'text-slate-400' : 'text-slate-500'}>/{p.billing_cycle === 'yearly' ? 'yr' : 'mo'}</span> : null}
                        </p>
                        <ul className="mt-8 flex-1 space-y-3 text-sm">
                            {limits.map((l) => (
                                <li key={l} className="flex items-center gap-2.5">
                                    <Check className={cn('size-4 shrink-0', popular ? 'text-sky-400' : 'text-emerald-500')} />
                                    <span className={popular ? 'text-slate-200' : 'text-slate-600'}>{l}</span>
                                </li>
                            ))}
                        </ul>
                        <Button href={route('register', { plan: p.slug })} size="lg" className={cn('mt-8 w-full', popular ? 'bg-white text-slate-900 hover:bg-slate-100' : 'bg-indigo-600 text-white hover:bg-indigo-700')}>
                            {p.price > 0 ? (p.trial_days > 0 ? `Start ${p.trial_days}-day trial` : 'Get started') : 'Get started free'}
                        </Button>
                    </div>
                );
            })}
        </div>
    );
}

export function CtaBand({ title = 'Ready to get paid faster?', subtitle = 'Create your free account in under a minute. No credit card required.' }) {
    return (
        <section className="px-4 py-20 sm:px-6 lg:px-8">
            <div className="relative mx-auto max-w-5xl overflow-hidden rounded-3xl bg-slate-900 px-6 py-16 text-center shadow-2xl sm:px-16">
                <div
                    className="pointer-events-none absolute inset-0 opacity-60"
                    style={{ background: 'radial-gradient(50% 60% at 20% 0%, rgb(99 102 241 / 0.5), transparent 70%), radial-gradient(40% 60% at 90% 100%, rgb(14 165 233 / 0.4), transparent 70%)' }}
                />
                <h2 className="relative text-3xl font-semibold tracking-tight text-balance text-white sm:text-4xl">{title}</h2>
                <p className="relative mx-auto mt-4 max-w-xl text-lg text-slate-300">{subtitle}</p>
                <div className="relative mt-8 flex flex-wrap items-center justify-center gap-3">
                    <Button href={route('register')} size="lg" className="bg-white text-slate-900 hover:bg-slate-100">
                        Start for free
                    </Button>
                    <Link href={route('landing.pricing')} className="px-4 py-2 text-sm font-semibold text-white hover:text-slate-200">
                        See pricing →
                    </Link>
                </div>
            </div>
        </section>
    );
}
