import { usePage } from '@inertiajs/react';
import { Check } from 'lucide-react';
import StripeCheckout from '@/Components/StripeCheckout';
import { Card, CardBody, CardHeader, PageHeader } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatMoney } from '@/lib/utils';

export default function SubscriptionPayment({ plan, stripeKey, intentUrl, returnUrl }) {
    const { auth } = usePage().props;
    const cycle = plan.billing_cycle === 'yearly' ? 'year' : 'month';

    return (
        <>
            <PageHeader title={`Upgrade to ${plan.name}`} back={route('settings.subscription')} />
            <div className="grid max-w-4xl gap-6 md:grid-cols-[1fr_320px]">
                <Card>
                    <CardHeader title="Payment details" />
                    <CardBody>
                        <StripeCheckout publishableKey={stripeKey} intentUrl={intentUrl} returnUrl={returnUrl} amount={plan.price} currency="USD" brand={auth.theme?.primary} />
                    </CardBody>
                </Card>
                <Card className="h-fit">
                    <CardHeader title="Order summary" />
                    <CardBody className="space-y-4 text-sm">
                        <div className="flex justify-between">
                            <span className="font-medium text-slate-900">{plan.name}</span>
                            <span className="font-semibold">{formatMoney(plan.price, 'USD')}</span>
                        </div>
                        <p className="text-slate-500">Billed per {cycle}.</p>
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
                            <span>{formatMoney(plan.price, 'USD')}</span>
                        </div>
                    </CardBody>
                </Card>
            </div>
        </>
    );
}
