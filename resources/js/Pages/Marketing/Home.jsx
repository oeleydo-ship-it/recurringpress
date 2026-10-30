import { Head } from '@inertiajs/react';
import { ArrowRight, Check, CreditCard, FileSignature, Repeat } from 'lucide-react';
import SmartLink from '@/Components/SmartLink';
import { DashboardMock, InvoiceMock } from '@/Components/marketing/mockups';
import { ChipMarquee, PricingCards, SectionHeading, TOOLKIT, accent, btnBlue, btnOutline, faIcon } from '@/Components/marketing/shared';
import { route } from '@/lib/route';
import { cn } from '@/lib/utils';

const HERO_POINTS = [
    ['Invoices & quotes', 'Branded PDFs sent by email with a secure link your clients can open anywhere.'],
    ['Paid online', 'Clients pay by card through Stripe and receipts go out automatically.'],
    ['Recurring billing', 'Retainers and subscriptions invoice themselves on the schedule you set.'],
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
                <ChipMarquee className="mt-10" />
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
