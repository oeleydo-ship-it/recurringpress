import { ArrowDown, ArrowUp, Box, GripVertical, Plus, Text, Trash2 } from 'lucide-react';
import { Button, Combobox, FieldError, Input, Select } from '@/Components/ui';
import { cn, formatMoney } from '@/lib/utils';
import { blankItem, lineTotal } from './totals';

/**
 * Editable table of document line items.
 * `items` carry a client-side `_key`; errors are Laravel's "items.N.field" keys.
 */
export default function LineItemsEditor({ items, onChange, products = [], units = {}, currency, symbol, errors = {}, defaults = {} }) {
    const update = (index, patch) => onChange(items.map((it, i) => (i === index ? { ...it, ...patch } : it)));
    const remove = (index) => onChange(items.length > 1 ? items.filter((_, i) => i !== index) : [blankItem(defaults)]);
    const move = (index, dir) => {
        const to = index + dir;
        if (to < 0 || to >= items.length) return;
        const next = [...items];
        [next[index], next[to]] = [next[to], next[index]];
        onChange(next);
    };
    const add = () => onChange([...items, blankItem(defaults)]);

    const pickProduct = (index, productId) => {
        const p = products.find((x) => String(x.id) === String(productId));
        if (!p) return;
        update(index, {
            product_id: p.id,
            name: p.name,
            description: p.description ?? '',
            price: p.price,
            unit: p.unit ?? 'unit',
            tax_rate: p.tax_rate ?? defaults.tax_rate ?? 0,
            _showDescription: Boolean(p.description),
        });
    };

    const productOptions = products.map((p) => ({ id: p.id, label: p.name, sublabel: `${formatMoney(p.price, currency)} / ${units[p.unit]?.toLowerCase() ?? p.unit}` }));
    const err = (i, f) => errors[`items.${i}.${f}`];

    return (
        <div className="@container">
            {/* Column headings (wide containers) */}
            <div className="hidden grid-cols-[minmax(0,1fr)_76px_100px_124px_88px_120px_72px] gap-3 border-b border-slate-100 px-1 pb-2 text-xs font-semibold tracking-wide text-slate-500 uppercase @4xl:grid">
                <span>Item</span>
                <span>Qty</span>
                <span>Unit</span>
                <span>Price</span>
                <span>Tax</span>
                <span className="text-right">Amount</span>
                <span />
            </div>

            <ul className="divide-y divide-slate-100">
                {items.map((it, i) => (
                    <li key={it._key} className="group py-4">
                        <div className="grid grid-cols-2 gap-3 @4xl:grid-cols-[minmax(0,1fr)_76px_100px_124px_88px_120px_72px] @4xl:items-start">
                            <div className="col-span-2 space-y-2 @4xl:col-span-1">
                                <div className="flex gap-2">
                                    <GripVertical className="mt-2.5 hidden size-4 shrink-0 text-slate-300 @4xl:block" />
                                    <div className="min-w-0 flex-1">
                                        <Input
                                            value={it.name}
                                            onChange={(e) => update(i, { name: e.target.value })}
                                            placeholder="Item name"
                                            invalid={Boolean(err(i, 'name'))}
                                            aria-label="Item name"
                                        />
                                        <FieldError>{err(i, 'name')}</FieldError>
                                    </div>
                                </div>
                                {it._showDescription || it.description ? (
                                    <textarea
                                        value={it.description ?? ''}
                                        onChange={(e) => update(i, { description: e.target.value })}
                                        rows={2}
                                        placeholder="Description (optional)"
                                        className="field text-sm @4xl:ml-6 @4xl:w-[calc(100%-1.5rem)]"
                                    />
                                ) : null}
                                <div className="flex flex-wrap items-center gap-3 @4xl:pl-6">
                                    {products.length ? (
                                        <Combobox value={it.product_id ?? ''} onChange={(id) => pickProduct(i, id)} options={productOptions} placeholder="Pick a saved product…" className="w-56 [&_.field]:h-8 [&_.field]:py-1 [&_.field]:text-xs" />
                                    ) : null}
                                    {!it._showDescription && !it.description ? (
                                        <button type="button" onClick={() => update(i, { _showDescription: true })} className="inline-flex items-center gap-1 text-xs font-medium text-slate-500 hover:text-slate-800">
                                            <Text className="size-3.5" /> Add description
                                        </button>
                                    ) : null}
                                </div>
                            </div>

                            <LabeledCell label="Qty" error={err(i, 'quantity')}>
                                <Input type="number" min="0" step="any" value={it.quantity} onChange={(e) => update(i, { quantity: e.target.value })} invalid={Boolean(err(i, 'quantity'))} aria-label="Quantity" />
                            </LabeledCell>
                            <LabeledCell label="Unit">
                                <Select value={it.unit ?? 'unit'} onChange={(e) => update(i, { unit: e.target.value })} options={units} aria-label="Unit" />
                            </LabeledCell>
                            <LabeledCell label="Price" error={err(i, 'price')}>
                                <Input type="number" min="0" step="0.01" prefix={symbol} value={it.price} onChange={(e) => update(i, { price: e.target.value })} invalid={Boolean(err(i, 'price'))} aria-label="Unit price" />
                            </LabeledCell>
                            <LabeledCell label="Tax" error={err(i, 'tax_rate')}>
                                <Input type="number" min="0" max="100" step="0.01" suffix="%" value={it.tax_rate} onChange={(e) => update(i, { tax_rate: e.target.value })} aria-label="Tax rate" />
                            </LabeledCell>
                            <div className="flex items-center justify-between @4xl:block @4xl:pt-2 @4xl:text-right">
                                <span className="text-xs text-slate-500 @4xl:hidden">Amount</span>
                                <span className="text-sm font-semibold text-slate-900 tabular-nums">{formatMoney(lineTotal(it), currency)}</span>
                            </div>
                            <div className="col-span-2 flex items-center justify-end gap-0.5 @4xl:col-span-1 @4xl:pt-0.5">
                                <IconBtn onClick={() => move(i, -1)} disabled={i === 0} label="Move up" icon={ArrowUp} />
                                <IconBtn onClick={() => move(i, 1)} disabled={i === items.length - 1} label="Move down" icon={ArrowDown} />
                                <IconBtn onClick={() => remove(i)} label="Remove line" icon={Trash2} danger />
                            </div>
                        </div>
                    </li>
                ))}
            </ul>

            <FieldError>{errors.items}</FieldError>
            <div className="mt-2 flex flex-wrap gap-2">
                <Button variant="soft" size="sm" icon={Plus} onClick={add}>
                    Add line
                </Button>
                {!products.length ? (
                    <span className="inline-flex items-center gap-1.5 text-xs text-slate-500">
                        <Box className="size-3.5" /> Save products to add them in one click.
                    </span>
                ) : null}
            </div>
        </div>
    );
}

function LabeledCell({ label, error, children }) {
    return (
        <div>
            <span className="mb-1 block text-xs font-medium text-slate-500 @4xl:hidden">{label}</span>
            {children}
            <FieldError className="text-xs">{error}</FieldError>
        </div>
    );
}

function IconBtn({ icon: Icon, label, onClick, disabled, danger }) {
    return (
        <button
            type="button"
            onClick={onClick}
            disabled={disabled}
            aria-label={label}
            title={label}
            className={cn('rounded-lg p-1.5 text-slate-400 transition disabled:opacity-30', danger ? 'hover:bg-red-50 hover:text-red-600' : 'hover:bg-slate-100 hover:text-slate-700')}
        >
            <Icon className="size-4" />
        </button>
    );
}
