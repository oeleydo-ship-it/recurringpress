import { Link, router, useForm } from '@inertiajs/react';
import { AlertTriangle, Building2, Check, Copy, CreditCard, FileSignature, FileText, Globe, ImagePlus, Mail, NotebookPen, Palette, RefreshCw, Save, Send, Settings2, Sparkles, Trash2, X } from 'lucide-react';
import { useState } from 'react';
import TemplateThumbnail from '@/Components/TemplateThumbnail';
import { Badge, Button, Card, CardBody, CardFooter, CardHeader, FieldError, FormField, Input, PageHeader, Segmented, Select, Switch, Tabs, Textarea, useConfirm } from '@/Components/ui';
import { route } from '@/lib/route';
import { cn, formatDateTime } from '@/lib/utils';

export default function SettingsIndex({ company, settings, options, webhookUrl, domain, smtp }) {
    return (
        <>
            <PageHeader
                title="Settings"
                description="Company profile, documents, payments and branding."
                actions={
                    <Button href={route('settings.subscription')} variant="secondary" icon={Sparkles}>
                        Subscription
                    </Button>
                }
            />
            <Tabs
                vertical
                defaultKey="company"
                tabs={[
                    { key: 'company', label: 'Company', icon: Building2, content: <CompanyTab company={company} options={options} /> },
                    { key: 'invoices', label: 'Invoices', icon: FileText, content: <InvoiceTab company={company} settings={settings} /> },
                    { key: 'quotations', label: 'Quotations', icon: FileSignature, content: <QuotationTab company={company} settings={settings} /> },
                    { key: 'payments', label: 'Payments', icon: CreditCard, content: <PaymentsTab settings={settings} webhookUrl={webhookUrl} /> },
                    { key: 'appearance', label: 'Appearance', icon: Palette, content: <AppearanceTab settings={settings} options={options} /> },
                    {
                        key: 'domain',
                        label: 'Domains',
                        icon: Globe,
                        content: (
                            <div className="space-y-6">
                                <SubdomainCard subdomain={domain.subdomain} />
                                <DomainTab domain={domain} />
                            </div>
                        ),
                    },
                    { key: 'email', label: 'Email (SMTP)', icon: Mail, content: <EmailTab smtp={smtp} /> },
                    { key: 'preferences', label: 'Preferences', icon: Settings2, content: <PreferencesTab settings={settings} options={options} /> },
                    { key: 'notes', label: 'Notes & terms', icon: NotebookPen, content: <NotesTab settings={settings} /> },
                ]}
            />
        </>
    );
}

/* ------------------------------------------------------------------ */

function Section({ title, description, form, onSubmit, children, submitLabel = 'Save changes' }) {
    return (
        <form
            onSubmit={(e) => {
                e.preventDefault();
                onSubmit();
            }}
            noValidate
        >
            <Card>
                <CardHeader title={title} description={description} />
                <CardBody className="space-y-5">{children}</CardBody>
                <CardFooter>
                    {form.recentlySuccessful ? (
                        <span className="mr-auto inline-flex items-center gap-1 text-sm text-emerald-600">
                            <Check className="size-4" /> Saved
                        </span>
                    ) : null}
                    <Button type="submit" icon={Save} loading={form.processing}>
                        {submitLabel}
                    </Button>
                </CardFooter>
            </Card>
        </form>
    );
}

const bind = (form, name) => ({
    value: form.data[name] ?? '',
    onChange: (e) => form.setData(name, e.target.value),
    invalid: Boolean(form.errors[name]),
});

function Field({ form, name, label, className, hint, required, ...props }) {
    return (
        <FormField label={label} error={form.errors[name]} className={className} hint={hint} required={required}>
            <Input {...bind(form, name)} {...props} />
        </FormField>
    );
}

/* ------------------------------ Company ------------------------------ */

function CompanyTab({ company, options }) {
    const [logo, setLogo] = useState(company.logo_url);
    const form = useForm({
        name: company.name ?? '',
        email: company.email ?? '',
        phone: company.phone ?? '',
        website: company.website ?? '',
        tax_number: company.tax_number ?? '',
        address: company.address ?? '',
        city: company.city ?? '',
        state: company.state ?? '',
        postal_code: company.postal_code ?? '',
        country: company.country ?? '',
        currency: company.currency ?? 'USD',
        logo: null,
        remove_logo: false,
    });

    return (
        <Section
            title="Company profile"
            description="Appears in the “From” block of every document."
            form={form}
            onSubmit={() => {
                form.transform((d) => ({ ...d, _method: 'put' }));
                form.post(route('settings.company'), { forceFormData: true, preserveScroll: true, onSuccess: () => form.setData('logo', null) });
            }}
        >
            <div className="flex items-center gap-5">
                <label className="group relative flex h-20 w-40 cursor-pointer items-center justify-center overflow-hidden rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 hover:border-brand-400">
                    {logo ? <img src={logo} alt="Logo" className="max-h-16 max-w-[140px] object-contain" /> : <ImagePlus className="size-6 text-slate-400" />}
                    <input
                        type="file"
                        accept="image/png,image/jpeg,image/webp,image/gif"
                        className="sr-only"
                        onChange={(e) => {
                            const f = e.target.files?.[0];
                            if (f) {
                                form.setData((d) => ({ ...d, logo: f, remove_logo: false }));
                                setLogo(URL.createObjectURL(f));
                            }
                        }}
                    />
                </label>
                <div className="text-sm">
                    <p className="font-medium text-slate-800">Logo</p>
                    <p className="text-slate-500">PNG, JPG or WebP · max 2 MB</p>
                    {logo ? (
                        <button
                            type="button"
                            className="mt-1 inline-flex items-center gap-1 text-xs font-medium text-red-600"
                            onClick={() => {
                                form.setData((d) => ({ ...d, logo: null, remove_logo: true }));
                                setLogo(null);
                            }}
                        >
                            <X className="size-3" /> Remove
                        </button>
                    ) : null}
                    <FieldError>{form.errors.logo}</FieldError>
                </div>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">
                <Field form={form} name="name" label="Company name" required />
                <FormField label="Currency" error={form.errors.currency}>
                    <Select {...bind(form, 'currency')} options={options.currencies} />
                </FormField>
                <Field form={form} name="email" label="Billing email" type="email" />
                <Field form={form} name="phone" label="Phone" />
                <Field form={form} name="website" label="Website" placeholder="https://" />
                <Field form={form} name="tax_number" label="Tax / VAT number" />
                <Field form={form} name="address" label="Street address" className="sm:col-span-2" />
                <Field form={form} name="city" label="City" />
                <Field form={form} name="state" label="State / region" />
                <Field form={form} name="postal_code" label="Postal code" />
                <Field form={form} name="country" label="Country" />
            </div>
        </Section>
    );
}

/* ------------------------------ Invoices ------------------------------ */

function InvoiceTab({ company, settings }) {
    const [signature, setSignature] = useState(company.signature_url);
    const form = useForm({
        invoice_prefix: company.invoice_prefix ?? 'INV-',
        invoice_start_number: company.invoice_start_number ?? 1,
        payment_terms: company.payment_terms ?? 30,
        invoice_footer: company.invoice_footer ?? '',
        show_invoice_footer: Boolean(settings.show_invoice_footer),
        show_authorized_signature: Boolean(settings.show_authorized_signature),
        authorized_signature: null,
    });

    return (
        <Section
            title="Invoice numbering & defaults"
            form={form}
            onSubmit={() => {
                form.transform((d) => ({ ...d, _method: 'put' }));
                form.post(route('settings.invoice'), { forceFormData: true, preserveScroll: true });
            }}
        >
            <div className="grid gap-4 sm:grid-cols-3">
                <Field form={form} name="invoice_prefix" label="Number prefix" className="font-mono" hint={`Next looks like ${form.data.invoice_prefix}00042`} />
                <Field form={form} name="invoice_start_number" label="Start at" type="number" min="1" />
                <Field form={form} name="payment_terms" label="Payment terms" type="number" min="0" max="365" suffix="days" />
            </div>
            <FormField label="Footer text" error={form.errors.invoice_footer}>
                <Textarea rows={2} {...bind(form, 'invoice_footer')} />
            </FormField>
            <Switch label="Show footer on invoices" checked={form.data.show_invoice_footer} onChange={(v) => form.setData('show_invoice_footer', v)} />
            <div className="rounded-xl bg-slate-50 p-4">
                <Switch label="Add authorised signature" description="Printed above the signature line." checked={form.data.show_authorized_signature} onChange={(v) => form.setData('show_authorized_signature', v)} />
                {form.data.show_authorized_signature ? (
                    <label className="mt-4 flex h-20 w-56 cursor-pointer items-center justify-center rounded-lg border-2 border-dashed border-slate-200 bg-white hover:border-brand-400">
                        {signature ? <img src={signature} alt="Signature" className="max-h-16 object-contain" /> : <span className="text-sm text-slate-500">Upload signature image</span>}
                        <input
                            type="file"
                            accept="image/png,image/jpeg,image/webp"
                            className="sr-only"
                            onChange={(e) => {
                                const f = e.target.files?.[0];
                                if (f) {
                                    form.setData('authorized_signature', f);
                                    setSignature(URL.createObjectURL(f));
                                }
                            }}
                        />
                    </label>
                ) : null}
                <FieldError>{form.errors.authorized_signature}</FieldError>
            </div>
        </Section>
    );
}

/* ----------------------------- Quotations ----------------------------- */

function QuotationTab({ company, settings }) {
    const form = useForm({
        quotation_prefix: company.quotation_prefix ?? 'QT-',
        quotation_validity_days: company.quotation_validity_days ?? 30,
        show_quotation_website: Boolean(settings.show_quotation_website),
        show_quotation_message: Boolean(settings.show_quotation_message),
        quotation_message: settings.quotation_message ?? '',
    });
    return (
        <Section title="Quotations" form={form} onSubmit={() => form.put(route('settings.quotation'), { preserveScroll: true })}>
            <div className="grid gap-4 sm:grid-cols-2">
                <Field form={form} name="quotation_prefix" label="Number prefix" className="font-mono" />
                <Field form={form} name="quotation_validity_days" label="Valid for" type="number" min="0" max="365" suffix="days" />
            </div>
            <Switch label="Show your website on quotations" checked={form.data.show_quotation_website} onChange={(v) => form.setData('show_quotation_website', v)} />
            <Switch label="Add a personal message" checked={form.data.show_quotation_message} onChange={(v) => form.setData('show_quotation_message', v)} />
            {form.data.show_quotation_message ? (
                <FormField label="Message" error={form.errors.quotation_message}>
                    <Textarea rows={3} {...bind(form, 'quotation_message')} />
                </FormField>
            ) : null}
        </Section>
    );
}

/* ------------------------------ Payments ------------------------------ */

function PaymentsTab({ settings, webhookUrl }) {
    const form = useForm({
        accept_stripe: Boolean(settings.accept_stripe),
        payment_gateway_mode: settings.payment_gateway_mode ?? 'app',
        stripe_public_key: settings.stripe_public_key ?? '',
        stripe_secret_key: '',
        stripe_webhook_secret: '',
        accept_bank_transfer: Boolean(settings.accept_bank_transfer),
        bank_name: settings.bank_name ?? '',
        bank_account_name: settings.bank_account_name ?? '',
        bank_account_number: settings.bank_account_number ?? '',
        bank_routing_number: settings.bank_routing_number ?? '',
        accept_cash: Boolean(settings.accept_cash),
        paypal_email: settings.paypal_email ?? '',
        show_payment_instructions: Boolean(settings.show_payment_instructions),
        payment_instructions: settings.payment_instructions ?? '',
    });
    const d = form.data;

    return (
        <Section
            title="Payment methods"
            description="What clients see when they open an invoice."
            form={form}
            onSubmit={() => form.put(route('settings.payment'), { preserveScroll: true, onSuccess: () => form.setData((x) => ({ ...x, stripe_secret_key: '', stripe_webhook_secret: '' })) })}
        >
            <div className="divide-y divide-slate-100 rounded-xl ring-1 ring-slate-200">
                <div className="space-y-4 p-4">
                    <Switch label="Card payments (Stripe)" description="A “Pay now” button on every invoice." checked={d.accept_stripe} onChange={(v) => form.setData('accept_stripe', v)} />
                    {d.accept_stripe ? (
                        <>
                            <Segmented
                                value={d.payment_gateway_mode}
                                onChange={(v) => form.setData('payment_gateway_mode', v)}
                                options={[
                                    { value: 'app', label: 'Platform account' },
                                    { value: 'own', label: 'My Stripe account' },
                                ]}
                            />
                            {d.payment_gateway_mode === 'own' ? (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <Field form={form} name="stripe_public_key" label="Publishable key" placeholder="pk_live_…" className="sm:col-span-2" />
                                    <Field
                                        form={form}
                                        name="stripe_secret_key"
                                        label="Secret key"
                                        type="password"
                                        autoComplete="off"
                                        placeholder={settings.has_stripe_secret ? '•••••••• (saved)' : 'sk_live_…'}
                                        hint={settings.has_stripe_secret ? 'Leave blank to keep the saved key.' : undefined}
                                    />
                                    <Field
                                        form={form}
                                        name="stripe_webhook_secret"
                                        label="Webhook signing secret"
                                        type="password"
                                        autoComplete="off"
                                        placeholder={settings.has_webhook_secret ? '•••••••• (saved)' : 'whsec_…'}
                                        hint={settings.has_webhook_secret ? 'Leave blank to keep the saved secret.' : undefined}
                                    />
                                    <div className="rounded-lg bg-slate-50 p-3 text-xs text-slate-600 sm:col-span-2">
                                        In Stripe → Developers → Webhooks, add an endpoint for <code className="rounded bg-white px-1 py-0.5 font-mono">{webhookUrl}</code> with the event{' '}
                                        <code className="rounded bg-white px-1 py-0.5 font-mono">payment_intent.succeeded</code>.
                                    </div>
                                </div>
                            ) : (
                                <p className="text-sm text-slate-500">
                                    Payments are collected by the platform and paid out from{' '}
                                    <Link href={route('payouts.index')} className="font-medium text-brand-600">
                                        Payouts
                                    </Link>
                                    .
                                </p>
                            )}
                        </>
                    ) : null}
                </div>
                <div className="space-y-4 p-4">
                    <Switch label="Bank transfer" description="Bank details printed on invoices." checked={d.accept_bank_transfer} onChange={(v) => form.setData('accept_bank_transfer', v)} />
                    {d.accept_bank_transfer ? (
                        <div className="grid gap-4 sm:grid-cols-2">
                            <Field form={form} name="bank_name" label="Bank name" />
                            <Field form={form} name="bank_account_name" label="Account holder" />
                            <Field form={form} name="bank_account_number" label="Account number / IBAN" />
                            <Field form={form} name="bank_routing_number" label="Routing / SWIFT" />
                        </div>
                    ) : null}
                </div>
                <div className="p-4">
                    <Switch label="Cash" checked={d.accept_cash} onChange={(v) => form.setData('accept_cash', v)} />
                </div>
            </div>
            <Field form={form} name="paypal_email" label="PayPal email" type="email" />
            <Switch label="Show payment instructions" checked={d.show_payment_instructions} onChange={(v) => form.setData('show_payment_instructions', v)} />
            {d.show_payment_instructions ? (
                <FormField label="Instructions" error={form.errors.payment_instructions}>
                    <Textarea rows={3} {...bind(form, 'payment_instructions')} />
                </FormField>
            ) : null}
        </Section>
    );
}

/* ----------------------------- Appearance ----------------------------- */

function AppearanceTab({ settings, options }) {
    const form = useForm({
        primary_color: settings.primary_color ?? '#4f46e5',
        secondary_color: settings.secondary_color ?? '#6366f1',
        sidebar_color: settings.sidebar_color ?? '#1e1b4b',
        sidebar_text_color: settings.sidebar_text_color ?? '#ffffff',
        invoice_template: settings.invoice_template ?? 'classic',
        font_family: settings.font_family ?? 'Inter',
        font_size: settings.font_size ?? 'base',
        heading_color: settings.heading_color ?? '',
        text_color: settings.text_color ?? '',
    });
    const d = form.data;
    const applyPreset = (p) => form.setData((x) => ({ ...x, primary_color: p.primary, secondary_color: p.secondary, sidebar_color: p.sidebar, sidebar_text_color: p.sidebar_text }));

    return (
        <Section title="Appearance" description="Your brand colours are used across the app and your client-facing pages." form={form} onSubmit={() => form.put(route('settings.theme'), { preserveScroll: true })}>
            <div>
                <p className="mb-3 text-sm font-medium text-slate-700">Presets</p>
                <div className="flex flex-wrap gap-3">
                    {Object.entries(options.colorPresets).map(([name, p]) => {
                        const active = d.primary_color === p.primary && d.sidebar_color === p.sidebar;
                        return (
                            <button key={name} type="button" onClick={() => applyPreset(p)} className={cn('flex overflow-hidden rounded-lg ring-2 ring-offset-2 transition', active ? 'ring-slate-900' : 'ring-transparent hover:ring-slate-300')} title={name}>
                                <span className="h-9 w-5" style={{ background: p.sidebar }} />
                                <span className="h-9 w-9" style={{ background: p.primary }} />
                            </button>
                        );
                    })}
                </div>
            </div>
            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {[
                    ['primary_color', 'Primary'],
                    ['secondary_color', 'Secondary'],
                    ['sidebar_color', 'Sidebar'],
                    ['sidebar_text_color', 'Sidebar text'],
                ].map(([key, label]) => (
                    <FormField key={key} label={label} error={form.errors[key]}>
                        <div className="flex items-center gap-2">
                            <label className="relative size-9 shrink-0 cursor-pointer overflow-hidden rounded-lg ring-1 ring-slate-300" style={{ background: d[key] }}>
                                <input type="color" value={d[key]} onChange={(e) => form.setData(key, e.target.value)} className="absolute inset-0 cursor-pointer opacity-0" />
                            </label>
                            <Input value={d[key]} onChange={(e) => form.setData(key, e.target.value)} className="font-mono" maxLength={7} />
                        </div>
                    </FormField>
                ))}
            </div>

            {/* Live mini preview */}
            <div className="flex h-36 overflow-hidden rounded-xl ring-1 ring-slate-200">
                <div className="w-32 space-y-2 p-3" style={{ background: d.sidebar_color }}>
                    <div className="h-2.5 w-16 rounded" style={{ background: d.sidebar_text_color, opacity: 0.9 }} />
                    {[1, 2, 3].map((i) => (
                        <div key={i} className="h-2 rounded" style={{ background: d.sidebar_text_color, opacity: i === 1 ? 0.8 : 0.35, width: `${80 - i * 10}%` }} />
                    ))}
                </div>
                <div className="flex-1 space-y-3 bg-slate-50 p-4" style={{ fontFamily: `'${d.font_family}'` }}>
                    <div className="text-sm font-semibold text-slate-800">Dashboard</div>
                    <div className="flex gap-2">
                        <span className="rounded-md px-3 py-1.5 text-xs font-medium text-white" style={{ background: d.primary_color }}>
                            New invoice
                        </span>
                        <span className="rounded-md bg-white px-3 py-1.5 text-xs font-medium text-slate-700 ring-1 ring-slate-200">Export</span>
                    </div>
                    <div className="h-2 w-3/4 rounded bg-slate-200" />
                </div>
            </div>

            <div className="grid gap-4 sm:grid-cols-2">
                <FormField label="Font" error={form.errors.font_family}>
                    <Select {...bind(form, 'font_family')} options={options.fontFamilies} />
                </FormField>
                <FormField label="Text size" error={form.errors.font_size}>
                    <Select {...bind(form, 'font_size')} options={options.fontSizes} />
                </FormField>
            </div>

            <div>
                <p className="mb-3 text-sm font-medium text-slate-700">Invoice design</p>
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-4">
                    {Object.entries(options.invoiceTemplates).map(([key, t]) => {
                        const active = d.invoice_template === key;
                        return (
                            <button key={key} type="button" onClick={() => form.setData('invoice_template', key)} className="group text-left">
                                <div className={cn('overflow-hidden rounded-xl ring-1 transition', active ? 'ring-2 ring-brand-500 ring-offset-2' : 'ring-slate-200 group-hover:ring-slate-300')}>
                                    <TemplateThumbnail src={route('settings.template.preview', key)} />
                                </div>
                                <div className="mt-2 flex items-center justify-between">
                                    <span className="text-sm font-medium text-slate-800">{t.name}</span>
                                    {active ? <Check className="size-4 text-brand-600" /> : null}
                                </div>
                                <span className="text-xs text-slate-500">{t.description}</span>
                            </button>
                        );
                    })}
                </div>
            </div>
        </Section>
    );
}

/* ----------------------------- Preferences ----------------------------- */

function PreferencesTab({ settings, options }) {
    const form = useForm({
        date_format: settings.date_format ?? 'M d, Y',
        timezone: settings.timezone ?? 'UTC',
        tax_enabled: Boolean(settings.tax_enabled),
        default_tax_rate: settings.default_tax_rate ?? '',
        email_notifications: Boolean(settings.email_notifications),
        invoice_due_reminder: Boolean(settings.invoice_due_reminder),
        overdue_reminder: Boolean(settings.overdue_reminder),
        show_logo_on_invoice: settings.show_logo_on_invoice === null || settings.show_logo_on_invoice === undefined ? true : Boolean(settings.show_logo_on_invoice),
    });
    const tz = Object.fromEntries(options.timezones.map((t) => [t, t.replaceAll('_', ' ')]));

    return (
        <Section title="Preferences" form={form} onSubmit={() => form.put(route('settings.preferences'), { preserveScroll: true })}>
            <div className="grid gap-4 sm:grid-cols-2">
                <FormField label="Date format" error={form.errors.date_format}>
                    <Select {...bind(form, 'date_format')} options={options.dateFormats} />
                </FormField>
                <FormField label="Time zone" error={form.errors.timezone}>
                    <Select {...bind(form, 'timezone')} options={tz} />
                </FormField>
            </div>
            <div className="rounded-xl bg-slate-50 p-4">
                <Switch label="Charge tax by default" checked={form.data.tax_enabled} onChange={(v) => form.setData('tax_enabled', v)} />
                {form.data.tax_enabled ? <Field form={form} name="default_tax_rate" label="Default tax rate" type="number" min="0" max="100" step="0.01" suffix="%" className="mt-4 sm:max-w-[200px]" /> : null}
            </div>
            <Switch label="Show logo on invoices" checked={form.data.show_logo_on_invoice} onChange={(v) => form.setData('show_logo_on_invoice', v)} />
            <Switch label="Email notifications" description="Get notified when clients view, accept or pay." checked={form.data.email_notifications} onChange={(v) => form.setData('email_notifications', v)} />
            <Switch label="Due-date reminders" checked={form.data.invoice_due_reminder} onChange={(v) => form.setData('invoice_due_reminder', v)} />
            <Switch label="Overdue reminders" checked={form.data.overdue_reminder} onChange={(v) => form.setData('overdue_reminder', v)} />
        </Section>
    );
}

/* --------------------------- Notes & terms --------------------------- */

function NotesTab({ settings }) {
    const form = useForm({ default_notes: settings.default_notes ?? '', default_terms: settings.default_terms ?? '' });
    return (
        <Section title="Default notes & terms" description="Pre-filled on new invoices and quotations." form={form} onSubmit={() => form.put(route('settings.notes-terms'), { preserveScroll: true })}>
            <FormField label="Notes to client" error={form.errors.default_notes}>
                <Textarea rows={4} {...bind(form, 'default_notes')} />
            </FormField>
            <FormField label="Terms & conditions" error={form.errors.default_terms}>
                <Textarea rows={6} {...bind(form, 'default_terms')} />
            </FormField>
        </Section>
    );
}

/* -------------------------- Platform subdomain -------------------------- */

function SubdomainCard({ subdomain }) {
    const confirm = useConfirm();
    const form = useForm({ subdomain: subdomain.slug ?? '' });

    if (!subdomain.enabled) {
        return <Card><CardHeader title="Subdomain" /><CardBody><p className="text-sm text-slate-500">{subdomain.allowed ? 'Subdomains are not enabled on this installation.' : 'Upgrade your plan to use a platform subdomain.'}</p></CardBody></Card>;
    }

    return (
        <Section
            title="Subdomain"
            description={`An address on ${subdomain.base} for your client-facing invoice and quotation pages. It works straight away, with no DNS setup.`}
            form={form}
            submitLabel={subdomain.slug ? 'Save subdomain' : 'Claim subdomain'}
            onSubmit={() => form.put(route('settings.subdomain'), { preserveScroll: true })}
        >
            <FormField label="Subdomain" error={form.errors.subdomain} hint="3–32 lowercase letters, numbers or hyphens. Changing it breaks links already sent with the old name.">
                <Input
                    value={form.data.subdomain}
                    onChange={(e) => form.setData('subdomain', e.target.value.toLowerCase())}
                    invalid={Boolean(form.errors.subdomain)}
                    placeholder="yourcompany"
                    className="font-mono"
                    maxLength={32}
                    suffix={`.${subdomain.base}`}
                />
            </FormField>
            {subdomain.url ? (
                <div className="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-slate-50 p-3 text-sm">
                    <span className="flex items-center gap-2">
                        <Badge tone={subdomain.in_use ? 'green' : 'gray'} dot>
                            {subdomain.in_use ? 'Used in emails' : 'Active'}
                        </Badge>
                        <CopyValue value={subdomain.url} />
                    </span>
                    {!subdomain.in_use ? <span className="text-xs text-slate-500">Emails link to your verified custom domain instead.</span> : null}
                    <button
                        type="button"
                        className="inline-flex items-center gap-1 text-xs font-medium text-red-600"
                        onClick={async () => {
                            if (await confirm({ title: 'Remove subdomain?', message: 'Links already sent with this subdomain will stop working.', confirmText: 'Remove' })) {
                                router.delete(route('settings.subdomain.remove'), { preserveScroll: true, onSuccess: () => form.setData('subdomain', '') });
                            }
                        }}
                    >
                        <Trash2 className="size-3" /> Remove
                    </button>
                </div>
            ) : null}
        </Section>
    );
}

/* --------------------------- Custom domain --------------------------- */

function CopyValue({ value }) {
    const [copied, setCopied] = useState(false);
    return (
        <span className="flex min-w-0 items-center gap-1.5">
            <code className="min-w-0 break-all rounded bg-slate-100 px-1.5 py-0.5 font-mono text-xs text-slate-800">{value}</code>
            <button
                type="button"
                className="shrink-0 text-slate-400 hover:text-slate-700"
                title="Copy"
                onClick={() => {
                    navigator.clipboard?.writeText(value);
                    setCopied(true);
                    setTimeout(() => setCopied(false), 1500);
                }}
            >
                {copied ? <Check className="size-3.5 text-emerald-600" /> : <Copy className="size-3.5" />}
            </button>
        </span>
    );
}

function DomainTab({ domain }) {
    const confirm = useConfirm();
    const [editing, setEditing] = useState(!domain.domain);
    const [verifying, setVerifying] = useState(false);
    const form = useForm({ custom_domain: domain.domain ?? '' });

    if (!domain.enabled) {
        return (
            <Card>
                <CardHeader title="Custom domain" description="Show invoice and quotation links on your own domain, e.g. billing.yourcompany.com." />
                <CardBody>
                    <p className="text-sm text-slate-500">{domain.allowed ? 'Custom domains are not enabled on this installation yet.' : 'Upgrade your plan to connect a custom domain.'} Your links use the platform address.</p>
                </CardBody>
            </Card>
        );
    }

    if (editing || !domain.domain) {
        return (
            <Section
                title="Custom domain"
                description="Show invoice and quotation links on your own domain, e.g. billing.yourcompany.com."
                form={form}
                submitLabel="Save domain"
                onSubmit={() => form.put(route('settings.domain'), { preserveScroll: true, onSuccess: () => setEditing(false) })}
            >
                <Field form={form} name="custom_domain" label="Domain" placeholder="billing.yourcompany.com" hint="A subdomain you control. Clients will see links like https://billing.yourcompany.com/invoice/…" />
                {domain.domain ? (
                    <button type="button" className="text-sm font-medium text-slate-500 hover:text-slate-700" onClick={() => setEditing(false)}>
                        Cancel
                    </button>
                ) : null}
            </Section>
        );
    }

    const isSub = domain.domain.split('.').length > 2;
    const stage = domain.verified ? null : domain.hostname_status === 'active' ? 'Issuing certificate' : 'Waiting for DNS';

    return (
        <Card>
            <CardHeader
                title="Custom domain"
                description="Client-facing invoice and quotation pages, and the links in your emails, use this domain once it is verified."
                actions={domain.verified ? <Badge tone="green" dot>Verified</Badge> : <Badge tone="yellow" dot>{stage}</Badge>}
            />
            <CardBody className="space-y-5">
                <div className="flex flex-wrap items-center gap-3">
                    <span className="font-mono text-base font-semibold text-slate-900">{domain.domain}</span>
                    <button type="button" className="text-sm font-medium text-brand-600" onClick={() => setEditing(true)}>
                        Change
                    </button>
                </div>

                {domain.error ? (
                    <div className="flex gap-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">
                        <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                        <span>
                            {domain.verified ? 'The last automatic check failed: ' : 'Not verified yet: '}
                            {domain.error}
                            {domain.checked_at ? <span className="text-amber-700/80"> (checked {formatDateTime(domain.checked_at)})</span> : null}
                        </span>
                    </div>
                ) : null}

                <div>
                    <p className="mb-2 text-sm font-medium text-slate-700">Add these DNS records at your domain provider</p>
                    <div className="overflow-x-auto rounded-xl ring-1 ring-slate-200">
                        <table className="w-full text-left text-sm">
                            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th className="px-3 py-2 font-medium">Type</th>
                                    <th className="px-3 py-2 font-medium">Name / host</th>
                                    <th className="px-3 py-2 font-medium">Value / target</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 align-top">
                                {domain.records.map((r) => (
                                    <tr key={`${r.type}-${r.name}-${r.value}`}>
                                        <td className="px-3 py-2.5">
                                            <span className="font-mono text-xs font-semibold">{r.type}</span>
                                            <span className="mt-0.5 block text-[11px] text-slate-500">{r.purpose}</span>
                                        </td>
                                        <td className="px-3 py-2.5">
                                            <CopyValue value={r.name} />
                                        </td>
                                        <td className="px-3 py-2.5">
                                            <CopyValue value={r.value} />
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <p className="mt-2 text-xs text-slate-500">
                        {isSub ? null : `Root domains need a DNS provider that supports CNAME flattening or ALIAS records; a subdomain such as billing.${domain.domain} is easier. `}
                        The TXT records let Cloudflare confirm the domain is yours and issue its SSL certificate. If a certificate record is missing, click “Check status” again in a minute. Keep the records in place: the domain is re-checked automatically. DNS changes can take up to an hour.
                    </p>
                </div>

                {domain.verified ? (
                    <p className="text-sm text-emerald-700">
                        Verified{domain.verified_at ? ` on ${formatDateTime(domain.verified_at)}` : ''}. New invoice and quotation emails link to https://{domain.domain}. Older links on the platform address keep working.
                    </p>
                ) : null}
            </CardBody>
            <CardFooter>
                <Button
                    variant="ghost"
                    icon={Trash2}
                    className="mr-auto text-red-600"
                    onClick={async () => {
                        if (await confirm({ title: `Disconnect ${domain.domain}?`, message: 'Links will use the platform address again. Links already sent with your domain will stop working.', confirmText: 'Disconnect' })) {
                            router.delete(route('settings.domain.remove'), { preserveScroll: true, onSuccess: () => form.setData('custom_domain', '') });
                        }
                    }}
                >
                    Disconnect
                </Button>
                <Button
                    icon={RefreshCw}
                    loading={verifying}
                    onClick={() => router.post(route('settings.domain.verify'), {}, { preserveScroll: true, onStart: () => setVerifying(true), onFinish: () => setVerifying(false) })}
                >
                    Check status
                </Button>
            </CardFooter>
        </Card>
    );
}

/* ------------------------------ Email (SMTP) ------------------------------ */

const ENCRYPTIONS = { tls: 'STARTTLS (usually port 587)', ssl: 'SSL/TLS (usually port 465)', none: 'None (not recommended)' };

function EmailTab({ smtp }) {
    const form = useForm({
        smtp_enabled: Boolean(smtp.smtp_enabled),
        smtp_host: smtp.smtp_host ?? '',
        smtp_port: smtp.smtp_port ?? 587,
        smtp_encryption: smtp.smtp_encryption ?? 'tls',
        smtp_username: smtp.smtp_username ?? '',
        smtp_password: '',
        smtp_from_address: smtp.smtp_from_address ?? '',
        smtp_from_name: smtp.smtp_from_name ?? '',
    });
    const test = useForm({ test_to: smtp.test_to ?? '' });
    const d = form.data;

    const status = smtp.in_use ? (
        <Badge tone="green" dot>
            In use
        </Badge>
    ) : smtp.smtp_enabled ? (
        <Badge tone="yellow" dot>
            Needs a successful test
        </Badge>
    ) : (
        <Badge tone="gray" dot>
            Off: platform mailer
        </Badge>
    );

    return (
        <div className="space-y-6">
            <Section
                title="Your SMTP server"
                description="Send invoices and quotations to clients from your own email address and server."
                form={form}
                onSubmit={() => form.put(route('settings.email'), { preserveScroll: true, onSuccess: () => form.setData('smtp_password', '') })}
            >
                <div className="flex justify-end">{status}</div>
                {smtp.smtp_last_error ? (
                    <div className="flex gap-2 rounded-lg bg-red-50 p-3 text-sm text-red-700">
                        <AlertTriangle className="mt-0.5 size-4 shrink-0" />
                        <span>
                            <strong className="font-semibold">Last send failed{smtp.last_error_at ? ` (${formatDateTime(smtp.last_error_at)})` : ''}:</strong> {smtp.smtp_last_error}
                            {smtp.in_use ? ' That email was delivered through the platform mailer instead.' : ''}
                        </span>
                    </div>
                ) : null}
                <Switch
                    label="Use my SMTP server for client emails"
                    description="Used only after a test email succeeds. If your server fails, emails go out from the platform address instead, with replies going to your company email."
                    checked={d.smtp_enabled}
                    onChange={(v) => form.setData('smtp_enabled', v)}
                />
                <div className="grid gap-4 sm:grid-cols-3">
                    <Field form={form} name="smtp_host" label="SMTP host" placeholder="smtp.yourprovider.com" className="sm:col-span-2" />
                    <Field form={form} name="smtp_port" label="Port" type="number" min="1" max="65535" />
                    <FormField label="Encryption" error={form.errors.smtp_encryption} className="sm:col-span-3">
                        <Select {...bind(form, 'smtp_encryption')} options={ENCRYPTIONS} />
                    </FormField>
                    <Field form={form} name="smtp_username" label="Username" autoComplete="off" />
                    <Field
                        form={form}
                        name="smtp_password"
                        label="Password"
                        type="password"
                        autoComplete="new-password"
                        className="sm:col-span-2"
                        placeholder={smtp.has_password ? '•••••••• (saved)' : ''}
                        hint={smtp.has_password ? 'Leave blank to keep the saved password. Stored encrypted.' : 'Stored encrypted.'}
                    />
                    <Field form={form} name="smtp_from_address" label="From address" type="email" placeholder="billing@yourcompany.com" className="sm:col-span-2" />
                    <Field form={form} name="smtp_from_name" label="From name" placeholder="Your Company" />
                </div>
            </Section>

            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    test.post(route('settings.email.test'), { preserveScroll: true });
                }}
                noValidate
            >
                <Card>
                    <CardHeader
                        title="Send a test email"
                        description={smtp.verified_at ? `Last successful test: ${formatDateTime(smtp.verified_at)}.` : 'Uses your saved settings, so save any changes first.'}
                    />
                    <CardBody>
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start">
                            <Field form={test} name="test_to" label="Send to" type="email" className="flex-1" />
                            <Button type="submit" variant="secondary" icon={Send} loading={test.processing} disabled={!smtp.smtp_host || form.isDirty} className="sm:mt-7">
                                Send test email
                            </Button>
                        </div>
                    </CardBody>
                </Card>
            </form>
        </div>
    );
}
