/**
 * Mirrors Invoice::calculateTotals() on the server: tax per line on the pre-discount amount,
 * discount (fixed or %) applied to the subtotal, total never below zero.
 */
export function computeTotals(items, discountAmount = 0, discountType = 'fixed') {
    let subtotal = 0;
    let tax = 0;
    for (const it of items) {
        const line = num(it.quantity) * num(it.price);
        subtotal += line;
        tax += line * (num(it.tax_rate) / 100);
    }
    const d = num(discountAmount);
    const discount = d > 0 ? (discountType === 'percentage' ? (subtotal * d) / 100 : d) : 0;
    return {
        subtotal: round(subtotal),
        tax: round(tax),
        discount: round(discount),
        total: round(Math.max(0, subtotal + tax - discount)),
    };
}

export const lineTotal = (it) => round(num(it.quantity) * num(it.price));

export const num = (v) => {
    const n = parseFloat(v);
    return Number.isFinite(n) ? n : 0;
};

const round = (n) => Math.round(n * 100) / 100;

let seq = 0;
export const blankItem = (defaults = {}) => ({
    _key: `new-${Date.now()}-${seq++}`,
    product_id: null,
    name: '',
    description: '',
    quantity: 1,
    unit: 'unit',
    price: '',
    tax_rate: defaults.tax_rate ?? 0,
});

export const withKeys = (items) => items.map((it, i) => ({ ...it, _key: it._key ?? `row-${i}-${seq++}` }));

/** Strip client-only fields before submitting. */
export const cleanItems = (items) => items.map(({ _key, _showDescription, ...rest }) => rest);
