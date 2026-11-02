export const PERIODS = ['monthly', 'yearly', 'lifetime'];

export const PERIOD_LABELS = { monthly: 'Monthly', yearly: 'Yearly', lifetime: 'Lifetime' };

const SUFFIXES = { monthly: '/ month', yearly: '/ year', lifetime: 'one-time' };

export const periodSuffix = (period) => SUFFIXES[period] ?? SUFFIXES.monthly;

export const isFreePlan = (plan) => Object.keys(plan.prices ?? {}).length === 0;

/** The periods at least one of these plans can be bought on, in display order. */
export const offeredPeriods = (plans) => PERIODS.filter((period) => plans.some((plan) => plan.prices?.[period] != null));
