import { Head, Link } from '@inertiajs/react';
import { CtaBand, FaqItem, PageHero, PricingCards, SectionHeading } from '@/Components/marketing/shared';
import { route } from '@/lib/route';

export default function Pricing({ title, subtitle, plans, faq }) {
    return (
        <>
            <Head title="Pricing" />
            <PageHero eyebrow="Pricing" title={title} subtitle={subtitle} />
            <section className="mx-auto max-w-6xl px-4 pb-24 sm:px-6 lg:px-8">
                <PricingCards plans={plans} />
            </section>
            {faq?.length ? (
                <section className="border-t border-slate-100 py-24">
                    <div className="mx-auto max-w-3xl px-4 sm:px-6">
                        <SectionHeading eyebrow="FAQ" title="Billing questions" />
                        <div className="mt-12 divide-y divide-slate-200 rounded-2xl bg-white ring-1 ring-slate-200">
                            {faq.map((f) => (
                                <FaqItem key={f.q} q={f.q} a={f.a} />
                            ))}
                        </div>
                        <p className="mt-8 text-center text-sm text-slate-500">
                            More answers in our{' '}
                            <Link href={route('landing.faq')} className="font-semibold text-blue-600 hover:text-blue-700">
                                FAQ
                            </Link>
                            .
                        </p>
                    </div>
                </section>
            ) : null}
            <CtaBand />
        </>
    );
}
