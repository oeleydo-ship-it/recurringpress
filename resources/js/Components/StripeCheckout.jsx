import { Elements, PaymentElement, useElements, useStripe } from '@stripe/react-stripe-js';
import { loadStripe } from '@stripe/stripe-js';
import { Lock, ShieldCheck } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';
import { Button } from '@/Components/ui';
import { postJson } from '@/lib/http';
import { formatMoney } from '@/lib/utils';

const stripeCache = new Map();
const getStripe = (key) => {
    if (!stripeCache.has(key)) stripeCache.set(key, loadStripe(key));
    return stripeCache.get(key);
};

/**
 * Card checkout with Stripe's Payment Element. The server creates the PaymentIntent
 * (POST `intentUrl` → { clientSecret, publishableKey }); on confirmation Stripe redirects to
 * `returnUrl?payment_intent=…`, where the server verifies and records the payment.
 */
export default function StripeCheckout({ publishableKey, intentUrl, returnUrl, amount, currency, brand = '#4f46e5', buttonLabel }) {
    const [state, setState] = useState({ loading: true, error: null, clientSecret: null, key: publishableKey });

    useEffect(() => {
        let cancelled = false;
        postJson(intentUrl, {})
            .then((res) => !cancelled && setState({ loading: false, error: null, clientSecret: res.clientSecret, key: res.publishableKey || res.publicKey || publishableKey }))
            .catch((err) => !cancelled && setState((s) => ({ ...s, loading: false, error: err.message })));
        return () => {
            cancelled = true;
        };
    }, [intentUrl, publishableKey]);

    const stripePromise = useMemo(() => (state.key ? getStripe(state.key) : null), [state.key]);

    if (state.loading) {
        return (
            <div className="space-y-3">
                <div className="h-11 animate-pulse rounded-lg bg-slate-100" />
                <div className="h-11 animate-pulse rounded-lg bg-slate-100" />
                <div className="h-11 animate-pulse rounded-lg bg-slate-100" />
            </div>
        );
    }

    if (state.error || !state.clientSecret || !stripePromise) {
        return <div className="rounded-lg bg-red-50 p-4 text-sm text-red-700 ring-1 ring-red-600/15">{state.error || 'Card payments are unavailable right now.'}</div>;
    }

    return (
        <Elements
            stripe={stripePromise}
            options={{
                clientSecret: state.clientSecret,
                appearance: {
                    theme: 'stripe',
                    variables: { colorPrimary: brand, borderRadius: '8px', fontFamily: 'Inter, system-ui, sans-serif', colorText: '#0f172a' },
                },
            }}
        >
            <CheckoutForm returnUrl={returnUrl} amount={amount} currency={currency} buttonLabel={buttonLabel} />
        </Elements>
    );
}

function CheckoutForm({ returnUrl, amount, currency, buttonLabel }) {
    const stripe = useStripe();
    const elements = useElements();
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState(null);

    const submit = async (e) => {
        e.preventDefault();
        if (!stripe || !elements) return;
        setSubmitting(true);
        setError(null);
        const { error: err } = await stripe.confirmPayment({ elements, confirmParams: { return_url: returnUrl } });
        // Only reached on an immediate error (otherwise Stripe redirects to returnUrl).
        if (err) setError(err.message ?? 'Payment failed. Please try again.');
        setSubmitting(false);
    };

    return (
        <form onSubmit={submit} className="space-y-5">
            <PaymentElement options={{ layout: 'tabs' }} />
            {error ? <p className="rounded-lg bg-red-50 p-3 text-sm text-red-700">{error}</p> : null}
            <Button type="submit" size="lg" icon={Lock} loading={submitting} disabled={!stripe} className="w-full">
                {buttonLabel ?? `Pay ${formatMoney(amount, currency)}`}
            </Button>
            <p className="flex items-center justify-center gap-1.5 text-xs text-slate-500">
                <ShieldCheck className="size-3.5" /> Secured by Stripe. Card details never touch our servers.
            </p>
        </form>
    );
}
