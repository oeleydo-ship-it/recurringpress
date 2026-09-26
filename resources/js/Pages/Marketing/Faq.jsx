import { Disclosure, DisclosureButton, DisclosurePanel } from '@headlessui/react';
import { Head, Link } from '@inertiajs/react';
import { ChevronDown } from 'lucide-react';
import { SectionHeading } from '@/Components/marketing/shared';
import { route } from '@/lib/route';

export default function Faq({ groups }) {
    return (
        <>
            <Head title="Frequently asked questions" />
            <section className="mx-auto max-w-4xl px-4 pt-16 pb-24 sm:px-6 lg:px-8">
                <SectionHeading eyebrow="FAQ" title="Frequently asked questions" subtitle="Everything you need to know. Can't find an answer? Get in touch." />
                <div className="mt-16 space-y-12">
                    {groups.map((g) => (
                        <div key={g.label}>
                            <h2 className="mb-4 text-sm font-semibold tracking-wide text-slate-500 uppercase">{g.label}</h2>
                            <div className="divide-y divide-slate-200 rounded-2xl bg-white ring-1 ring-slate-200">
                                {g.items.map((item) => (
                                    <Disclosure key={item.q} as="div" className="px-6">
                                        <DisclosureButton className="group flex w-full items-center justify-between gap-4 py-5 text-left">
                                            <span className="font-medium text-slate-900">{item.q}</span>
                                            <ChevronDown className="size-5 shrink-0 text-slate-400 transition group-data-open:rotate-180" />
                                        </DisclosureButton>
                                        <DisclosurePanel transition className="origin-top pb-5 leading-relaxed text-slate-600 transition duration-150 data-closed:-translate-y-1 data-closed:opacity-0">
                                            {item.a}
                                        </DisclosurePanel>
                                    </Disclosure>
                                ))}
                            </div>
                        </div>
                    ))}
                </div>
                <p className="mt-12 text-center text-slate-600">
                    Still have questions?{' '}
                    <Link href={route('landing.contact')} className="font-semibold text-indigo-600 hover:text-indigo-700">
                        Contact us →
                    </Link>
                </p>
            </section>
        </>
    );
}
