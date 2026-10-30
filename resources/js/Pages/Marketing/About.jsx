import { Head, usePage } from '@inertiajs/react';
import { Heart, Lightbulb, ShieldCheck, Zap } from 'lucide-react';
import { CtaBand, PageHero, SectionHeading } from '@/Components/marketing/shared';

export default function About() {
    const { site } = usePage().props;
    const name = site?.name ?? 'RecurringPress';

    return (
        <>
            <Head title="About us">
                <meta name="description" content="We're on a mission to help freelancers and small businesses get paid faster with professional invoicing tools that are simple to use." head-key="description" />
            </Head>
            <PageHero
                eyebrow="About us"
                title="Built for freelancers, loved by businesses"
                subtitle="We're on a mission to help freelancers and small businesses get paid faster with professional invoicing tools that are simple to use."
            />

            <section className="pb-24">
                <div className="mx-auto grid max-w-6xl items-center gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:px-8">
                    <div>
                        <p className="text-xs font-semibold tracking-[0.14em] text-blue-600 uppercase">Our story</p>
                        <h2 className="mt-3 text-3xl font-semibold tracking-tight text-slate-900 sm:text-4xl">Why we built {name}</h2>
                        <div className="mt-6 space-y-4 text-base leading-relaxed text-slate-500">
                            <p>{name} was born out of frustration with complicated invoicing software. As freelancers ourselves, we knew there had to be a better way.</p>
                            <p>We built it to be simple, beautiful, and powerful — everything you need to create professional invoices and get paid faster, without the complexity.</p>
                            <p>Today, thousands of freelancers and small businesses trust {name} to manage their invoicing and grow their businesses.</p>
                        </div>
                    </div>
                    <dl className="grid grid-cols-2 gap-3 rounded-3xl bg-blue-50 p-3 ring-1 ring-blue-100 sm:gap-4 sm:p-6">
                        {[
                            ['10K+', 'Active users'],
                            ['$50M+', 'Invoiced'],
                            ['100K+', 'Invoices sent'],
                            ['4.9★', 'User rating'],
                        ].map(([n, l]) => (
                            <div key={l} className="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 sm:p-7">
                                <dt className="text-sm text-slate-500">{l}</dt>
                                <dd className="mt-2 text-2xl font-semibold tracking-tight text-slate-900 sm:text-3xl">{n}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </section>

            <section className="border-t border-slate-100 py-24">
                <div className="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
                    <SectionHeading eyebrow="Our values" title="What guides us every day" />
                    <div className="mt-14 grid gap-10 sm:grid-cols-2 lg:grid-cols-4">
                        {[
                            [Zap, 'Simplicity', 'Powerful tools that anyone can use without a manual.'],
                            [ShieldCheck, 'Trust', 'Your data and your clients’ payments are protected by design.'],
                            [Heart, 'Customer love', 'We listen, and ship what our users actually need.'],
                            [Lightbulb, 'Craft', 'Beautiful documents that make your business look its best.'],
                        ].map(([Icon, t, d]) => (
                            <div key={t} className="text-center">
                                <Icon className="mx-auto size-6 text-blue-600" />
                                <h3 className="mt-4 text-base font-semibold text-slate-900">{t}</h3>
                                <p className="mt-2 text-sm leading-relaxed text-slate-500">{d}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>
            <CtaBand />
        </>
    );
}
