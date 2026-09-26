import { useForm, usePage } from '@inertiajs/react';
import { Landmark, Send, Wallet } from 'lucide-react';
import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Segmented, Textarea } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatMoney } from '@/lib/utils';

export default function PayoutCreate({ available, minimum, feePercentage, paymentCount, defaults }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const fee = Math.round(available * feePercentage) / 100;
    const form = useForm({
        payout_method: 'bank_transfer',
        bank_name: defaults.bank_name,
        bank_account_name: defaults.bank_account_name,
        bank_account_number: defaults.bank_account_number,
        bank_routing_number: defaults.bank_routing_number,
        bank_iban: '',
        bank_swift: '',
        paypal_email: defaults.paypal_email,
        notes: '',
    });
    const d = form.data;
    const e = form.errors;
    const field = (name, label, props = {}) => (
        <FormField label={label} required={props.required} error={e[name]} className={props.className}>
            <Input value={d[name] ?? ''} onChange={(ev) => form.setData(name, ev.target.value)} invalid={Boolean(e[name])} type={props.type} autoComplete="off" />
        </FormField>
    );

    return (
        <>
            <PageHeader title="Request a payout" back={route('payouts.index')} />
            <form
                onSubmit={(ev) => {
                    ev.preventDefault();
                    form.post(route('payouts.store'));
                }}
                noValidate
                className="grid max-w-5xl gap-6 lg:grid-cols-3"
            >
                <Card className="lg:col-span-2">
                    <CardHeader title="Where should we send it?" icon={Landmark} />
                    <CardBody className="space-y-5">
                        <Segmented
                            value={d.payout_method}
                            onChange={(v) => form.setData('payout_method', v)}
                            options={[
                                { value: 'bank_transfer', label: 'Bank transfer' },
                                { value: 'paypal', label: 'PayPal' },
                            ]}
                        />
                        {d.payout_method === 'bank_transfer' ? (
                            <div className="grid gap-4 sm:grid-cols-2">
                                {field('bank_name', 'Bank name', { required: true })}
                                {field('bank_account_name', 'Account holder', { required: true })}
                                {field('bank_account_number', 'Account number')}
                                {field('bank_routing_number', 'Routing / sort code')}
                                {field('bank_iban', 'IBAN')}
                                {field('bank_swift', 'SWIFT / BIC')}
                            </div>
                        ) : (
                            field('paypal_email', 'PayPal email', { required: true, type: 'email' })
                        )}
                        <FormField label="Note for the payments team" error={e.notes}>
                            <Textarea rows={2} value={d.notes} onChange={(ev) => form.setData('notes', ev.target.value)} />
                        </FormField>
                    </CardBody>
                </Card>

                <Card className="h-fit">
                    <CardHeader title="Summary" icon={Wallet} />
                    <CardBody>
                        <dl className="space-y-3 text-sm">
                            <div className="flex justify-between">
                                <dt className="text-slate-500">Available ({paymentCount} payments)</dt>
                                <dd className="font-medium">{formatMoney(available, currency)}</dd>
                            </div>
                            <div className="flex justify-between">
                                <dt className="text-slate-500">Fee ({feePercentage}%)</dt>
                                <dd className="font-medium">− {formatMoney(fee, currency)}</dd>
                            </div>
                            <div className="flex justify-between border-t border-slate-200 pt-3 text-base">
                                <dt className="font-semibold text-slate-900">You receive</dt>
                                <dd className="font-semibold text-slate-900">{formatMoney(available - fee, currency)}</dd>
                            </div>
                        </dl>
                        <p className="mt-4 text-xs text-slate-500">
                            Payouts cover your full available balance. Minimum payout: {formatMoney(minimum, currency)}.
                        </p>
                    </CardBody>
                    <CardFooter>
                        <Button type="submit" icon={Send} loading={form.processing} disabled={available < minimum} className="w-full">
                            Request {formatMoney(available - fee, currency)}
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </>
    );
}
