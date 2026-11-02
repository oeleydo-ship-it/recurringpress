import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import { Check, Sparkles } from 'lucide-react';
import BillingPeriodToggle from '@/Components/BillingPeriodToggle';
import { Badge, Button, Card, PageHeader, useConfirm } from '@/Components/ui';
import { PERIOD_LABELS, isFreePlan, offeredPeriods, periodSuffix } from '@/lib/billing';
import { route } from '@/lib/route';
import { cn, formatDate, formatMoney } from '@/lib/utils';

export default function Subscription({ currentPlanId, status, endsAt, billingPeriod, isLifetime, plans, usage, canPayOnline, hasPlanAccess }) {
    const { auth } = usePage().props;
    const confirm = useConfirm();
    const current = plans.find((p) => p.id === currentPlanId);
    const periods = offeredPeriods(plans);
    const [period, setPeriod] = useState(periods.includes(billingPeriod) ? billingPeriod : periods[0] ?? 'monthly');
    const isCurrentLifetime = (plan) => isLifetime && plan.id === currentPlanId;
    // Plans that can't be bought on the selected period are hidden, except the one the company is on.
    const visiblePlans = plans.filter((p) => isFreePlan(p) || p.prices[period] != null || p.id === currentPlanId);

    const choose = async (plan, planPeriod) => {
        if (isFreePlan(plan)) {
            const message = isLifetime ? 'You will give up your lifetime access. Plan limits apply immediately.' : 'Plan limits apply immediately.';
            if (await confirm({ title: `Switch to ${plan.name}?`, message, confirmText: 'Switch plan', tone: 'brand' })) {
                router.post(route('settings.subscription.change', plan.id));
            }
        } else {
            router.visit(route('settings.subscription.payment', { plan: plan.id, period: planPeriod }));
        }
    };

    const buttonLabel = (plan, planPeriod) => {
        const isCurrent = plan.id === currentPlanId;
        if (isCurrentLifetime(plan)) return 'Lifetime access';
        if (isFreePlan(plan)) return isCurrent ? 'Current plan' : `Switch to ${plan.name}`;
        if (!canPayOnline) return isCurrent && hasPlanAccess ? 'Current plan' : 'Contact support';
        if (!isCurrent) return planPeriod === 'lifetime' ? `Buy ${plan.name} for life` : `Upgrade to ${plan.name}`;
        if (!hasPlanAccess) return 'Renew now';
        if (status === 'trial') return 'Subscribe now';
        if (planPeriod === billingPeriod) return 'Renew early';
        return planPeriod === 'lifetime' ? 'Switch to lifetime' : `Switch to ${planPeriod}`;
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
                            {isLifetime ? ' · Lifetime, no renewal needed' : ''}
                            {!isLifetime && billingPeriod && status !== 'trial' ? ` · ${PERIOD_LABELS[billingPeriod]}` : ''}
                            {!isLifetime && endsAt ? ` · ${status === 'trial' ? 'trial ends' : hasPlanAccess ? 'renew by' : 'ended'} ${formatDate(endsAt, auth.company?.date_format)}` : ''}
                        </>
                    ) : null
                }
            />
            {periods.length > 1 ? (
                <div className="mb-6 flex justify-center">
                    <BillingPeriodToggle periods={periods} value={period} onChange={setPeriod} />
                </div>
            ) : null}
            <div className="grid gap-6 md:grid-cols-2 xl:grid-cols-3">
                {visiblePlans.map((p) => {
                    const isCurrent = p.id === currentPlanId;
                    const free = isFreePlan(p);
                    // The current plan keeps showing what the company pays when the selected period isn't offered.
                    const planPeriod = isCurrentLifetime(p) ? 'lifetime' : p.prices[period] != null ? period : billingPeriod;
                    const price = p.prices[planPeriod];
                    const purchasable = !free && price != null && !isCurrentLifetime(p);
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
                                <span className="text-4xl font-semibold tracking-tight text-slate-900">{free ? 'Free' : price != null ? formatMoney(price, 'USD') : '—'}</span>
                                {!free && price != null ? <span className="text-sm text-slate-500"> {periodSuffix(planPeriod)}</span> : null}
                            </p>
                            {planPeriod === 'yearly' && p.yearly_saving_percent ? <p className="mt-1 text-sm font-medium text-emerald-600">Save {p.yearly_saving_percent}% compared to monthly</p> : null}
                            {planPeriod === 'lifetime' && !free ? <p className="mt-1 text-sm font-medium text-emerald-600">Pay once, no renewals</p> : null}
                            <ul className="mt-6 flex-1 space-y-2.5 text-sm text-slate-600">
                                {limitRow('invoices', usage.invoices, p.max_invoices)}
                                {limitRow('clients', usage.clients, p.max_clients)}
                                {limitRow('team members', usage.users, p.max_users)}
                                {limitRow('products', usage.products, p.max_products)}
                                {[!free && p.trial_days > 0 && `${p.trial_days}-day trial for new accounts`, p.stripe_enabled && 'Online card payments', p.custom_branding && 'Custom branding', p.white_label && 'White label', p.custom_domain && 'Custom domain', p.subdomain && 'Platform subdomain', p.client_portal && 'Client portal', p.ai_requests > 0 && `AI assistant · ${p.ai_requests} requests/month`,...(p.features ?? [])].filter(Boolean).map((f) => (
                                    <li key={f} className="flex items-center gap-2">
                                        <Check className="size-4 shrink-0 text-emerald-500" /> {f}
                                    </li>
                                ))}
                            </ul>
                            <Button
                                className="mt-6 w-full"
                                variant={isCurrent ? 'secondary' : p.is_popular ? 'primary' : 'secondary'}
                                disabled={free ? isCurrent : !purchasable || !canPayOnline}
                                onClick={() => choose(p, planPeriod)}
                            >
                                {buttonLabel(p, planPeriod)}
                            </Button>
                        </Card>
                    );
                })}
            </div>
        </>
    );
}
