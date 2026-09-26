import { Combobox as HCombobox, ComboboxButton, ComboboxInput, ComboboxOption, ComboboxOptions } from '@headlessui/react';
import { Check, ChevronsUpDown, Plus } from 'lucide-react';
import { useMemo, useState } from 'react';
import { cn } from '@/lib/utils';

/**
 * Searchable select. `options`: [{ id, label, sublabel? }]. `value` is the selected id.
 * Optional `onCreate(query)` adds a "Create …" row at the bottom.
 */
export default function Combobox({ value, onChange, options, placeholder = 'Search…', invalid, onCreate, createLabel = 'Create', className, emptyText = 'No matches', renderOption }) {
    const [query, setQuery] = useState('');
    const selected = options.find((o) => String(o.id) === String(value)) ?? null;

    const filtered = useMemo(() => {
        const q = query.trim().toLowerCase();
        if (!q) return options.slice(0, 100);
        return options.filter((o) => `${o.label} ${o.sublabel ?? ''}`.toLowerCase().includes(q)).slice(0, 100);
    }, [options, query]);

    return (
        <HCombobox
            value={selected}
            by={(a, b) => String(a?.id) === String(b?.id)}
            onChange={(o) => {
                if (o?.__create) {
                    onCreate?.(query);
                    return;
                }
                onChange(o?.id ?? '');
            }}
            onClose={() => setQuery('')}
        >
            <div className={cn('relative', className)}>
                <ComboboxInput
                    className={cn('field pr-9', invalid && 'field-invalid')}
                    displayValue={(o) => o?.label ?? ''}
                    onChange={(e) => setQuery(e.target.value)}
                    placeholder={placeholder}
                    autoComplete="off"
                />
                <ComboboxButton className="absolute inset-y-0 right-0 flex items-center px-2.5 text-slate-400">
                    <ChevronsUpDown className="size-4" />
                </ComboboxButton>
            </div>
            <ComboboxOptions
                anchor="bottom start"
                transition
                className="z-50 mt-1 max-h-72 w-[var(--input-width)] min-w-64 overflow-auto rounded-xl bg-white p-1 shadow-pop ring-1 ring-slate-900/5 transition duration-100 [--anchor-gap:4px] empty:invisible data-closed:opacity-0"
            >
                {filtered.map((o) => (
                    <ComboboxOption key={o.id} value={o} className="group flex cursor-pointer items-center gap-2 rounded-lg px-3 py-2 text-sm text-slate-700 select-none data-focus:bg-slate-100 data-focus:text-slate-900">
                        <Check className="invisible size-4 shrink-0 text-brand-600 group-data-selected:visible" />
                        {renderOption ? (
                            renderOption(o)
                        ) : (
                            <span className="min-w-0">
                                <span className="block truncate">{o.label}</span>
                                {o.sublabel ? <span className="block truncate text-xs text-slate-500">{o.sublabel}</span> : null}
                            </span>
                        )}
                    </ComboboxOption>
                ))}
                {!filtered.length && !onCreate ? <div className="px-3 py-2 text-sm text-slate-500">{emptyText}</div> : null}
                {onCreate ? (
                    <ComboboxOption
                        value={{ __create: true, id: '__create' }}
                        className="flex cursor-pointer items-center gap-2 rounded-lg border-t border-slate-100 px-3 py-2 text-sm font-medium text-brand-700 select-none data-focus:bg-brand-50"
                    >
                        <Plus className="size-4" />
                        {createLabel}
                        {query ? ` “${query}”` : '…'}
                    </ComboboxOption>
                ) : null}
            </ComboboxOptions>
        </HCombobox>
    );
}
