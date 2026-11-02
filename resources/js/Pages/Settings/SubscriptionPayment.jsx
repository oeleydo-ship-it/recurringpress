import { usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import StripeCheckout from '@/Components/StripeCheckout';
import { Card, CardBody, CardHeader, PageHeader } from '@/Components/ui';
import { PERIOD_LABELS, periodSuffix } from '@/lib/billing';
import { route } from '@/lib/route';
import { formatMoney } from '@/lib/utils';

const TERMS = {
    monthly: 'Covers one month. Your card is not charged automatically: pay again from the billing page to renew.',
    yearly: 'Covers one year. Your card is not charged automatically: pay again from the billing page to renew.',
    lifetime: 'One payment. No renewals and no expiry.',
};

export default function SubscriptionPayment({ plan, period, amount, replacesLifetime, stripeKey, intentUrl, returnUrl }) {
    const { auth } = usePage().props;

    return (
        <>
            <PageHeader title={`${plan.name} · ${PERIOD_LABELS[period]}`} back={route('settings.subscription')} />
            <div className="grid max-w-4xl gap-6 md:grid-cols-[1fr_320px]">
                <Card>
                    <CardHeader title="Payment details" />
                    <CardBody>
                        <StripeCheckout publishableKey={stripeKey} intentUrl={intentUrl} returnUrl={returnUrl} amount={amount} currency="USD" brand={auth.theme?.primary} />
                    </CardBody>
                </Card>
                <Card className="h-fit">
                    <CardHeader title="Order summary" />
                    <CardBody className="space-y-4 text-sm">
                        <div className="flex justify-between">
                            <span className="font-medium text-slate-900">{plan.name}</span>
                            <span className="font-semibold">
                                {formatMoney(amount, 'USD')} {periodSuffix(period)}
                            </span>
                        </div>
                        <p className="text-slate-500">{TERMS[period]}</p>
                        {period === 'yearly' && plan.yearly_saving_percent ? <p className="font-medium text-emerald-600">You save {plan.yearly_saving_percent}% compared to monthly.</p> : null}
                        {replacesLifetime ? <p className="rounded-lg bg-amber-50 p-3 text-amber-800">This replaces your lifetime access to {replacesLifetime}.</p> : null}
                        <ul className="space-y-2 text-slate-600">
                            {[
                                plan.max_invoices != null ? `${plan.max_invoices} invoices` : 'Unlimited invoices',
                                plan.max_clients != null ? `${plan.max_clients} clients` : 'Unlimited clients',
                                plan.max_users != null ? `${plan.max_users} team members` : 'Unlimited team',
                            ].map((t) => (
                                <li key={t} className="flex items-center gap-2">
                                    <Check className="size-4 text-emerald-500" /> {t}
                                </li>
                            ))}
                        </ul>
                        <div className="flex justify-between border-t border-slate-100 pt-4 text-base font-semibold text-slate-900">
                            <span>Due today</span>
                            <span>{formatMoney(amount, 'USD')}</span>
                        </div>
                    </CardBody>
                </Card>
            </div>
        </>
    );
}
