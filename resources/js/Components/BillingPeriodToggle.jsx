import { PERIOD_LABELS } from '@/lib/billing';
import { cn } from '@/lib/utils';

/** Monthly / Yearly / Lifetime switch. Pages show it only when there is more than one period to pick. */
export default function BillingPeriodToggle({ periods, value, onChange, activeClassName = 'text-brand-600' }) {
    return (
        <div className="inline-flex rounded-full bg-slate-100 p-1 text-sm font-medium" role="group" aria-label="Billing period">
            {periods.map((period) => (
                <button
                    key={period}
                    type="button"
                    aria-pressed={value === period}
                    onClick={() => onChange(period)}
                    className={cn('rounded-full px-5 py-1.5 transition', value === period ? cn('bg-white shadow-sm', activeClassName) : 'text-slate-500 hover:text-slate-800')}
                >
                    {PERIOD_LABELS[period]}
                </button>
            ))}
        </div>
    );
}
