<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Stripe\Webhook;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    public function index(Request $request)
    {
        $query = Payment::with(['invoice', 'client', 'receipt'])
            ->orderBy('created_at', 'desc');

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
                    ->orWhereHas('invoice', fn($q) => $q->where('invoice_number', 'like', "%{$search}%"))
                    ->orWhereHas('client', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $payments = $query->paginate(15)->withQueryString();
        $paymentMethods = Payment::paymentMethods();

        return view('payments.index', compact('payments', 'paymentMethods'));
    }

    public function show(Payment $payment)
    {
        $payment->load(['invoice.items', 'client', 'receipt', 'company']);
        return view('payments.show', compact('payment'));
    }

    public function refund(Request $request, Payment $payment)
    {
        if (!$payment->canRefund()) {
            return back()->with('error', 'This payment cannot be refunded.');
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $payment->remaining_refundable,
            'reason' => 'required|string|max:500',
        ]);

        DB::transaction(function () use ($payment, $validated) {
            $payment->refunded_amount += $validated['amount'];
            $payment->refund_reason = $validated['reason'];
            $payment->refunded_at = now();

            if ($payment->refunded_amount >= $payment->amount) {
                $payment->status = Payment::STATUS_REFUNDED;
            } else {
                $payment->status = Payment::STATUS_PARTIALLY_REFUNDED;
            }

            $payment->save();

            if ($payment->status === Payment::STATUS_REFUNDED && $payment->invoice) {
                $payment->invoice->update(['status' => 'refunded']);
            }
        });

        return back()->with('success', 'Refund processed successfully.');
    }

    public function receipt(Payment $payment)
    {
        $payment->load(['invoice', 'client', 'company', 'receipt']);

        if (!$payment->receipt) {
            return back()->with('error', 'No receipt found for this payment.');
        }

        $receipt = $payment->receipt;
        $pdf = Pdf::loadView('receipts.pdf', compact('receipt', 'payment'));

        return $pdf->download("receipt-{$receipt->receipt_number}.pdf");
    }


    public function createPaymentIntent(Invoice $invoice)
    {
        $settings = $invoice->company->settings;

        if (!$settings->stripe_enabled || !$settings->stripe_secret_key) {
            return response()->json(['error' => 'Stripe is not configured'], 400);
        }

        Stripe::setApiKey($settings->stripe_secret_key);

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) ($invoice->total * 100),
                'currency' => strtolower($invoice->company->currency),
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'company_id' => $invoice->company_id,
                ],
            ]);

            // Check if a pending payment already exists for this invoice
            $existingPayment = Payment::where('invoice_id', $invoice->id)
                ->where('status', 'pending')
                ->where('payment_method', 'stripe')
                ->first();

            if ($existingPayment) {
                // Update existing pending payment
                $existingPayment->update([
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'amount' => $invoice->total,
                    'currency' => $invoice->company->currency,
                ]);
            } else {
                // Create new pending payment
                $paymentNumber = $invoice->company->getNextPaymentNumber();

                Payment::create([
                    'company_id' => $invoice->company_id,
                    'invoice_id' => $invoice->id,
                    'client_id' => $invoice->client_id,
                    'payment_number' => $paymentNumber,
                    'payment_method' => 'stripe',
                    'stripe_payment_intent_id' => $paymentIntent->id,
                    'amount' => $invoice->total,
                    'currency' => $invoice->company->currency,
                    'status' => 'pending',
                ]);
            }

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'publicKey' => $settings->stripe_public_key,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function handleWebhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        $companies = \App\Models\Company::whereHas('settings', function ($q) {
            $q->where('stripe_enabled', true)
                ->whereNotNull('stripe_webhook_secret');
        })->with('settings')->get();

        foreach ($companies as $company) {
            try {
                $event = Webhook::constructEvent(
                    $payload,
                    $sigHeader,
                    $company->settings->stripe_webhook_secret
                );

                if ($event->type === 'payment_intent.succeeded') {
                    $paymentIntent = $event->data->object;

                    $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

                    if ($payment && $payment->status !== 'completed') {
                        DB::transaction(function () use ($payment, $paymentIntent) {
                            $payment->update([
                                'status' => 'completed',
                                'transaction_id' => $paymentIntent->id,
                                'payment_date' => now(),
                            ]);

                            $this->createReceipt($payment);

                            if ($payment->invoice) {
                                $payment->invoice->addPayment((float) $payment->amount);
                            }
                        });
                    }
                }

                return response()->json(['status' => 'success']);
            } catch (\Exception $e) {
                continue;
            }
        }

        return response()->json(['error' => 'Webhook verification failed'], 400);
    }

    public function paymentSuccess(Request $request, Invoice $invoice)
    {
        return view('payments.success', compact('invoice'));
    }

    public function recordManualPayment(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string',
            'transaction_id' => 'nullable|string',
            'notes' => 'nullable|string',
            'paid_at' => 'required|date',
        ]);

        if (!empty($validated['transaction_id'])) {
            $duplicate = Payment::where('invoice_id', $invoice->id)
                ->where('transaction_id', $validated['transaction_id'])
                ->exists();

            if ($duplicate) {
                return back()->with('error', 'A payment with this transaction ID has already been recorded for this invoice.');
            }
        }

        DB::transaction(function () use ($invoice, $validated) {
            $paymentNumber = $invoice->company->getNextPaymentNumber();

            $payment = Payment::create([
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'payment_number' => $paymentNumber,
                'payment_method' => $validated['payment_method'],
                'transaction_id' => $validated['transaction_id'],
                'amount' => $validated['amount'],
                'currency' => $invoice->company->currency,
                'status' => 'completed',
                'notes' => $validated['notes'],
                'payment_date' => $validated['paid_at'],
            ]);

            $this->createReceipt($payment);

            $invoice->addPayment((float) $validated['amount']);
        });

        return back()->with('success', 'Payment recorded and receipt generated successfully.');
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
