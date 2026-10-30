import { Head } from '@inertiajs/react';
import { ArrowRight, Check } from 'lucide-react';
import SmartLink from '@/Components/SmartLink';
import { DashboardMock, InvoiceMock } from '@/Components/marketing/mockups';
import { ChipMarquee, CtaBand, FeatureCard, PageHero, SectionHeading, TOOLKIT, btnBlue, btnOutline } from '@/Components/marketing/shared';
import { route } from '@/lib/route';

const HIGHLIGHTS = [
    {
        eyebrow: 'Invoices & quotations',
        title: 'Look professional on every document',
        text: 'Create invoices and quotations from your saved clients and products, apply taxes and discounts, and send a branded PDF by email. Clients accept quotes online and you convert them to invoices in one click.',
        bullets: ['Custom invoice templates', 'Quote to invoice in one click', 'Taxes, discounts and notes', 'Custom fields on every document'],
        mock: 'invoice',
    },
    {
        eyebrow: 'Payments & reports',
        title: 'Know exactly where your money is',
        text: 'Clients pay by card through Stripe and receipts go out on their own. The dashboard shows revenue, outstanding and overdue balances, and reports break it all down by client, period and currency.',
        bullets: ['Online payments via Stripe', 'Automatic receipts', 'Expense tracking', 'Multi-currency reports'],
        mock: 'dashboard',
    },
];

export default function Features({ title, subtitle, items, cta }) {
    return (
        <>
            <Head title="Features" />
            <PageHero eyebrow="Features" title={title} subtitle={subtitle}>
                <SmartLink href={route('landing.pricing')} className={btnOutline}>
                    See pricing
                </SmartLink>
                <SmartLink href={route('register')} className={btnBlue}>
                    Get started free <ArrowRight className="size-4" />
                </SmartLink>
            </PageHero>

            <ChipMarquee />

            {items?.length ? (
                <section className="mx-auto max-w-6xl px-4 py-20 sm:px-6 lg:px-8">
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {items.map((f) => (
                            <FeatureCard key={f.title} item={f} />
                        ))}
                    </div>
                </section>
            ) : null}

            {HIGHLIGHTS.map((h, i) => (
                <section key={h.title} className="pb-24">
                    <div className="mx-auto grid max-w-6xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                        <div className={i % 2 ? 'lg:order-2' : undefined}>
                            <div className="rounded-3xl bg-blue-50 p-3 ring-1 ring-blue-100 sm:p-6">{h.mock === 'invoice' ? <InvoiceMock /> : <DashboardMock />}</div>
                        </div>
                        <div>
                            <p className="text-xs font-semibold tracking-[0.14em] text-blue-600 uppercase">{h.eyebrow}</p>
                            <h2 className="mt-3 text-3xl font-semibold tracking-tight text-balance text-slate-900 sm:text-4xl">{h.title}</h2>
                            <p className="mt-4 text-base leading-relaxed text-slate-500">{h.text}</p>
                            <ul className="mt-6 grid gap-3 sm:grid-cols-2">
                                {h.bullets.map((b) => (
                                    <li key={b} className="flex items-center gap-2.5 text-sm text-slate-700">
                                        <span className="flex size-5 shrink-0 items-center justify-center rounded-full bg-blue-600">
                                            <Check className="size-3 text-white" strokeWidth={3} />
                                        </span>
                                        {b}
                                    </li>
                                ))}
                            </ul>
                        </div>
                    </div>
                </section>
            ))}

            <section className="border-t border-slate-100 py-24">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <SectionHeading eyebrow="And more" title="Built to fit the way you work" subtitle="The details that save you time every single week." />
                    <div className="mt-14 grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                        {TOOLKIT.map(([Icon, t, d]) => (
                            <div key={t} className="text-center">
                                <Icon className="mx-auto size-6 text-blue-600" />
                                <h3 className="mt-4 text-base font-semibold text-slate-900">{t}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-slate-500">{d}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <CtaBand title={cta.title} subtitle={cta.subtitle} />
        </>
    );
}
