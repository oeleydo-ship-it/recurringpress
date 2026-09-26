<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Services\StripeGateway;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['invoice', 'client', 'receipt'])->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                    ->orWhere('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('invoice', fn ($q) => $q->where('invoice_number', 'like', "%{$search}%"))
                    ->orWhereHas('client', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('company_name', 'like', "%{$search}%"));
            });
        }

        $payments = $query->paginate(20)->withQueryString();

        return Inertia::render('Payments/Index', [
            'payments' => $payments->through(fn (Payment $p) => $this->row($p)),
            'filters' => $request->only('search', 'status', 'payment_method'),
            'paymentMethods' => Payment::paymentMethods(),
            'summary' => [
                'received_30d' => (float) Payment::where('status', 'completed')->where('payment_date', '>=', now()->subDays(30))->sum('amount'),
                'count_30d' => Payment::where('status', 'completed')->where('payment_date', '>=', now()->subDays(30))->count(),
                'refunded' => (float) Payment::sum('refunded_amount'),
            ],
        ]);
    }

    public function show(Payment $payment)
    {
        $payment->load(['invoice', 'client', 'receipt']);

        return Inertia::render('Payments/Show', [
            'payment' => [
                ...$this->row($payment),
                'currency' => $payment->currency,
                'notes' => $payment->notes,
                'refund_reason' => $payment->refund_reason,
                'refunded_at' => $payment->refunded_at?->toIso8601String(),
                'remaining_refundable' => (float) $payment->remaining_refundable,
                'can_refund' => $payment->canRefund(),
                'via_app_gateway' => (bool) $payment->via_app_gateway,
                'created_at' => $payment->created_at?->toIso8601String(),
                'client_email' => $payment->client?->email,
                'invoice_total' => $payment->invoice ? (float) $payment->invoice->total : null,
                'invoice_balance' => $payment->invoice ? (float) $payment->invoice->remaining_amount : null,
            ],
        ]);
    }

    /**
     * Record a refund against a payment (money is returned outside the app, e.g. in the
     * Stripe dashboard or by bank transfer). Keeps the invoice's paid amount and status in
     * sync — previously a refund left the invoice showing as fully paid.
     */
    public function refund(Request $request, Payment $payment)
    {
        if (! $payment->canRefund()) {
            return back()->with('error', 'This payment cannot be refunded.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $payment->remaining_refundable,
            'reason' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($payment, $validated) {
            $payment = Payment::whereKey($payment->id)->lockForUpdate()->first();
            $amount = (float) $validated['amount'];

            $payment->refunded_amount = (float) $payment->refunded_amount + $amount;
            $payment->refund_reason = $validated['reason'];
            $payment->refunded_at = now();
            $payment->status = $payment->refunded_amount >= (float) $payment->amount
                ? Payment::STATUS_REFUNDED
                : Payment::STATUS_PARTIALLY_REFUNDED;
            $payment->save();

            if ($invoice = $payment->invoice) {
                $invoice = Invoice::withoutGlobalScopes()->whereKey($invoice->id)->lockForUpdate()->first();
                $invoice->paid_amount = max(0, (float) $invoice->paid_amount - $amount);

                if ($invoice->paid_amount <= 0) {
                    $invoice->status = Invoice::STATUS_REFUNDED;
                    $invoice->paid_at = null;
                } elseif ($invoice->paid_amount < (float) $invoice->total) {
                    $invoice->status = Invoice::STATUS_PARTIAL;
                    $invoice->paid_at = null;
                }

                $invoice->save();
            }
        });

        return back()->with('success', 'Refund recorded.');
    }

    public function receipt(Payment $payment)
    {
        $payment->load(['invoice', 'client', 'company', 'receipt']);

        if (! $payment->receipt) {
            return back()->with('error', 'No receipt found for this payment.');
        }

        $receipt = $payment->receipt;

        return Pdf::loadView('receipts.pdf', compact('receipt', 'payment'))
            ->download("receipt-{$receipt->receipt_number}.pdf");
    }

    /**
     * Card payment started from inside the app. The PaymentIntent is tagged with the
     * invoice/company so the webhook (or success redirect) can record it.
     */
    public function createPaymentIntent(Invoice $invoice)
    {
        $invoice->load('company.settings');
        $settings = $invoice->company->settings;

        if (! StripeGateway::available($settings)) {
            return response()->json(['error' => 'Stripe is not configured'], 400);
        }

        $amountDue = $invoice->remaining_amount;

        if ($amountDue <= 0) {
            return response()->json(['error' => 'Invoice is already paid'], 400);
        }

        Stripe::setApiKey(StripeGateway::secretKey($settings));

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) round($amountDue * 100),
                'currency' => strtolower($invoice->company->currency ?? 'usd'),
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'company_id' => $invoice->company_id,
                ],
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'publicKey' => StripeGateway::publishableKey($settings),
            ]);
        } catch (\Throwable $e) {
            Log::error('Stripe PaymentIntent creation failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Could not start the card payment. Please try again.'], 500);
        }
    }

    /**
     * Stripe webhook. Previously this looked up a non-existent payments column, swallowed
     * the SQL error and answered 400 to every event, so no webhook was ever processed.
     *
     * The signature is verified against the platform secret, then each tenant's own secret.
     * A tenant secret may only settle invoices of that same tenant.
     */
    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = (string) $request->header('Stripe-Signature');

        [$event, $verifiedCompanyId] = $this->verifyWebhook($payload, $signature);

        if (! $event) {
            return response()->json(['error' => 'Webhook verification failed'], 400);
        }

        if ($event->type !== 'payment_intent.succeeded') {
            return response()->json(['status' => 'ignored']);
        }

        $intent = $event->data->object;
        $invoiceId = (int) ($intent->metadata->invoice_id ?? 0);
        $companyId = (int) ($intent->metadata->company_id ?? 0);

        if (! $invoiceId || ! $companyId) {
            return response()->json(['status' => 'ignored']); // not an invoice payment (e.g. subscription)
        }

        if ($verifiedCompanyId !== null && $verifiedCompanyId !== $companyId) {
            Log::warning('Stripe webhook company mismatch', ['verified' => $verifiedCompanyId, 'metadata' => $companyId]);

            return response()->json(['error' => 'Company mismatch'], 400);
        }

        $invoice = Invoice::withoutGlobalScopes()->where('id', $invoiceId)->where('company_id', $companyId)->first();

        if (! $invoice) {
            return response()->json(['status' => 'unknown invoice']);
        }

        try {
            StripeGateway::recordInvoicePayment($invoice, $intent);
        } catch (\Throwable $e) {
            Log::error('Stripe webhook failed to record payment', ['invoice_id' => $invoiceId, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Processing failed'], 500); // Stripe will retry
        }

        return response()->json(['status' => 'success']);
    }

    public function paymentSuccess(Request $request, Invoice $invoice)
    {
        return redirect()->route('invoices.show', $invoice);
    }

    public function recordManualPayment(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            // Bounded by the balance (previously unbounded, allowing over-payment).
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->remaining_amount,
            'payment_method' => ['required', Rule::in(array_keys(Payment::paymentMethods()))],
            'transaction_id' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'paid_at' => 'required|date|before_or_equal:today',
        ]);

        if (! empty($validated['transaction_id'])) {
            $duplicate = Payment::where('invoice_id', $invoice->id)
                ->where('transaction_id', $validated['transaction_id'])
                ->exists();

            if ($duplicate) {
                return back()->with('error', 'A payment with this transaction ID has already been recorded for this invoice.');
            }
        }

        DB::transaction(function () use ($invoice, $validated) {
            $payment = Payment::create([
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'payment_number' => $invoice->company->getNextPaymentNumber(),
                'payment_method' => $validated['payment_method'],
                'transaction_id' => $validated['transaction_id'] ?? null,
                'amount' => $validated['amount'],
                'currency' => $invoice->company->currency,
                'status' => 'completed',
                'notes' => $validated['notes'] ?? null,
                'payment_date' => $validated['paid_at'],
            ]);

            $this->createReceipt($payment);

            $invoice->addPayment((float) $validated['amount']);
        });

        return back()->with('success', 'Payment recorded and receipt generated successfully.');
    }

    /* ------------------------------------------------------------------ */

    /** @return array{0: ?\Stripe\Event, 1: ?int} event + the tenant whose secret verified it (null = platform) */
    private function verifyWebhook(string $payload, string $signature): array
    {
        if ($secret = StripeGateway::platformWebhookSecret()) {
            try {
                return [Webhook::constructEvent($payload, $signature, $secret), null];
            } catch (\Throwable) {
                // fall through to tenant secrets
            }
        }

        $companies = Company::whereHas('settings', fn ($q) => $q->where('payment_gateway_mode', 'own')->whereNotNull('stripe_webhook_secret'))
            ->with('settings')->get();

        foreach ($companies as $company) {
            try {
                return [Webhook::constructEvent($payload, $signature, $company->settings->stripe_webhook_secret), $company->id];
            } catch (\Throwable) {
                continue;
            }
        }

        return [null, null];
    }

    private function row(Payment $p): array
    {
        return [
            'id' => $p->id,
            'payment_number' => $p->payment_number,
            'amount' => (float) $p->amount,
            'refunded_amount' => (float) $p->refunded_amount,
            'payment_method' => $p->payment_method,
            'payment_method_label' => Payment::paymentMethods()[$p->payment_method] ?? ucwords(str_replace('_', ' ', (string) $p->payment_method)),
            'status' => $p->status,
            'transaction_id' => $p->transaction_id,
            'payment_date' => $p->payment_date?->toIso8601String(),
            'client_name' => $p->client?->bill_to_name,
            'client_id' => $p->client_id,
            'invoice_id' => $p->invoice_id,
            'invoice_number' => $p->invoice?->invoice_number,
            'receipt_id' => $p->receipt?->id,
            'receipt_number' => $p->receipt?->receipt_number,
        ];
    }

    protected function createReceipt(Payment $payment): Receipt
    {
        return Receipt::create([
            'company_id' => $payment->company_id,
            'payment_id' => $payment->id,
            'invoice_id' => $payment->invoice_id,
            'client_id' => $payment->client_id,
            'receipt_number' => Receipt::generateReceiptNumber($payment->company_id),
            'amount' => $payment->amount,
            'payment_method' => $payment->payment_method,
            'issued_at' => now(),
            'notes' => $payment->notes,
        ]);
    }
}
