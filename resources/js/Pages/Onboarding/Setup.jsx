import { Head, router, useForm, usePage } from '@inertiajs/react';
import { ArrowLeft, ArrowRight, Building2, Check, CheckCircle2, CreditCard, ImagePlus, Palette, Percent, Sparkles, Upload, Users } from 'lucide-react';
import { useState } from 'react';
import TemplateThumbnail from '@/Components/TemplateThumbnail';
import { Badge, Button, FieldError, FormField, Input, Segmented, Select, Switch } from '@/Components/ui';
import { route } from '@/lib/route';
import { cn, formatMoney, themeStyle } from '@/lib/utils';

const STEPS = [
    { key: 1, label: 'Logo', icon: Upload, title: 'Add your logo', description: 'It appears on invoices, quotations and your client portal.' },
    { key: 2, label: 'Address', icon: Building2, title: 'Where is your business?', description: 'Shown in the “From” section of every invoice.' },
    { key: 3, label: 'Payments', icon: CreditCard, title: 'How do clients pay you?', description: 'Enable the methods you accept. Card payments work out of the box.' },
    { key: 4, label: 'Tax', icon: Percent, title: 'Tax settings', description: 'Set a default rate — you can override it per line item.' },
    { key: 5, label: 'Branding', icon: Palette, title: 'Make it yours', description: 'Pick a brand colour and an invoice design.' },
    { key: 6, label: 'Team', icon: Users, title: 'Invite your team', description: 'They’ll get an email to set their own password.' },
    { key: 7, label: 'Plan', icon: Sparkles, title: 'Choose your plan', description: 'Start free and upgrade whenever you need more.' },
];

export default function Setup(props) {
    const { auth } = usePage().props;
    const [step, setStep] = useState(props.step);
    const [brand, setBrand] = useState(props.settings.primary_color);
    const current = STEPS[step - 1];

    const go = (n) => {
        setStep(n);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };

    return (
        <div style={{ ...themeStyle(auth?.theme), '--brand': brand }} className="min-h-screen bg-slate-50 font-sans">
            <Head title="Set up your account" />

            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6">
                    <span className="text-sm font-semibold text-slate-900">{props.company.name}</span>
                    <button type="button" onClick={() => router.post(route('onboarding.skip'))} className="text-sm font-medium text-slate-500 hover:text-slate-800">
                        Skip setup
                    </button>
                </div>
            </header>

            <div className="mx-auto grid max-w-5xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[220px_1fr] lg:py-12">
                {/* Progress rail */}
                <nav aria-label="Setup progress">
                    <div className="mb-3 text-xs font-semibold tracking-wider text-slate-400 uppercase lg:mb-5">
                        Step {step} of {STEPS.length}
                    </div>
                    <div className="mb-6 h-1.5 overflow-hidden rounded-full bg-slate-200 lg:hidden">
                        <div className="h-full rounded-full bg-brand-500 transition-all" style={{ width: `${(step / STEPS.length) * 100}%` }} />
                    </div>
                    <ol className="hidden space-y-1 lg:block">
                        {STEPS.map((s) => {
                            const done = s.key < step;
                            const active = s.key === step;
                            const Icon = s.icon;
                            return (
                                <li key={s.key}>
                                    <button
                                        type="button"
                                        onClick={() => go(s.key)}
                                        className={cn(
                                            'flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left text-sm font-medium transition-colors',
                                            active ? 'bg-white text-slate-900 shadow-card ring-1 ring-slate-900/5' : 'text-slate-500 hover:text-slate-800',
                                        )}
                                    >
                                        <span
                                            className={cn(
                                                'flex size-7 items-center justify-center rounded-full text-xs',
                                                done ? 'bg-emerald-500 text-white' : active ? 'bg-brand-600 text-brand-contrast' : 'bg-slate-200 text-slate-500',
                                            )}
                                        >
                                            {done ? <Check className="size-3.5" /> : <Icon className="size-3.5" />}
                                        </span>
                                        {s.label}
                                    </button>
                                </li>
                            );
                        })}
                    </ol>
                </nav>

                <main key={step} className="animate-slide-up rounded-2xl bg-white shadow-card ring-1 ring-slate-900/5">
                    <div className="border-b border-slate-100 px-6 py-5 sm:px-8">
                        <h1 className="text-xl font-semibold text-slate-900">{current.title}</h1>
                        <p className="mt-1 text-sm text-slate-500">{current.description}</p>
                    </div>
                    <StepBody step={step} props={props} brand={brand} setBrand={setBrand} onBack={() => go(step - 1)} onNext={() => go(step + 1)} />
                </main>
            </div>
        </div>
    );
}

function StepBody({ step, props, brand, setBrand, onBack, onNext }) {
    const { company, settings } = props;
    const initial = {
        1: { logo: null },
        2: { address: company.address ?? '', city: company.city ?? '', state: company.state ?? '', postal_code: company.postal_code ?? '', country: company.country ?? '', phone: company.phone ?? '' },
        3: {
            accept_stripe: settings.accept_stripe,
            accept_bank_transfer: settings.accept_bank_transfer,
            accept_cash: settings.accept_cash,
            payment_gateway_mode: settings.payment_gateway_mode,
            stripe_public_key: settings.stripe_public_key ?? '',
            stripe_secret_key: '',
            bank_name: settings.bank_name ?? '',
            bank_account_name: settings.bank_account_name ?? '',
            bank_account_number: settings.bank_account_number ?? '',
            paypal_email: settings.paypal_email ?? '',
        },
        4: { tax_enabled: settings.tax_enabled, default_tax_rate: settings.default_tax_rate ?? '', tax_number: company.tax_number ?? '' },
        5: { primary_color: settings.primary_color, invoice_template: settings.invoice_template },
        6: { team_name: '', team_email: '', team_role: 'member' },
        7: {},
    }[step];

    const form = useForm(initial);
    const last = step === STEPS.length;

    const save = (e) => {
        e?.preventDefault();
        if (last) {
            router.post(route('onboarding.complete'));
            return;
        }
        form.post(route('onboarding.step', step), {
            preserveScroll: true,
            forceFormData: step === 1,
            onSuccess: () => onNext(),
        });
    };

    return (
        <form onSubmit={save} noValidate>
            <div className="px-6 py-6 sm:px-8">
                {step === 1 && <LogoStep form={form} company={company} />}
                {step === 2 && <AddressStep form={form} />}
                {step === 3 && <PaymentsStep form={form} settings={settings} />}
                {step === 4 && <TaxStep form={form} />}
                {step === 5 && <BrandStep form={form} props={props} brand={brand} setBrand={setBrand} />}
                {step === 6 && <TeamStep form={form} members={props.teamMembers} />}
                {step === 7 && <PlanStep plans={props.plans} currentPlanId={props.currentPlanId} currency={company.currency} />}
            </div>
            <div className="flex items-center justify-between gap-3 rounded-b-2xl border-t border-slate-100 bg-slate-50/60 px-6 py-4 sm:px-8">
                {step > 1 ? (
                    <Button variant="ghost" icon={ArrowLeft} onClick={onBack}>
                        Back
                    </Button>
                ) : (
                    <span />
                )}
                <div className="flex items-center gap-2">
                    {!last && step !== 1 ? (
                        <Button variant="ghost" onClick={onNext}>
                            Skip step
                        </Button>
                    ) : null}
                    <Button type="submit" loading={form.processing} iconRight={last ? CheckCircle2 : ArrowRight}>
                        {last ? 'Finish setup' : step === 1 && !form.data.logo ? 'Skip for now' : 'Save & continue'}
                    </Button>
                </div>
            </div>
        </form>
    );
}

function LogoStep({ form, company }) {
    const [preview, setPreview] = useState(company.logo_url);
    return (
        <label className="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-slate-200 px-6 py-12 text-center transition hover:border-brand-400 hover:bg-brand-50/40">
            {preview ? (
                <img src={preview} alt="Logo preview" className="max-h-24 max-w-[240px] object-contain" />
            ) : (
                <span className="flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-600">
                    <ImagePlus className="size-7" />
                </span>
            )}
            <span className="text-sm font-medium text-slate-800">{preview ? 'Click to replace' : 'Click to upload your logo'}</span>
            <span className="text-xs text-slate-500">PNG, JPG or WebP · up to 2 MB</span>
            <input
                type="file"
                accept="image/png,image/jpeg,image/webp,image/gif"
                className="sr-only"
                onChange={(e) => {
                    const file = e.target.files?.[0];
                    if (!file) return;
                    form.setData('logo', file);
                    setPreview(URL.createObjectURL(file));
                }}
            />
            <FieldError>{form.errors.logo}</FieldError>
        </label>
    );
}

function AddressStep({ form }) {
    const field = (name, label, props = {}) => (
        <FormField label={label} htmlFor={name} error={form.errors[name]} className={props.wide ? 'sm:col-span-2' : ''}>
            <Input id={name} value={form.data[name]} onChange={(e) => form.setData(name, e.target.value)} invalid={Boolean(form.errors[name])} autoComplete={props.autoComplete} />
        </FormField>
    );
    return (
        <div className="grid gap-4 sm:grid-cols-2">
            {field('address', 'Street address', { wide: true, autoComplete: 'street-address' })}
            {field('city', 'City', { autoComplete: 'address-level2' })}
            {field('state', 'State / region', { autoComplete: 'address-level1' })}
            {field('postal_code', 'Postal code', { autoComplete: 'postal-code' })}
            {field('country', 'Country', { autoComplete: 'country-name' })}
            {field('phone', 'Phone', { wide: true, autoComplete: 'tel' })}
        </div>
    );
}

function PaymentsStep({ form, settings }) {
    const d = form.data;
    return (
        <div className="space-y-6">
            <div className="divide-y divide-slate-100 rounded-xl ring-1 ring-slate-200">
                <div className="p-4">
                    <Switch label="Card payments (Stripe)" description="Clients pay online from the invoice link." checked={d.accept_stripe} onChange={(v) => form.setData('accept_stripe', v)} />
                    {d.accept_stripe ? (
                        <div className="mt-4 space-y-4">
                            <Segmented
                                value={d.payment_gateway_mode}
                                onChange={(v) => form.setData('payment_gateway_mode', v)}
                                options={[
                                    { value: 'app', label: 'Use platform account' },
                                    { value: 'own', label: 'Use my Stripe account' },
                                ]}
                            />
                            {d.payment_gateway_mode === 'own' ? (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <FormField label="Publishable key" htmlFor="stripe_public_key" error={form.errors.stripe_public_key}>
                                        <Input id="stripe_public_key" value={d.stripe_public_key} onChange={(e) => form.setData('stripe_public_key', e.target.value)} placeholder="pk_live_…" />
                                    </FormField>
                                    <FormField
                                        label="Secret key"
                                        htmlFor="stripe_secret_key"
                                        error={form.errors.stripe_secret_key}
                                        hint={settings.has_stripe_secret ? 'A secret key is saved. Leave blank to keep it.' : 'Saved securely on the server and never shown in the browser again.'}
                                    >
                                        <Input id="stripe_secret_key" type="password" autoComplete="off" value={d.stripe_secret_key} onChange={(e) => form.setData('stripe_secret_key', e.target.value)} placeholder={settings.has_stripe_secret ? '••••••••••••' : 'sk_live_…'} />
                                    </FormField>
                                </div>
                            ) : (
                                <p className="text-sm text-slate-500">Payments are collected by the platform and paid out to you according to the payout schedule.</p>
                            )}
                        </div>
                    ) : null}
                </div>
                <div className="p-4">
                    <Switch label="Bank transfer" description="Show your bank details on invoices." checked={d.accept_bank_transfer} onChange={(v) => form.setData('accept_bank_transfer', v)} />
                    {d.accept_bank_transfer ? (
                        <div className="mt-4 grid gap-4 sm:grid-cols-3">
                            <FormField label="Bank name" error={form.errors.bank_name}>
                                <Input value={d.bank_name} onChange={(e) => form.setData('bank_name', e.target.value)} />
                            </FormField>
                            <FormField label="Account name" error={form.errors.bank_account_name}>
                                <Input value={d.bank_account_name} onChange={(e) => form.setData('bank_account_name', e.target.value)} />
                            </FormField>
                            <FormField label="Account number / IBAN" error={form.errors.bank_account_number}>
                                <Input value={d.bank_account_number} onChange={(e) => form.setData('bank_account_number', e.target.value)} />
                            </FormField>
                        </div>
                    ) : null}
                </div>
                <div className="p-4">
                    <Switch label="Cash" description="Record cash payments manually." checked={d.accept_cash} onChange={(v) => form.setData('accept_cash', v)} />
                </div>
            </div>
            <FormField label="PayPal email (optional)" htmlFor="paypal_email" error={form.errors.paypal_email}>
                <Input id="paypal_email" type="email" value={d.paypal_email} onChange={(e) => form.setData('paypal_email', e.target.value)} />
            </FormField>
        </div>
    );
}

function TaxStep({ form }) {
    return (
        <div className="space-y-5">
            <Switch label="Charge tax on invoices" description="Adds a tax line to new invoices by default." checked={form.data.tax_enabled} onChange={(v) => form.setData('tax_enabled', v)} />
            <div className="grid gap-4 sm:grid-cols-2">
                <FormField label="Default tax rate" htmlFor="rate" error={form.errors.default_tax_rate}>
                    <Input id="rate" type="number" min="0" max="100" step="0.01" suffix="%" value={form.data.default_tax_rate} disabled={!form.data.tax_enabled} onChange={(e) => form.setData('default_tax_rate', e.target.value)} />
                </FormField>
                <FormField label="Tax / VAT number" htmlFor="tax_number" error={form.errors.tax_number} hint="Printed on your invoices.">
                    <Input id="tax_number" value={form.data.tax_number} onChange={(e) => form.setData('tax_number', e.target.value)} />
                </FormField>
            </div>
        </div>
    );
}

function BrandStep({ form, props, brand, setBrand }) {
    const setColor = (c) => {
        form.setData('primary_color', c);
        setBrand(c);
    };
    return (
        <div className="space-y-8">
            <div>
                <h3 className="mb-3 text-sm font-medium text-slate-700">Brand colour</h3>
                <div className="flex flex-wrap items-center gap-3">
                    {Object.entries(props.colorPresets).map(([name, p]) => (
                        <button
                            key={name}
                            type="button"
                            onClick={() => setColor(p.primary)}
                            className={cn('size-10 rounded-full ring-offset-2 transition hover:scale-110', brand === p.primary && 'ring-2 ring-slate-900')}
                            style={{ background: p.primary }}
                            aria-label={name}
                            title={name}
                        />
                    ))}
                    <label className="relative flex size-10 cursor-pointer items-center justify-center overflow-hidden rounded-full ring-1 ring-slate-300" title="Custom colour">
                        <input type="color" value={brand} onChange={(e) => setColor(e.target.value)} className="absolute inset-0 size-full cursor-pointer opacity-0" />
                        <Palette className="size-4 text-slate-500" />
                    </label>
                    <span className="ml-2 font-mono text-sm text-slate-500">{brand}</span>
                </div>
                <FieldError>{form.errors.primary_color}</FieldError>
            </div>

            <div>
                <h3 className="mb-3 text-sm font-medium text-slate-700">Invoice design</h3>
                <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-4">
                    {Object.entries(props.invoiceTemplates).map(([key, t]) => {
                        const active = form.data.invoice_template === key;
                        return (
                            <button key={key} type="button" onClick={() => form.setData('invoice_template', key)} className="group text-left">
                                <div className={cn('overflow-hidden rounded-xl ring-1 transition', active ? 'ring-2 ring-brand-500 ring-offset-2' : 'ring-slate-200 group-hover:ring-slate-300')}>
                                    <TemplateThumbnail src={route('settings.template.preview', key)} />
                                </div>
                                <div className="mt-2 flex items-center justify-between">
                                    <span className="text-sm font-medium text-slate-800">{t.name}</span>
                                    {active ? <Check className="size-4 text-brand-600" /> : null}
                                </div>
                            </button>
                        );
                    })}
                </div>
            </div>
        </div>
    );
}

function TeamStep({ form, members }) {
    return (
        <div className="space-y-6">
            {members.length ? (
                <ul className="divide-y divide-slate-100 rounded-xl ring-1 ring-slate-200">
                    {members.map((m) => (
                        <li key={m.id} className="flex items-center justify-between px-4 py-3 text-sm">
                            <span>
                                <span className="font-medium text-slate-900">{m.name}</span> <span className="text-slate-500">{m.email}</span>
                            </span>
                            <Badge tone="gray">{m.role}</Badge>
                        </li>
                    ))}
                </ul>
            ) : null}
            <div className="grid gap-4 sm:grid-cols-[1fr_1fr_160px]">
                <FormField label="Name" error={form.errors.team_name}>
                    <Input value={form.data.team_name} onChange={(e) => form.setData('team_name', e.target.value)} />
                </FormField>
                <FormField label="Email" error={form.errors.team_email}>
                    <Input type="email" value={form.data.team_email} onChange={(e) => form.setData('team_email', e.target.value)} />
                </FormField>
                <FormField label="Role" error={form.errors.team_role}>
                    <Select value={form.data.team_role} onChange={(e) => form.setData('team_role', e.target.value)} options={{ member: 'Member', admin: 'Admin' }} />
                </FormField>
            </div>
            <p className="text-sm text-slate-500">Leave blank to skip. You can invite more people later under Team.</p>
        </div>
    );
}

function PlanStep({ plans, currentPlanId, currency }) {
    return (
        <div className="grid gap-4 md:grid-cols-3">
            {plans.map((p) => {
                const current = p.id === currentPlanId;
                const paid = Number(p.price) > 0;
                return (
                    <div key={p.id} className={cn('flex flex-col rounded-2xl p-5 ring-1', current ? 'bg-brand-50/50 ring-2 ring-brand-500' : 'ring-slate-200')}>
                        <div className="flex items-center justify-between">
                            <h3 className="font-semibold text-slate-900">{p.name}</h3>
                            {current ? <Badge tone="brand">Current</Badge> : null}
                        </div>
                        <p className="mt-3">
                            <span className="text-3xl font-semibold tracking-tight text-slate-900">{paid ? formatMoney(p.price, 'USD') : 'Free'}</span>
                            {paid ? <span className="text-sm text-slate-500"> / {p.billing_cycle === 'yearly' ? 'year' : 'month'}</span> : null}
                        </p>
                        <ul className="mt-4 flex-1 space-y-2 text-sm text-slate-600">
                            <li className="flex gap-2">
                                <Check className="size-4 text-emerald-500" /> {p.max_invoices ? `${p.max_invoices} invoices / month` : 'Unlimited invoices'}
                            </li>
                            <li className="flex gap-2">
                                <Check className="size-4 text-emerald-500" /> {p.max_clients ? `${p.max_clients} clients` : 'Unlimited clients'}
                            </li>
                            <li className="flex gap-2">
                                <Check className="size-4 text-emerald-500" /> {p.max_users ? `${p.max_users} team members` : 'Unlimited team'}
                            </li>
                            {(p.features ?? []).slice(0, 3).map((f) => (
                                <li key={f} className="flex gap-2">
                                    <Check className="size-4 text-emerald-500" /> {f}
                                </li>
                            ))}
                        </ul>
                        {!current ? (
                            paid ? (
                                <Button href={route('settings.subscription.payment', p.id)} variant="secondary" className="mt-5 w-full">
                                    Upgrade
                                </Button>
                            ) : (
                                <Button variant="secondary" className="mt-5 w-full" onClick={() => router.post(route('settings.subscription.change', p.id))}>
                                    Switch to {p.name}
                                </Button>
                            )
                        ) : null}
                    </div>
                );
            })}
            {!plans.length ? <p className="text-sm text-slate-500">No plans are configured yet.</p> : null}
            <p className="text-xs text-slate-400 md:col-span-3">Prices shown in USD.{currency && currency !== 'USD' ? ' Your invoices will still use your own currency.' : ''}</p>
        </div>
    );
}
