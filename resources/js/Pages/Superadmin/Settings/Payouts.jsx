import { useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { Button, Card, CardBody, CardFooter, FormField, Input, PageHeader } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatMoney } from '@/lib/utils';

export default function PayoutSettings({ settings }) {
    const form = useForm(settings);
    const example = 1000;
    const fee = (example * (parseFloat(form.data.payout_fee_percentage) || 0)) / 100;

    return (
        <>
            <PageHeader title="Payout settings" description="Rules for tenants withdrawing card payments collected by the platform." />
            <form
                onSubmit={(ev) => {
                    ev.preventDefault();
                    form.put(route('superadmin.settings.payouts.update'), { preserveScroll: true });
                }}
                noValidate
                className="max-w-2xl"
            >
                <Card>
                    <CardBody className="grid gap-4 sm:grid-cols-2">
                        <FormField label="Platform fee" error={form.errors.payout_fee_percentage}>
                            <Input type="number" min="0" max="100" step="0.01" suffix="%" value={form.data.payout_fee_percentage} onChange={(e) => form.setData('payout_fee_percentage', e.target.value)} />
                        </FormField>
                        <FormField label="Minimum payout" error={form.errors.payout_minimum_amount}>
                            <Input type="number" min="0" step="0.01" prefix="$" value={form.data.payout_minimum_amount} onChange={(e) => form.setData('payout_minimum_amount', e.target.value)} />
                        </FormField>
                        <p className="rounded-lg bg-slate-50 p-3 text-sm text-slate-600 sm:col-span-2">
                            Example: on a {formatMoney(example, 'USD')} payout the platform keeps {formatMoney(fee, 'USD')} and the tenant receives {formatMoney(example - fee, 'USD')}.
                        </p>
                    </CardBody>
                    <CardFooter>
                        <Button type="submit" icon={Save} loading={form.processing}>
                            Save
                        </Button>
                    </CardFooter>
                </Card>
            </form>
        </>
    );
}
