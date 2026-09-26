import { route as ziggyRoute } from 'ziggy-js';

/**
 * Ziggy route() helper. The route list arrives once as the "ziggy" shared prop and is
 * stored here by app.jsx (both in the browser and during SSR).
 */
let config = null;

export function setZiggyConfig(value) {
    if (value) config = value;
}

export function route(name, params, absolute = false) {
    if (name === undefined) {
        return ziggyRoute(undefined, undefined, undefined, config ?? undefined);
    }
    return ziggyRoute(name, params, absolute, config ?? undefined);
}

/** True when the current URL matches a route name pattern (e.g. "invoices.*"). */
export function isCurrent(pattern, url) {
    try {
        if (typeof window === 'undefined' && config) {
            const cfg = url ? { ...config, location: new URL(url, config.url).href } : config;
            return ziggyRoute(undefined, undefined, undefined, cfg).current(pattern);
        }
        return ziggyRoute(undefined, undefined, undefined, config ?? undefined).current(pattern);
    } catch {
        return false;
    }
}
