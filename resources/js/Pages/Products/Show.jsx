import { router, usePage } from '@inertiajs/react';
import { Banknote, FileText, Hash, Pencil, Repeat, Trash2 } from 'lucide-react';
import { Badge, Button, Card, CardHeader, DescriptionItem, Dropdown, PageHeader, StatCard, useConfirm } from '@/Components/ui';
import { route } from '@/lib/route';
import { formatMoney, formatNumber } from '@/lib/utils';
import { typeMeta } from './Index';

export default function ProductShow({ product, units, billingCycles, usage }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const confirm = useConfirm();
    const meta = typeMeta[product.type] ?? typeMeta.service;

    const destroy = async () => {
        if (await confirm({ title: `Delete ${product.name}?`, confirmText: 'Delete product' })) router.delete(route('products.destroy', product.id));
    };

    return (
        <>
            <PageHeader
                title={product.name}
                back={route('products.index')}
                meta={
                    <>
                        <Badge tone={meta.tone}>{meta.label}</Badge>
                        {!product.is_active ? <Badge>Inactive</Badge> : null}
                    </>
                }
                actions={
                    <>
                        <Button href={route('products.edit', product.id)} variant="secondary" icon={Pencil}>
                            Edit
                        </Button>
                        <Dropdown items={[{ label: 'Delete', icon: Trash2, danger: true, onClick: destroy }]} />
                    </>
                }
            />

            <div className="grid gap-4 sm:grid-cols-3">
                <StatCard label="Price" value={formatMoney(product.price, currency)} icon={Banknote} hint={`per ${units[product.unit]?.toLowerCase() ?? product.unit}`} />
                <StatCard label="Invoiced lines" value={usage.invoice_lines} icon={FileText} tone="blue" hint={`${formatNumber(usage.quantity_sold, 2)} units billed`} />
                <StatCard label="Revenue billed" value={formatMoney(usage.revenue, currency)} icon={Hash} tone="green" />
            </div>

            <Card className="mt-6">
                <CardHeader title="Details" />
                <dl className="grid gap-6 p-5 sm:grid-cols-2 lg:grid-cols-3">
                    <DescriptionItem label="Description" className="sm:col-span-2 lg:col-span-3">
                        {product.description ? <span className="whitespace-pre-line">{product.description}</span> : null}
                    </DescriptionItem>
                    <DescriptionItem label="SKU">{product.sku ? <span className="font-mono">{product.sku}</span> : null}</DescriptionItem>
                    <DescriptionItem label="Tax rate">{`${product.tax_rate || 0}%`}</DescriptionItem>
                    <DescriptionItem label="Unit">{units[product.unit] ?? product.unit}</DescriptionItem>
                    {product.track_stock ? (
                        <>
                            <DescriptionItem label="In stock">{product.stock_quantity ?? 0}</DescriptionItem>
                            <DescriptionItem label="Low-stock alert">{product.low_stock_threshold ?? '—'}</DescriptionItem>
                        </>
                    ) : null}
                    {product.is_recurring ? (
                        <DescriptionItem label="Recurring">
                            <span className="inline-flex items-center gap-1.5">
                                <Repeat className="size-4 text-brand-600" />
                                {billingCycles[product.billing_cycle] ?? product.billing_cycle}
                                {product.billing_cycle_count ? ` × ${product.billing_cycle_count}` : ''}
                                {product.has_trial ? ` · ${product.trial_days}-day trial` : ''}
                            </span>
                        </DescriptionItem>
                    ) : null}
                </dl>
            </Card>
        </>
    );
}
