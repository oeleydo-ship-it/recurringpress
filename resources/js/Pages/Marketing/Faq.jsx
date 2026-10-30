import { Head, Link } from '@inertiajs/react';
import { CtaBand, FaqItem, PageHero } from '@/Components/marketing/shared';
import { route } from '@/lib/route';

export default function Faq({ groups }) {
    return (
        <>
            <Head title="Frequently asked questions" />
            <PageHero eyebrow="FAQ" title="Frequently asked questions" subtitle="Everything you need to know. Can't find an answer? Get in touch." />
            <section className="mx-auto max-w-3xl px-4 pb-24 sm:px-6">
                <div className="space-y-12">
                    {groups.map((g) => (
                        <div key={g.label}>
                            <h2 className="mb-4 text-xs font-semibold tracking-[0.14em] text-blue-600 uppercase">{g.label}</h2>
                            <div className="divide-y divide-slate-200 rounded-2xl bg-white ring-1 ring-slate-200">
                                {g.items.map((item) => (
                                    <FaqItem key={item.q} q={item.q} a={item.a} />
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
                <p className="mt-12 text-center text-sm text-slate-500">
                    Still have questions?{' '}
                    <Link href={route('landing.contact')} className="font-semibold text-blue-600 hover:text-blue-700">
                        Contact us →
                    </Link>
                </p>
            </section>
            <CtaBand />
        </>
    );
}
