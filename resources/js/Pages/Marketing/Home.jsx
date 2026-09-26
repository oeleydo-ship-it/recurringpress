import { Head, usePage } from '@inertiajs/react';
import { ArrowRight, BadgeCheck, CheckCircle2, CreditCard, Eye, Send, Star } from 'lucide-react';
import SmartLink from '@/Components/SmartLink';
import { CtaBand, FeatureCard, PricingCards, SectionHeading, accent } from '@/Components/marketing/shared';
import { cn } from '@/lib/utils';

export default function Home({ hero, trust, features, pricing, testimonials }) {
    const { site } = usePage().props;

    return (
        <>
            <Head title={hero.title} />

            {/* Hero */}
            <section className="relative overflow-hidden">
                <div
                    className="pointer-events-none absolute inset-x-0 -top-40 h-[640px]"
                    style={{ background: 'radial-gradient(45% 50% at 30% 30%, rgb(99 102 241 / 0.16), transparent 70%), radial-gradient(35% 45% at 80% 20%, rgb(14 165 233 / 0.14), transparent 70%)' }}
                />
                <div className="relative mx-auto grid max-w-7xl items-center gap-16 px-4 pt-14 pb-20 sm:px-6 lg:grid-cols-2 lg:px-8 lg:pt-20 lg:pb-28">
                    <div>
                        <span className="inline-flex items-center gap-2 rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-600 shadow-sm ring-1 ring-slate-200">
                            <span className="size-1.5 rounded-full bg-emerald-500" /> Invoices, quotes & payments in one place
                        </span>
                        <h1 className="mt-6 text-4xl leading-[1.08] font-semibold tracking-tight text-balance text-slate-900 sm:text-5xl lg:text-6xl">{hero.title}</h1>
                        <p className="mt-6 max-w-xl text-lg leading-relaxed text-pretty text-slate-600">{hero.subtitle}</p>
                        <div className="mt-10 flex flex-wrap items-center gap-4">
                            <SmartLink href={hero.cta_url} className="inline-flex h-12 items-center gap-2 rounded-xl bg-indigo-600 px-6 text-base font-semibold text-white shadow-lg shadow-indigo-600/25 transition hover:bg-indigo-700">
                                {hero.cta_text} <ArrowRight className="size-4" />
                            </SmartLink>
                            <SmartLink href="#pricing" className="text-sm font-semibold text-slate-700 hover:text-slate-900">
                                View pricing
                            </SmartLink>
                        </div>
                        <dl className="mt-12 flex gap-10">
                            {[
                                ['12+', 'Invoice designs'],
                                ['30s', 'To your first invoice'],
                                ['0%', 'Hidden fees'],
                            ].map(([n, l]) => (
                                <div key={l}>
                                    <dt className="text-2xl font-semibold text-slate-900">{n}</dt>
                                    <dd className="text-sm text-slate-500">{l}</dd>
                                </div>
                            ))}
                        </dl>
                    </div>
                    <HeroMock />
                </div>
            </section>

            {/* Trust */}
            {trust.badges?.length ? (
                <section className="border-y border-slate-100 bg-slate-50/60 py-10">
                    <p className="text-center text-sm font-medium text-slate-500">{trust.text}</p>
                    <div className="mx-auto mt-6 flex max-w-5xl flex-wrap items-center justify-center gap-x-12 gap-y-4 px-4">
                        {trust.badges.map((b) => (
                            <span key={b.name} className="text-lg font-semibold tracking-tight text-slate-400">
                                {b.name}
                            </span>
                        ))}
                    </div>
                </section>
            ) : null}

            {/* Features */}
            {features.items?.length ? (
                <section id="features" className="mx-auto max-w-7xl px-4 py-24 sm:px-6 lg:px-8">
                    <SectionHeading eyebrow="Features" title={features.title} subtitle={features.subtitle} />
                    <div className="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {features.items.map((f) => (
                            <FeatureCard key={f.title} item={f} />
                        ))}
                    </div>
                </section>
            ) : null}

            {/* How it works */}
            <section className="bg-slate-50 py-24">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <SectionHeading eyebrow="How it works" title="From quote to paid in three steps" />
                    <div className="mt-16 grid gap-8 md:grid-cols-3">
                        {[
                            [Send, 'Send', 'Create a branded invoice or quotation and email it with a secure link.'],
                            [Eye, 'Track', 'See when it is viewed, accepted or overdue — reminders go out automatically.'],
                            [CreditCard, 'Get paid', 'Clients pay by card or bank transfer. Receipts are issued instantly.'],
                        ].map(([Icon, title, text], i) => (
                            <div key={title} className="relative rounded-2xl bg-white p-8 ring-1 ring-slate-200">
                                <span className="absolute top-8 right-8 text-5xl font-bold text-slate-100">{i + 1}</span>
                                <span className="flex size-12 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-lg shadow-indigo-600/25">
                                    <Icon className="size-5" />
                                </span>
                                <h3 className="mt-6 text-lg font-semibold text-slate-900">{title}</h3>
                                <p className="mt-2 text-slate-600">{text}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* Pricing */}
            {pricing.plans?.length ? (
                <section id="pricing" className="mx-auto max-w-7xl scroll-mt-20 px-4 py-24 sm:px-6 lg:px-8">
                    <SectionHeading eyebrow="Pricing" title={pricing.title} subtitle={pricing.subtitle} />
                    <div className="mt-16">
                        <PricingCards plans={pricing.plans} />
                    </div>
                </section>
            ) : null}

            {/* Testimonials */}
            {testimonials.items?.length ? (
                <section className="bg-slate-50 py-24">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <SectionHeading eyebrow="Testimonials" title={testimonials.title} subtitle={testimonials.subtitle} />
                        <div className="mt-16 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {testimonials.items.map((t) => (
                                <figure key={t.name} className="flex flex-col rounded-2xl bg-white p-8 ring-1 ring-slate-200">
                                    <div className="flex gap-0.5 text-amber-400">
                                        {[1, 2, 3, 4, 5].map((i) => (
                                            <Star key={i} className="size-4 fill-current" />
                                        ))}
                                    </div>
                                    <blockquote className="mt-4 flex-1 leading-relaxed text-slate-700">“{t.quote}”</blockquote>
                                    <figcaption className="mt-6 flex items-center gap-3">
                                        <span className={cn('flex size-10 items-center justify-center rounded-full text-sm font-semibold', accent(t.color))}>{t.initials}</span>
                                        <span>
                                            <span className="block text-sm font-semibold text-slate-900">{t.name}</span>
                                            <span className="block text-sm text-slate-500">{t.role}</span>
                                        </span>
                                    </figcaption>
                                </figure>
                            ))}
                        </div>
                    </div>
                </section>
            ) : null}

            <CtaBand title={`Start invoicing with ${site?.name ?? 'us'} today`} />
        </>
    );
}

/** Decorative product preview (pure markup — no screenshots to keep in sync). */
function HeroMock() {
    return (
        <div className="relative mx-auto w-full max-w-lg" aria-hidden="true">
            <div className="absolute -inset-4 rounded-[2rem] bg-gradient-to-tr from-indigo-500/10 to-sky-400/10 blur-2xl" />
            <div className="relative rounded-2xl bg-white p-6 shadow-2xl ring-1 ring-slate-900/5">
                <div className="flex items-start justify-between">
                    <div>
                        <div className="h-3 w-24 rounded bg-slate-900" />
                        <div className="mt-2 h-2 w-32 rounded bg-slate-200" />
                    </div>
                    <div className="text-right">
                        <p className="text-xs font-semibold tracking-[0.2em] text-indigo-600">INVOICE</p>
                        <p className="mt-1 text-xs text-slate-400">INV-00042</p>
                    </div>
                </div>
                <div className="mt-8 space-y-3">
                    {[
                        ['Brand identity design', '$2,400.00'],
                        ['Website development', '$4,800.00'],
                        ['Monthly retainer', '$1,200.00'],
                    ].map(([a, b]) => (
                        <div key={a} className="flex items-center justify-between border-b border-slate-100 pb-3 text-sm">
                            <span className="text-slate-700">{a}</span>
                            <span className="font-medium text-slate-900">{b}</span>
                        </div>
                    ))}
                </div>
                <div className="mt-6 flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3">
                    <span className="text-sm text-slate-500">Total due</span>
                    <span className="text-xl font-semibold text-slate-900">$8,400.00</span>
                </div>
                <div className="mt-4 flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 text-sm font-semibold text-white">
                    <CreditCard className="size-4" /> Pay now
                </div>
            </div>
            <div className="absolute -bottom-6 -left-6 flex items-center gap-3 rounded-2xl bg-white px-4 py-3 shadow-xl ring-1 ring-slate-900/5">
                <span className="flex size-9 items-center justify-center rounded-full bg-emerald-50 text-emerald-600">
                    <CheckCircle2 className="size-5" />
                </span>
                <span>
                    <span className="block text-sm font-semibold text-slate-900">Payment received</span>
                    <span className="block text-xs text-slate-500">$8,400.00 · just now</span>
                </span>
            </div>
            <div className="absolute -top-5 -right-4 flex items-center gap-2 rounded-full bg-white px-3 py-2 text-xs font-medium text-slate-700 shadow-lg ring-1 ring-slate-900/5">
                <BadgeCheck className="size-4 text-indigo-600" /> Viewed by client
            </div>
        </div>
    );
}
