import { useForm, usePage } from '@inertiajs/react';
import { Download, Package, Save, Wrench } from 'lucide-react';
import { Button, Card, CardBody, CardFooter, CardHeader, FormField, Input, PageHeader, Select, Switch, Textarea } from '@/Components/ui';
import { CustomFieldsCard, initialCustomValues } from '@/Components/CustomFields';
import { route } from '@/lib/route';
import { cn } from '@/lib/utils';

const types = [
    { value: 'service', label: 'Service', description: 'Time, work or expertise', icon: Wrench },
    { value: 'physical', label: 'Physical', description: 'Goods you ship or hand over', icon: Package },
    { value: 'digital', label: 'Digital', description: 'Downloads, licences, access', icon: Download },
];

export default function ProductForm({ product, units, billingCycles, customFields = [] }) {
    const { auth } = usePage().props;
    const editing = Boolean(product);
    const form = useForm({
        name: product?.name ?? '',
        description: product?.description ?? '',
        price: product?.price ?? '',
        unit: product?.unit ?? 'unit',
        tax_rate: product?.tax_rate ?? '',
        sku: product?.sku ?? '',
        type: product?.type ?? 'service',
        is_active: product?.is_active ?? true,
        track_stock: product?.track_stock ?? false,
        stock_quantity: product?.stock_quantity ?? '',
        low_stock_threshold: product?.low_stock_threshold ?? '',
        is_recurring: product?.is_recurring ?? false,
        billing_cycle: product?.billing_cycle ?? 'monthly',
        billing_cycle_count: product?.billing_cycle_count ?? '',
        has_trial: product?.has_trial ?? false,
        trial_days: product?.trial_days ?? 14,
        auto_renew: product?.auto_renew ?? true,
        custom_values: initialCustomValues(customFields, product?.custom_values),
    });
    const d = form.data;
    const e = form.errors;

    const submit = (ev) => {
        ev.preventDefault();
        if (editing) form.put(route('products.update', product.id));
        else form.post(route('products.store'));
    };

    return (
        <>
            <PageHeader title={editing ? `Edit ${product.name}` : 'New product'} back={route('products.index')} />
            <form onSubmit={submit} noValidate className="grid gap-6 lg:grid-cols-3">
                <div className="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader title="Details" />
                        <CardBody className="space-y-5">
                            <div className="grid gap-3 sm:grid-cols-3">
                                {types.map((t) => {
                                    const Icon = t.icon;
                                    const active = d.type === t.value;
                                    return (
                                        <button
                                            key={t.value}
                                            type="button"
                                            onClick={() => form.setData('type', t.value)}
                                            className={cn(
                                                'flex items-start gap-3 rounded-xl p-3 text-left ring-1 transition',
                                                active ? 'bg-brand-50/60 ring-2 ring-brand-500' : 'ring-slate-200 hover:ring-slate-300',
                                            )}
                                        >
                                            <Icon className={cn('mt-0.5 size-5', active ? 'text-brand-600' : 'text-slate-400')} />
                                            <span>
                                                <span className="block text-sm font-medium text-slate-900">{t.label}</span>
                                                <span className="block text-xs text-slate-500">{t.description}</span>
                                            </span>
                                        </button>
                                    );
                                })}
                            </div>
                            <FormField label="Name" htmlFor="name" required error={e.name}>
                                <Input id="name" value={d.name} onChange={(ev) => form.setData('name', ev.target.value)} invalid={Boolean(e.name)} placeholder="e.g. Website design" />
                            </FormField>
                            <FormField label="Description" htmlFor="description" error={e.description} hint="Shown under the line item on invoices.">
                                <Textarea id="description" rows={3} value={d.description} onChange={(ev) => form.setData('description', ev.target.value)} />
                            </FormField>
                            <div className="grid gap-4 sm:grid-cols-3">
                                <FormField label="Price" htmlFor="price" required error={e.price}>
                                    <Input
                                        id="price"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                        prefix={auth.company?.currency_symbol}
                                        value={d.price}
                                        onChange={(ev) => form.setData('price', ev.target.value)}
                                        invalid={Boolean(e.price)}
                                    />
                                </FormField>
                                <FormField label="Per" htmlFor="unit" error={e.unit}>
                                    <Select id="unit" value={d.unit} onChange={(ev) => form.setData('unit', ev.target.value)} options={units} />
                                </FormField>
                                <FormField label="Tax rate" htmlFor="tax_rate" error={e.tax_rate}>
                                    <Input id="tax_rate" type="number" min="0" max="100" step="0.01" suffix="%" value={d.tax_rate} onChange={(ev) => form.setData('tax_rate', ev.target.value)} />
                                </FormField>
                            </div>
                            <FormField label="SKU" htmlFor="sku" error={e.sku} hint="Optional internal reference.">
                                <Input id="sku" value={d.sku} onChange={(ev) => form.setData('sku', ev.target.value)} className="font-mono sm:max-w-xs" />
                            </FormField>
                        </CardBody>
                    </Card>

                    <Card>
                        <CardHeader title="Recurring billing" description="Bill this automatically on a schedule via recurring invoices." />
                        <CardBody className="space-y-5">
                            <Switch label="This is a subscription / recurring item" checked={d.is_recurring} onChange={(v) => form.setData('is_recurring', v)} />
                            {d.is_recurring ? (
                                <div className="space-y-5 rounded-xl bg-slate-50 p-4">
                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <FormField label="Billing cycle" error={e.billing_cycle}>
                                            <Select value={d.billing_cycle ?? ''} onChange={(ev) => form.setData('billing_cycle', ev.target.value)} options={billingCycles} />
                                        </FormField>
                                        <FormField label="Number of cycles" error={e.billing_cycle_count} hint="Leave blank to bill until cancelled.">
                                            <Input type="number" min="1" value={d.billing_cycle_count ?? ''} onChange={(ev) => form.setData('billing_cycle_count', ev.target.value)} />
                                        </FormField>
                                    </div>
                                    <Switch label="Free trial" checked={d.has_trial} onChange={(v) => form.setData('has_trial', v)} />
                                    {d.has_trial ? (
                                        <FormField label="Trial length" error={e.trial_days}>
                                            <Input type="number" min="1" max="365" suffix="days" value={d.trial_days ?? ''} onChange={(ev) => form.setData('trial_days', ev.target.value)} className="sm:max-w-[200px]" />
                                        </FormField>
                                    ) : null}
                                    <Switch label="Auto-renew" description="Keep billing when the cycles run out." checked={d.auto_renew} onChange={(v) => form.setData('auto_renew', v)} />
                                </div>
                            ) : null}
                        </CardBody>
                    </Card>

                    <CustomFieldsCard fields={customFields} values={d.custom_values} onChange={(v) => form.setData('custom_values', v)} errors={e} />
                </div>

                <div className="space-y-6">
                    {d.type === 'physical' ? (
                        <Card>
                            <CardHeader title="Inventory" />
                            <CardBody className="space-y-5">
                                <Switch label="Track stock" checked={d.track_stock} onChange={(v) => form.setData('track_stock', v)} />
                                {d.track_stock ? (
                                    <>
                                        <FormField label="In stock" error={e.stock_quantity}>
                                            <Input type="number" min="0" value={d.stock_quantity ?? ''} onChange={(ev) => form.setData('stock_quantity', ev.target.value)} />
                                        </FormField>
                                        <FormField label="Low-stock alert at" error={e.low_stock_threshold}>
                                            <Input type="number" min="0" value={d.low_stock_threshold ?? ''} onChange={(ev) => form.setData('low_stock_threshold', ev.target.value)} />
                                        </FormField>
                                    </>
                                ) : null}
                            </CardBody>
                        </Card>
                    ) : null}
                    {editing ? (
                        <Card>
                            <CardBody>
                                <Switch label="Active" description="Inactive products are hidden from pickers." checked={d.is_active} onChange={(v) => form.setData('is_active', v)} />
                            </CardBody>
                        </Card>
                    ) : null}
                    <Card>
                        <CardFooter className="rounded-xl border-t-0 bg-white">
                            <Button variant="secondary" href={route('products.index')}>
                                Cancel
                            </Button>
                            <Button type="submit" icon={Save} loading={form.processing}>
                                {editing ? 'Save changes' : 'Create product'}
                            </Button>
                        </CardFooter>
                    </Card>
                </div>
            </form>
        </>
    );
}
