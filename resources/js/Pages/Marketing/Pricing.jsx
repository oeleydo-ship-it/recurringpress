import { Head, Link } from '@inertiajs/react';
import { CtaBand, PricingCards, SectionHeading } from '@/Components/marketing/shared';
import { route } from '@/lib/route';

export default function Pricing({ title, subtitle, plans, faq }) {
    return (
        <>
            <Head title="Pricing" />
            <section className="mx-auto max-w-7xl px-4 pt-16 pb-24 sm:px-6 lg:px-8">
                <SectionHeading eyebrow="Pricing" title={title} subtitle={subtitle} />
                <div className="mt-16">
                    <PricingCards plans={plans} />
                </div>
                {faq?.length ? (
                    <div className="mx-auto mt-24 max-w-3xl">
                        <h2 className="text-center text-2xl font-semibold text-slate-900">Billing questions</h2>
                        <dl className="mt-8 divide-y divide-slate-200 rounded-2xl bg-white ring-1 ring-slate-200">
                            {faq.map((f) => (
                                <div key={f.q} className="p-6">
                                    <dt className="font-semibold text-slate-900">{f.q}</dt>
                                    <dd className="mt-2 leading-relaxed text-slate-600">{f.a}</dd>
                                </div>
                            ))}
                        </dl>
                        <p className="mt-6 text-center text-sm text-slate-500">
                            More answers in our{' '}
                            <Link href={route('landing.faq')} className="font-semibold text-indigo-600">
                                FAQ
                            </Link>
                            .
                        </p>
                    </div>
                ) : null}
            </section>
            <CtaBand />
        </>
    );
}
