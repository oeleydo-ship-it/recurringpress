import { clsx } from 'clsx';
import { twMerge } from 'tailwind-merge';

export function cn(...inputs) {
    return twMerge(clsx(inputs));
}

const moneyFormatters = new Map();

/**
 * Format an amount in the given ISO currency (falls back to a plain symbol prefix for
 * codes Intl doesn't know).
 */
export function formatMoney(amount, currency = 'USD', options = {}) {
    const value = Number(amount ?? 0);
    const key = `${currency}|${options.compact ? 'c' : ''}`;

    try {
        if (!moneyFormatters.has(key)) {
            moneyFormatters.set(
                key,
                new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency,
                    ...(options.compact ? { notation: 'compact', maximumFractionDigits: 1 } : { minimumFractionDigits: 2 }),
                }),
            );
        }
        return moneyFormatters.get(key).format(value);
    } catch {
        return `${currency} ${value.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }
}

export function formatNumber(value, digits = 0) {
    return Number(value ?? 0).toLocaleString('en-US', { minimumFractionDigits: digits, maximumFractionDigits: digits });
}

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const MONTHS_LONG = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];

function parseDate(value) {
    if (!value) return null;
    if (value instanceof Date) return value;
    // "YYYY-MM-DD" is a calendar date: build it in local time so it never shifts a day.
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(value);
    if (m) return new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
    const d = new Date(value);
    return Number.isNaN(d.getTime()) ? null : d;
}

/**
 * Format a date using a PHP-style format string (the company "date_format" setting),
 * supporting the tokens that setting offers: d j D m n M F Y y.
 */
export function formatDate(value, format = 'M d, Y') {
    const d = parseDate(value);
    if (!d) return '—';
    const pad = (n) => String(n).padStart(2, '0');
    const map = {
        d: pad(d.getDate()),
        j: String(d.getDate()),
        D: d.toLocaleDateString('en-US', { weekday: 'short' }),
        m: pad(d.getMonth() + 1),
        n: String(d.getMonth() + 1),
        M: MONTHS[d.getMonth()],
        F: MONTHS_LONG[d.getMonth()],
        Y: String(d.getFullYear()),
        y: String(d.getFullYear()).slice(-2),
    };
    return format.replace(/[djDmnMFYy]/g, (t) => map[t]);
}

export function formatDateTime(value) {
    const d = parseDate(value);
    if (!d) return '—';
    return d.toLocaleString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' });
}

export function relativeTime(value) {
    const d = parseDate(value);
    if (!d) return '';
    const diff = (Date.now() - d.getTime()) / 1000;
    const units = [
        ['year', 31536000],
        ['month', 2592000],
        ['week', 604800],
        ['day', 86400],
        ['hour', 3600],
        ['minute', 60],
    ];
    const rtf = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });
    for (const [unit, secs] of units) {
        if (Math.abs(diff) >= secs) return rtf.format(-Math.round(diff / secs), unit);
    }
    return 'just now';
}

export function initials(name = '') {
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((p) => p[0]?.toUpperCase())
        .join('');
}

/** Today's date as YYYY-MM-DD in local time (for date inputs). */
export function today(offsetDays = 0) {
    const d = new Date();
    d.setDate(d.getDate() + offsetDays);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

export function titleCase(value = '') {
    return String(value)
        .replace(/[_-]+/g, ' ')
        .replace(/\b\w/g, (c) => c.toUpperCase());
}

/** Black or white text, whichever reads better on the given hex background. */
export function contrastText(hex) {
    const m = /^#?([0-9a-f]{6})$/i.exec(String(hex ?? '').trim());
    if (!m) return '#ffffff';
    const n = parseInt(m[1], 16);
    const lin = (c) => {
        const s = c / 255;
        return s <= 0.03928 ? s / 12.92 : ((s + 0.055) / 1.055) ** 2.4;
    };
    const L = 0.2126 * lin((n >> 16) & 255) + 0.7152 * lin((n >> 8) & 255) + 0.0722 * lin(n & 255);
    return L > 0.45 ? '#0f172a' : '#ffffff';
}

/** CSS custom properties for a company theme (see resources/css/app.css). */
export function themeStyle(theme = {}) {
    const brand = /^#[0-9a-f]{6}$/i.test(theme.primary ?? '') ? theme.primary : '#4f46e5';
    return {
        '--brand': brand,
        '--brand-contrast': contrastText(brand),
        '--sidebar': theme.sidebar || '#ffffff',
        '--sidebar-text': theme.sidebar_text || '#1f2937',
        '--app-font': `'${theme.font || 'Inter'}'`,
    };
}
