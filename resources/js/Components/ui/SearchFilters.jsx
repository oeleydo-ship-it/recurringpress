import { router } from '@inertiajs/react';
import { Search, X } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { cn } from '@/lib/utils';

/**
 * Keeps list filters in the query string. Changing a filter re-requests the page with
 * preserveState so inputs keep focus; search is debounced.
 *
 *   const [filters, setFilter] = useFilters(route('invoices.index'), initialFilters)
 */
export function useFilters(url, initial = {}) {
    const [filters, setFilters] = useState(() => ({ ...initial }));
    const first = useRef(true);
    const timer = useRef(null);

    useEffect(() => {
        if (first.current) {
            first.current = false;
            return;
        }
        clearTimeout(timer.current);
        timer.current = setTimeout(() => {
            const query = Object.fromEntries(Object.entries(filters).filter(([, v]) => v !== '' && v !== null && v !== undefined));
            router.get(url, query, { preserveState: true, preserveScroll: true, replace: true });
        }, 300);
        return () => clearTimeout(timer.current);
    }, [filters, url]);

    const set = (key, value) => setFilters((f) => ({ ...f, [key]: value }));
    const reset = () => setFilters({});
    return [filters, set, reset];
}

export function SearchInput({ value, onChange, placeholder = 'Search…', className }) {
    return (
        <div className={cn('relative', className)}>
            <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-slate-400" />
            <input
                type="search"
                value={value ?? ''}
                onChange={(e) => onChange(e.target.value)}
                placeholder={placeholder}
                className="field pr-8 pl-9 [&::-webkit-search-cancel-button]:hidden"
            />
            {value ? (
                <button type="button" onClick={() => onChange('')} className="absolute top-1/2 right-2 -translate-y-1/2 rounded p-0.5 text-slate-400 hover:text-slate-600" aria-label="Clear search">
                    <X className="size-4" />
                </button>
            ) : null}
        </div>
    );
}

/** Toolbar row that sits at the top of a list card. */
export function FilterBar({ children, className }) {
    return <div className={cn('flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center', className)}>{children}</div>;
}
