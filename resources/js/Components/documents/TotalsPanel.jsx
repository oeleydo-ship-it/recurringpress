import { FieldError, Input, Select } from '@/Components/ui';
import { formatMoney } from '@/lib/utils';

/** Subtotal / tax / discount / total summary with an editable discount. */
export default function TotalsPanel({ totals, currency, discountAmount, discountType, onDiscountAmount, onDiscountType, error, symbol }) {
    return (
        <dl className="space-y-3 text-sm">
            <Row label="Subtotal" value={formatMoney(totals.subtotal, currency)} />
            <Row label="Tax" value={formatMoney(totals.tax, currency)} />
            <div className="flex items-center justify-between gap-3">
                <dt className="text-slate-500">Discount</dt>
                <dd className="flex items-center gap-2">
                    <Input
                        type="number"
                        min="0"
                        step="0.01"
                        value={discountAmount}
                        onChange={(e) => onDiscountAmount(e.target.value)}
                        className="w-24 text-right"
                        aria-label="Discount amount"
                        invalid={Boolean(error)}
                    />
                    <Select value={discountType} onChange={(e) => onDiscountType(e.target.value)} className="w-20" aria-label="Discount type" options={{ fixed: symbol || '$', percentage: '%' }} />
                </dd>
            </div>
            <FieldError className="text-right">{error}</FieldError>
            {totals.discount > 0 ? <Row label="" value={`− ${formatMoney(totals.discount, currency)}`} muted /> : null}
            <div className="flex items-center justify-between border-t border-slate-200 pt-3">
                <dt className="text-base font-semibold text-slate-900">Total</dt>
                <dd className="text-xl font-semibold tracking-tight text-slate-900 tabular-nums">{formatMoney(totals.total, currency)}</dd>
            </div>
        </dl>
    );
}

function Row({ label, value, muted }) {
    return (
        <div className="flex items-center justify-between">
            <dt className="text-slate-500">{label}</dt>
            <dd className={muted ? 'text-emerald-600 tabular-nums' : 'font-medium text-slate-900 tabular-nums'}>{value}</dd>
        </div>
    );
}
