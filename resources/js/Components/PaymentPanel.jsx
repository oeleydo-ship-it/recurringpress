import { Banknote, Check, Copy, CreditCard, Landmark, Wallet } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import StripeCheckout from '@/Components/StripeCheckout';
import { Button } from '@/Components/ui';
import { cn, formatMoney } from '@/lib/utils';

/** Payment choices shown to a client on a public invoice / accepted quotation. */
export default function PaymentPanel({ payment, amount, currency, brand, intentUrl, returnUrl }) {
    const methods = [
        payment.stripe && { key: 'card', label: 'Card', icon: CreditCard },
        payment.bank && Object.keys(payment.bank).length && { key: 'bank', label: 'Bank transfer', icon: Landmark },
        payment.paypal_email && { key: 'paypal', label: 'PayPal', icon: Wallet },
        payment.cash && { key: 'cash', label: 'Cash', icon: Banknote },
    ].filter(Boolean);
    const [method, setMethod] = useState(methods[0]?.key ?? null);
    const [cardOpen, setCardOpen] = useState(false);

    if (!methods.length) {
        return <p className="text-sm text-slate-500">Please contact us to arrange payment.</p>;
    }

    return (
        <div className="space-y-5">
            {methods.length > 1 ? (
                <div className="grid grid-cols-2 gap-2">
                    {methods.map((m) => {
                        const Icon = m.icon;
                        return (
                            <button
                                key={m.key}
                                type="button"
                                onClick={() => setMethod(m.key)}
                                className={cn(
                                    'flex items-center gap-2 rounded-xl px-3 py-2.5 text-sm font-medium ring-1 transition',
                                    method === m.key ? 'bg-brand-50 text-brand-700 ring-2 ring-brand-500' : 'text-slate-600 ring-slate-200 hover:ring-slate-300',
                                )}
                            >
                                <Icon className="size-4" /> {m.label}
                            </button>
                        );
                    })}
                </div>
            ) : null}

            {method === 'card' ? (
                cardOpen ? (
                    <StripeCheckout publishableKey={payment.stripe_key} intentUrl={intentUrl} returnUrl={returnUrl} amount={amount} currency={currency} brand={brand} />
                ) : (
                    <Button size="lg" icon={CreditCard} className="w-full" onClick={() => setCardOpen(true)}>
                        Pay {formatMoney(amount, currency)} by card
                    </Button>
                )
            ) : null}

            {method === 'bank' ? (
                <dl className="divide-y divide-slate-100 rounded-xl bg-slate-50 text-sm ring-1 ring-slate-200">
                    {[
                        ['Bank', payment.bank.bank_name],
                        ['Account name', payment.bank.account_name],
                        ['Account / IBAN', payment.bank.account_number],
                        ['Routing / SWIFT', payment.bank.routing_number],
                        ['Amount', formatMoney(amount, currency)],
                    ]
                        .filter(([, v]) => v)
                        .map(([k, v]) => (
                            <CopyRow key={k} label={k} value={v} />
                        ))}
                </dl>
            ) : null}

            {method === 'paypal' ? (
                <dl className="rounded-xl bg-slate-50 text-sm ring-1 ring-slate-200">
                    <CopyRow label="Send to" value={payment.paypal_email} />
                </dl>
            ) : null}

            {method === 'cash' ? <p className="rounded-xl bg-slate-50 p-4 text-sm text-slate-600 ring-1 ring-slate-200">Cash payments are accepted — please contact us to arrange.</p> : null}

            {payment.instructions ? <p className="text-sm whitespace-pre-line text-slate-600">{payment.instructions}</p> : null}
        </div>
    );
}

function CopyRow({ label, value }) {
    const [copied, setCopied] = useState(false);
    return (
        <div className="flex items-center justify-between gap-3 px-4 py-2.5">
            <dt className="text-slate-500">{label}</dt>
            <dd className="flex min-w-0 items-center gap-2">
                <span className="truncate font-medium text-slate-900">{value}</span>
                <button
                    type="button"
                    aria-label={`Copy ${label}`}
                    onClick={async () => {
                        try {
                            await navigator.clipboard.writeText(String(value));
                            setCopied(true);
                            setTimeout(() => setCopied(false), 1500);
                        } catch {
                            toast.error('Copy failed');
                        }
                    }}
                    className="rounded p-1 text-slate-400 hover:bg-white hover:text-slate-700"
                >
                    {copied ? <Check className="size-3.5 text-emerald-600" /> : <Copy className="size-3.5" />}
                </button>
            </dd>
        </div>
    );
}
