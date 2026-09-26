import { router, usePage } from '@inertiajs/react';
import { Box, Download, Package, PackagePlus, Pencil, Repeat, Trash2, Wrench } from 'lucide-react';
import {
    Badge,
    Button,
    Card,
    Dropdown,
    EmptyState,
    FilterBar,
    PageHeader,
    Pagination,
    SearchInput,
    Select,
    Table,
    TBody,
    TD,
    TH,
    THead,
    TR,
    useConfirm,
    useFilters,
} from '@/Components/ui';
import { route } from '@/lib/route';
import { formatMoney } from '@/lib/utils';

export const typeMeta = {
    service: { label: 'Service', icon: Wrench, tone: 'blue' },
    physical: { label: 'Physical', icon: Package, tone: 'orange' },
    digital: { label: 'Digital', icon: Download, tone: 'purple' },
};

export default function ProductsIndex({ products, filters: initial, units }) {
    const { auth } = usePage().props;
    const currency = auth.company?.currency;
    const confirm = useConfirm();
    const [filters, setFilter] = useFilters(route('products.index'), initial);
    const filtered = Boolean(filters.search || filters.status || filters.type);

    const destroy = async (p) => {
        if (await confirm({ title: `Delete ${p.name}?`, message: 'Products used on invoices cannot be deleted — deactivate them instead.', confirmText: 'Delete product' })) {
            router.delete(route('products.destroy', p.id), { preserveScroll: true });
        }
    };

    return (
        <>
            <PageHeader
                title="Products & services"
                description="Reusable line items for invoices and quotations."
                actions={
                    <Button href={route('products.create')} icon={PackagePlus}>
                        Add product
                    </Button>
                }
            />

            <Card>
                <FilterBar>
                    <SearchInput value={filters.search} onChange={(v) => setFilter('search', v)} placeholder="Search name, SKU…" className="sm:max-w-xs sm:flex-1" />
                    <Select value={filters.type ?? ''} onChange={(e) => setFilter('type', e.target.value)} className="sm:w-40" options={{ '': 'All types', service: 'Services', physical: 'Physical', digital: 'Digital' }} />
                    <Select value={filters.status ?? ''} onChange={(e) => setFilter('status', e.target.value)} className="sm:w-36" options={{ '': 'Any status', active: 'Active', inactive: 'Inactive' }} />
                </FilterBar>

                {products.data.length ? (
                    <>
                        <Table>
                            <THead>
                                <TH>Product</TH>
                                <TH className="hidden md:table-cell">Type</TH>
                                <TH className="hidden lg:table-cell">SKU</TH>
                                <TH className="hidden sm:table-cell" align="right">
                                    Tax
                                </TH>
                                <TH align="right">Price</TH>
                                <TH className="w-10" />
                            </THead>
                            <TBody>
                                {products.data.map((p) => {
                                    const meta = typeMeta[p.type] ?? typeMeta.service;
                                    const Icon = meta.icon;
                                    const low = p.track_stock && p.low_stock_threshold !== null && p.stock_quantity !== null && p.stock_quantity <= p.low_stock_threshold;
                                    return (
                                        <TR key={p.id} href={route('products.show', p.id)}>
                                            <TD>
                                                <div className="flex items-center gap-3">
                                                    <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-slate-500">
                                                        <Icon className="size-4" />
                                                    </span>
                                                    <div className="min-w-0">
                                                        <div className="flex items-center gap-2">
                                                            <span className="truncate font-medium text-slate-900">{p.name}</span>
                                                            {!p.is_active ? <Badge>Inactive</Badge> : null}
                                                            {p.is_recurring ? (
                                                                <Badge tone="brand">
                                                                    <Repeat className="size-3" /> Recurring
                                                                </Badge>
                                                            ) : null}
                                                        </div>
                                                        <span className="block max-w-xs truncate text-xs text-slate-500">
                                                            {p.track_stock ? <span className={low ? 'font-medium text-red-600' : ''}>{p.stock_quantity ?? 0} in stock · </span> : null}
                                                            {p.description || `Per ${units[p.unit]?.toLowerCase() ?? p.unit}`}
                                                        </span>
                                                    </div>
                                                </div>
                                            </TD>
                                            <TD className="hidden md:table-cell">
                                                <Badge tone={meta.tone}>{meta.label}</Badge>
                                            </TD>
                                            <TD className="hidden font-mono text-xs text-slate-500 lg:table-cell">{p.sku || '—'}</TD>
                                            <TD className="hidden sm:table-cell" align="right">
                                                {p.tax_rate ? `${p.tax_rate}%` : '—'}
                                            </TD>
                                            <TD align="right">
                                                <span className="font-medium text-slate-900">{formatMoney(p.price, currency)}</span>
                                                <span className="block text-xs text-slate-500">/ {units[p.unit]?.toLowerCase() ?? p.unit}</span>
                                            </TD>
                                            <TD>
                                                <Dropdown
                                                    items={[
                                                        { label: 'Edit', icon: Pencil, href: route('products.edit', p.id) },
                                                        { separator: true },
                                                        { label: 'Delete', icon: Trash2, danger: true, onClick: () => destroy(p) },
                                                    ]}
                                                />
                                            </TD>
                                        </TR>
                                    );
                                })}
                            </TBody>
                        </Table>
                        <Pagination paginator={products} />
                    </>
                ) : (
                    <EmptyState
                        icon={Box}
                        title={filtered ? 'No products match your filters' : 'No products yet'}
                        description={filtered ? 'Try a different search.' : 'Save the services and goods you sell to add them to invoices in one click.'}
                        action={
                            !filtered ? (
                                <Button href={route('products.create')} icon={PackagePlus}>
                                    Add product
                                </Button>
                            ) : null
                        }
                    />
                )}
            </Card>
        </>
    );
}
