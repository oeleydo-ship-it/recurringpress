import { Head } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    Bell,
    Check,
    CreditCard,
    FileSignature,
    FileText,
    Globe,
    LayoutDashboard,
    ListPlus,
    Package,
    Palette,
    Receipt,
    Repeat,
    Search,
    Settings,
    Sparkles,
    Users,
    Wallet,
} from 'lucide-react';
import SmartLink from '@/Components/SmartLink';
import { PricingCards, SectionHeading, accent, faIcon } from '@/Components/marketing/shared';
import { route } from '@/lib/route';
import { cn } from '@/lib/utils';

const btn = 'inline-flex h-10 items-center justify-center gap-1.5 rounded-lg px-4 text-sm font-medium transition-colors';
const btnBlue = cn(btn, 'bg-blue-600 text-white shadow-sm shadow-blue-600/25 hover:bg-blue-700');
const btnOutline = cn(btn, 'bg-white text-slate-700 ring-1 ring-slate-200 hover:bg-slate-50 hover:text-slate-900');

const HERO_POINTS = [
    ['Invoices & quotes', 'Branded PDFs sent by email with a secure link your clients can open anywhere.'],
    ['Paid online', 'Clients pay by card through Stripe and receipts go out automatically.'],
    ['Recurring billing', 'Retainers and subscriptions invoice themselves on the schedule you set.'],
];

const CHIPS = [
    'Invoices',
    'Quotations',
    'Recurring billing',
    'Clients',
    'Products & services',
    'Expenses',
    'Stripe payments',
    'Receipts',
    'Reports',
    'Multi-currency',
    'Invoice templates',
    'Custom fields',
    'Ask AI',
    'Custom domains',
    'Payment reminders',
    'Tax rates',
];

const TOOLKIT = [
    [Palette, 'Custom templates', 'Pick a template, add your logo and colours, and every PDF matches your brand.'],
    [Globe, 'Multi-currency', 'Bill each client in their own currency with the right symbols and formats.'],
    [ListPlus, 'Custom fields', 'Add your own fields to invoices, quotes, clients, products and expenses.'],
    [Sparkles, 'Ask AI', 'Ask about your numbers or have AI draft an invoice, quote or expense for you.'],
];

const WORKFLOW = [
    {
        icon: FileSignature,
        title: 'Quotes that convert',
        text: 'Send a quotation, let the client accept it online, then turn it into an invoice.',
        bullets: ['One-click quote to invoice', 'PDF and email delivery'],
    },
    {
        icon: Repeat,
        title: 'Recurring on autopilot',
        text: 'Set the schedule once and invoices are created and sent for you, every time.',
        bullets: ['Weekly, monthly or yearly', 'Automatic payment reminders'],
    },
    {
        icon: CreditCard,
        title: 'Faster payments',
        text: 'A Pay now button on every invoice takes clients straight to a secure checkout.',
        bullets: ['Card payments via Stripe', 'Receipts issued instantly'],
    },
];

const STEPS = [
    ['Create your account', 'Sign up free, then add your logo, currency, tax rates and invoice template.'],
    ['Add clients & products', 'Import or add your clients and the products or services you sell.'],
    ['Send & get paid', 'Email invoices and quotes, take card payments and watch the reports update.'],
];

export default function Home({ hero, trust, features, pricing, testimonials }) {
    return (
        <>
            <Head title={hero.title} />

            {/* Hero */}
            <section className="relative">
                <div className="mx-auto max-w-6xl px-4 pt-16 text-center sm:px-6 lg:px-8 lg:pt-24">
                    {hero.eyebrow ? <p className="text-xs font-semibold tracking-[0.14em] text-blue-600 uppercase">{hero.eyebrow}</p> : null}
                    <h1 className="mx-auto mt-4 max-w-3xl text-4xl leading-[1.1] font-semibold tracking-tight text-balance text-slate-900 sm:text-5xl">{hero.title}</h1>
                    <p className="mx-auto mt-5 max-w-2xl text-base leading-relaxed text-pretty text-slate-500 sm:text-lg">{hero.subtitle}</p>
                    <div className="mt-8 flex flex-wrap items-center justify-center gap-3">
                        <SmartLink href="#pricing" className={btnOutline}>
                            See pricing
                        </SmartLink>
                        <SmartLink href={hero.cta_url} className={btnBlue}>
                            {hero.cta_text} <ArrowRight className="size-4" />
                        </SmartLink>
                    </div>

                    <dl className="mx-auto mt-14 grid max-w-4xl gap-6 text-left sm:grid-cols-3 sm:gap-10">
                        {HERO_POINTS.map(([title, text]) => (
                            <div key={title} className="text-sm leading-relaxed text-slate-500">
                                <dt className="inline font-semibold text-slate-900">{title} — </dt>
                                <dd className="inline">{text}</dd>
                            </div>
                        ))}
                    </dl>
                </div>

                <div className="mx-auto mt-14 max-w-6xl px-4 sm:px-6 lg:px-8">
                    <div className="rounded-3xl bg-blue-50 p-2 ring-1 ring-blue-100 sm:p-4 lg:p-6">
                        <DashboardMock />
                    </div>
                </div>

                {trust.badges?.length ? (
                    <div className="mx-auto mt-12 max-w-5xl px-4 text-center">
                        <p className="text-sm text-slate-500">{trust.text}</p>
                        <div className="mt-5 flex flex-wrap items-center justify-center gap-x-10 gap-y-3">
                            {trust.badges.map((b) => (
                                <span key={b.name} className="text-base font-semibold tracking-tight text-slate-400">
                                    {b.name}
                                </span>
                            ))}
                        </div>
                    </div>
                ) : null}
            </section>

            {/* Toolkit: chips + four features */}
            <section className="py-24">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <SectionHeading
                        eyebrow="All in one"
                        title="Your whole billing workflow in one place"
                        subtitle="From the first quote to the final receipt, without juggling spreadsheets and separate tools."
                    />
                </div>
                <div className="relative mt-10 overflow-hidden [mask-image:linear-gradient(to_right,transparent,black_12%,black_88%,transparent)]">
                    <div className="flex w-max animate-marquee gap-3 motion-reduce:w-full motion-reduce:animate-none motion-reduce:flex-wrap motion-reduce:justify-center">
                        {[0, 1].map((copy) => (
                            <ul key={copy} className={cn('flex shrink-0 gap-3', copy === 1 && 'motion-reduce:hidden')} aria-hidden={copy === 1 || undefined}>
                                {CHIPS.map((c) => (
                                    <li key={c} className="rounded-full bg-white px-4 py-1.5 text-sm whitespace-nowrap text-slate-600 ring-1 ring-slate-200">
                                        {c}
                                    </li>
                                ))}
                            </ul>
                        ))}
                    </div>
                </div>
                <div className="mx-auto mt-16 grid max-w-6xl gap-10 px-4 sm:grid-cols-2 sm:px-6 lg:grid-cols-4 lg:px-8">
                    {TOOLKIT.map(([Icon, title, text]) => (
                        <div key={title} className="text-center">
                            <Icon className="mx-auto size-6 text-blue-600" />
                            <h3 className="mt-4 text-base font-semibold text-slate-900">{title}</h3>
                            <p className="mt-2 text-sm leading-relaxed text-slate-500">{text}</p>
                        </div>
                    ))}
                </div>
            </section>

            {/* Split: invoice preview + workflow */}
            <section className="pb-24">
                <div className="mx-auto grid max-w-6xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-[1.15fr_1fr] lg:px-8">
                    <div className="rounded-3xl bg-blue-50 p-3 ring-1 ring-blue-100 sm:p-6">
                        <InvoiceMock />
                    </div>
                    <div>
                        <p className="text-xs font-semibold tracking-[0.14em] text-blue-600 uppercase">Quote to cash</p>
                        <h2 className="mt-3 text-3xl font-semibold tracking-tight text-balance text-slate-900 sm:text-4xl">Send a quote today. Get paid tomorrow.</h2>
                        <p className="mt-4 text-base leading-relaxed text-slate-500">
                            Professional invoices and quotations in a couple of clicks. Your clients see a clean, branded document with a Pay now button, and you see every view, payment and overdue balance as it happens.
                        </p>
                        <div className="mt-8 flex flex-wrap gap-3">
                            <SmartLink href={hero.cta_url} className={btnBlue}>
                                {hero.cta_text}
                            </SmartLink>
                            <SmartLink href={route('landing.features')} className={btnOutline}>
                                See all features
                            </SmartLink>
                        </div>
                    </div>
                </div>
                <div className="mx-auto mt-16 grid max-w-6xl gap-10 px-4 sm:px-6 md:grid-cols-3 lg:px-8">
                    {WORKFLOW.map(({ icon: Icon, title, text, bullets }) => (
                        <div key={title}>
                            <h3 className="flex items-center gap-2 text-base font-semibold text-slate-900">
                                <Icon className="size-5 text-blue-600" /> {title}
                            </h3>
                            <p className="mt-2 text-sm leading-relaxed text-slate-500">{text}</p>
                            <ul className="mt-5 space-y-2.5">
                                {bullets.map((b) => (
                                    <li key={b} className="flex items-center gap-2.5 text-sm text-slate-700">
                                        <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-blue-600">
                                            <Check className="size-3 text-white" strokeWidth={3} />
                                        </span>
                                        {b}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    ))}
                </div>
            </section>

            {/* Features (editable) on a solid blue band */}
            {features.items?.length ? (
                <section id="features" className="scroll-mt-16 bg-blue-600 py-20 sm:py-24">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                        <SectionHeading dark eyebrow="Features" title={features.title} subtitle={features.subtitle} />
                        <div className="mt-14 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                            {features.items.map((f) => {
                                const Icon = faIcon(f.icon);
                                return (
                                    <div key={f.title} className="rounded-2xl bg-blue-50 p-6 ring-1 ring-white/40">
                                        <Icon className="size-6 text-blue-600" />
                                        <h3 className="mt-4 text-base font-semibold text-slate-900">{f.title}</h3>
                                        <p className="mt-2 text-sm leading-relaxed text-slate-600">{f.description}</p>
                                    </div>
                                );
                            })}
                        </div>
                    </div>
                </section>
            ) : null}

            {/* How it works */}
            <section className="py-24">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <SectionHeading eyebrow="How it works" title="Up and running in minutes" subtitle="Three simple steps from sign-up to your first paid invoice." />
                    <ol className="mt-16 grid gap-10 md:grid-cols-3 md:gap-0 md:divide-x md:divide-slate-200">
                        {STEPS.map(([title, text], i) => (
                            <li key={title} className="md:px-8 md:first:pl-0 md:last:pr-0">
                                <span className="flex size-8 items-center justify-center rounded-full bg-blue-50 text-sm font-semibold text-blue-600 ring-1 ring-blue-100">{i + 1}</span>
                                <h3 className="mt-5 text-base font-semibold text-slate-900">{title}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-slate-500">{text}</p>
                            </li>
                        ))}
                    </ol>
                </div>
            </section>

            {/* Pricing */}
            {pricing.plans?.length ? (
                <section id="pricing" className="scroll-mt-16 pb-24">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                        <SectionHeading eyebrow="Pricing" title={pricing.title} subtitle={pricing.subtitle} />
                        <div className="mt-10">
                            <PricingCards plans={pricing.plans} />
                        </div>
                    </div>
                </section>
            ) : null}

            {/* Testimonials */}
            {testimonials.items?.length ? (
                <section className="pb-24">
                    <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                        <SectionHeading eyebrow="Testimonials" title={testimonials.title} subtitle={testimonials.subtitle} />
                        <div className="mt-14 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {testimonials.items.map((t) => (
                                <figure key={t.name} className="flex flex-col rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                                    <span className={cn('flex size-11 items-center justify-center rounded-full text-sm font-semibold', accent(t.color))}>
                                        {t.initials || t.name?.slice(0, 1)}
                                    </span>
                                    <blockquote className="mt-5 flex-1 text-sm leading-relaxed text-slate-600">“{t.quote}”</blockquote>
                                    <figcaption className="mt-5 text-sm">
                                        <span className="font-semibold text-slate-900">— {t.name}</span>
                                        {t.role ? <span className="text-slate-500">, {t.role}</span> : null}
                                    </figcaption>
                                </figure>
                            ))}
                        </div>
                    </div>
                </section>
            ) : null}
        </>
    );
}

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

function DashboardMock() {
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

function InvoiceMock() {
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
