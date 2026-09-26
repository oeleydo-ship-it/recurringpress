import { Head, usePage } from '@inertiajs/react';
import { Heart, Lightbulb, ShieldCheck, Zap } from 'lucide-react';
import { CtaBand, SectionHeading } from '@/Components/marketing/shared';

export default function About() {
    const { site } = usePage().props;
    const name = site?.name ?? 'RecurringPress';

    return (
        <>
            <Head title="About us">
                <meta name="description" content="We're on a mission to help freelancers and small businesses get paid faster with professional invoicing tools that are simple to use." head-key="description" />
            </Head>
            <section className="mx-auto max-w-7xl px-4 pt-16 pb-20 sm:px-6 lg:px-8">
                <SectionHeading
                    eyebrow="About us"
                    title="Built for freelancers, loved by businesses"
                    subtitle="We're on a mission to help freelancers and small businesses get paid faster with professional invoicing tools that are simple to use."
                />
                <div className="mt-20 grid items-center gap-12 lg:grid-cols-2">
                    <div>
                        <p className="text-sm font-semibold tracking-wide text-indigo-600 uppercase">Our story</p>
                        <h2 className="mt-2 text-3xl font-semibold tracking-tight text-slate-900">Why we built {name}</h2>
                        <div className="mt-6 space-y-4 text-lg leading-relaxed text-slate-600">
                            <p>{name} was born out of frustration with complicated invoicing software. As freelancers ourselves, we knew there had to be a better way.</p>
                            <p>We built it to be simple, beautiful, and powerful — everything you need to create professional invoices and get paid faster, without the complexity.</p>
                            <p>Today, thousands of freelancers and small businesses trust {name} to manage their invoicing and grow their businesses.</p>
                        </div>
                    </div>
                    <dl className="grid grid-cols-2 gap-4">
                        {[
                            ['10K+', 'Active users'],
                            ['$50M+', 'Invoiced'],
                            ['100K+', 'Invoices sent'],
                            ['4.9★', 'User rating'],
                        ].map(([n, l]) => (
                            <div key={l} className="rounded-2xl bg-slate-50 p-8 ring-1 ring-slate-200">
                                <dt className="text-sm text-slate-500">{l}</dt>
                                <dd className="mt-2 text-3xl font-semibold tracking-tight text-slate-900">{n}</dd>
                            </div>
                        ))}
                    </dl>
                </div>
            </section>
            <section className="bg-slate-50 py-24">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <SectionHeading eyebrow="Our values" title="What guides us every day" />
                    <div className="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                        {[
                            [Zap, 'Simplicity', 'Powerful tools that anyone can use without a manual.'],
                            [ShieldCheck, 'Trust', 'Your data and your clients’ payments are protected by design.'],
                            [Heart, 'Customer love', 'We listen, and ship what our users actually need.'],
                            [Lightbulb, 'Craft', 'Beautiful documents that make your business look its best.'],
                        ].map(([Icon, t, d]) => (
                            <div key={t} className="rounded-2xl bg-white p-6 ring-1 ring-slate-200">
                                <span className="flex size-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                                    <Icon className="size-5" />
                                </span>
                                <h3 className="mt-5 font-semibold text-slate-900">{t}</h3>
                                <p className="mt-2 text-sm text-slate-600">{d}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>
            <CtaBand />
        </>
    );
}
