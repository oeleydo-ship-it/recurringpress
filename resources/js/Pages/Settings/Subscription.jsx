import { router, usePage } from '@inertiajs/react';
import { Check, Sparkles } from 'lucide-react';
import { Badge, Button, Card, PageHeader, useConfirm } from '@/Components/ui';
import { route } from '@/lib/route';
import { cn, formatDate, formatMoney } from '@/lib/utils';

export default function Subscription({ currentPlanId, status, endsAt, plans, usage, canPayOnline, hasPlanAccess }) {
    const { auth } = usePage().props;
    const confirm = useConfirm();
    const current = plans.find((p) => p.id === currentPlanId);

    const choose = async (plan) => {
        if (plan.price <= 0) {
            if (await confirm({ title: `Switch to ${plan.name}?`, message: 'Plan limits apply immediately.', confirmText: 'Switch plan', tone: 'brand' })) {
                router.post(route('settings.subscription.change', plan.id));
            }
        } else {
            router.visit(route('settings.subscription.payment', plan.id));
        }
    };

    const limitRow = (label, used, max) => (
        <li className="flex items-center gap-2">
            <Check className="size-4 shrink-0 text-emerald-500" />
            <span>
                {max != null ? `${max} ${label}` : `Unlimited ${label}`}
                {used !== undefined && max != null ? <span className="text-slate-400"> · {used} used</span> : null}
            </span>
        </li>
    );

    return (
        <>
            <PageHeader
                title="Subscription"
                back={route('settings.index')}
                description={
                    current ? (
                        <>
                            You're on <span className="font-medium text-slate-800">{current.name}</span>
                            {status === 'trial' ? ' (trial)' : ''}
                            {endsAt ? ` · ${status === 'trial' ? 'trial ends' : 'renews'} ${formatDate(endsAt, auth.company?.date_format)}` : ''}
                        </>
                    ) : null
                }
            />
            <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                {plans.map((p) => {
                    const isCurrent = p.id === currentPlanId;
                    return (
                        <Card key={p.id} className={cn('relative flex flex-col p-6', isCurrent && 'ring-2 ring-brand-500', p.is_popular && !isCurrent && 'ring-2 ring-slate-900')}>
                            {p.is_popular ? (
                                <span className="absolute -top-3 left-6 inline-flex items-center gap-1 rounded-full bg-slate-900 px-3 py-1 text-xs font-semibold text-white">
                                    <Sparkles className="size-3" /> Most popular
                                </span>
                            ) : null}
                            <div className="flex items-center justify-between">
                                <h3 className="text-lg font-semibold text-slate-900">{p.name}</h3>
                                {isCurrent ? <Badge tone="brand">Current plan</Badge> : null}
                            </div>
                            {p.description ? <p className="mt-1 text-sm text-slate-500">{p.description}</p> : null}
                            <p className="mt-5">
                                <span className="text-4xl font-semibold tracking-tight text-slate-900">{p.price > 0 ? formatMoney(p.price, 'USD') : 'Free'}</span>
                                {p.price > 0 ? <span className="text-sm text-slate-500"> / {p.billing_cycle === 'yearly' ? 'year' : 'month'}</span> : null}
                            </p>
                            <ul className="mt-6 flex-1 space-y-2.5 text-sm text-slate-600">
                                {limitRow('invoices', usage.invoices, p.max_invoices)}
                                {limitRow('clients', usage.clients, p.max_clients)}
                                {limitRow('team members', usage.users, p.max_users)}
                                {limitRow('products', usage.products, p.max_products)}
                                {[p.price > 0 && p.trial_days > 0 && `${p.trial_days}-day trial for new accounts`, p.stripe_enabled && 'Online card payments', p.custom_branding && 'Custom branding', p.white_label && 'White label', p.custom_domain && 'Custom domain', p.subdomain && 'Platform subdomain', ...(p.features ?? [])].filter(Boolean).map((f) => (
                                    <li key={f} className="flex items-center gap-2">
                                        <Check className="size-4 shrink-0 text-emerald-500" /> {f}
                                    </li>
                                ))}
                            </ul>
                            <Button
                                className="mt-6 w-full"
                                variant={isCurrent ? 'secondary' : p.is_popular ? 'primary' : 'secondary'}
                                disabled={(isCurrent && hasPlanAccess) || (p.price > 0 && !canPayOnline)}
                                onClick={() => choose(p)}
                            >
                                {isCurrent && hasPlanAccess ? 'Current plan' : p.price > 0 ? (canPayOnline ? `Upgrade to ${p.name}` : 'Contact support') : `Switch to ${p.name}`}
                            </Button>
                        </Card>
                    );
                })}
            </div>
        </>
    );
}
