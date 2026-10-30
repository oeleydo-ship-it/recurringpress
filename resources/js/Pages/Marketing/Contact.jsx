import { Head, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Clock, Mail, MessageSquare, Send } from 'lucide-react';
import Honeypot, { useHoneypot } from '@/Components/Honeypot';
import { Button, FieldError, FormField, Input, Textarea } from '@/Components/ui';
import { PageHero } from '@/Components/marketing/shared';
import { route } from '@/lib/route';

export default function Contact({ page, email }) {
    const { flash } = usePage().props;
    const hp = useHoneypot();
    const form = useForm({ ...hp.fields, name: '', email: '', subject: '', message: '' });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('landing.contact.submit'), { preserveScroll: true, onSuccess: () => form.reset('name', 'email', 'subject', 'message') });
    };

    return (
        <>
            <Head title="Contact" />
            <PageHero eyebrow="Contact" title={page?.title ?? 'Get in touch'} subtitle={page ? null : "Questions, feedback or partnership ideas — we'd love to hear from you."} />
            <section className="mx-auto max-w-6xl px-4 pb-24 sm:px-6 lg:px-8">
                {page?.content ? <div className="prose-content mx-auto mb-12 max-w-3xl" dangerouslySetInnerHTML={{ __html: page.content }} /> : null}

                <div className="mx-auto grid max-w-5xl gap-8 rounded-3xl bg-blue-50 p-3 ring-1 ring-blue-100 sm:p-6 lg:grid-cols-[1fr_1.4fr] lg:p-8">
                    <div className="space-y-6 p-3 sm:p-2">
                        {[
                            [Mail, 'Email us', email ?? 'Use the form and we’ll reply by email.'],
                            [Clock, 'Response time', 'Usually within one business day.'],
                            [MessageSquare, 'Existing customer?', 'Sign in and use the help link for priority support.'],
                        ].map(([Icon, t, d]) => (
                            <div key={t} className="flex gap-4">
                                <span className="flex size-11 shrink-0 items-center justify-center rounded-xl bg-white text-blue-600 shadow-sm ring-1 ring-blue-100">
                                    <Icon className="size-5" />
                                </span>
                                <div>
                                    <p className="font-semibold text-slate-900">{t}</p>
                                    <p className="text-sm text-slate-500">{d}</p>
                                </div>
                            </div>
                        ))}
                    </div>

                    <form onSubmit={submit} noValidate className="relative space-y-5 rounded-2xl bg-white p-6 shadow-xl shadow-blue-900/5 ring-1 ring-slate-200 sm:p-8">
                        {flash?.success ? (
                            <div className="flex items-center gap-2 rounded-xl bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
                                <CheckCircle2 className="size-5" /> {flash.success}
                            </div>
                        ) : null}
                        <Honeypot form={form} />
                        <FieldError>{form.errors.form}</FieldError>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <FormField label="Name" required error={form.errors.name}>
                                <Input value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} invalid={Boolean(form.errors.name)} autoComplete="name" />
                            </FormField>
                            <FormField label="Email" required error={form.errors.email}>
                                <Input type="email" value={form.data.email} onChange={(e) => form.setData('email', e.target.value)} invalid={Boolean(form.errors.email)} autoComplete="email" />
                            </FormField>
                        </div>
                        <FormField label="Subject" required error={form.errors.subject}>
                            <Input value={form.data.subject} onChange={(e) => form.setData('subject', e.target.value)} invalid={Boolean(form.errors.subject)} />
                        </FormField>
                        <FormField label="Message" required error={form.errors.message}>
                            <Textarea rows={5} value={form.data.message} onChange={(e) => form.setData('message', e.target.value)} invalid={Boolean(form.errors.message)} />
                        </FormField>
                        <Button type="submit" size="lg" icon={Send} loading={form.processing} className="w-full bg-blue-600 text-white hover:bg-blue-700">
                            Send message
                        </Button>
                    </form>
                </div>
            </section>
        </>
    );
}
