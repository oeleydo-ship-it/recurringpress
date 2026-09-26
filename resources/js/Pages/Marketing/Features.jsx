import { Head } from '@inertiajs/react';
import { CtaBand, FeatureCard, SectionHeading } from '@/Components/marketing/shared';

export default function Features({ title, subtitle, items, cta }) {
    return (
        <>
            <Head title="Features" />
            <section className="mx-auto max-w-7xl px-4 pt-16 pb-24 sm:px-6 lg:px-8">
                <SectionHeading eyebrow="Features" title={title} subtitle={subtitle} />
                <div className="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {items.map((f) => (
                        <FeatureCard key={f.title} item={f} />
                    ))}
                </div>
            </section>
            <CtaBand title={cta.title} subtitle={cta.subtitle} />
        </>
    );
}
