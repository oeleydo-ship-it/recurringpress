<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Notifications\QuotationResponseNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PublicQuotationController extends Controller
{
    public function show(string $token)
    {
        $quotation = Quotation::withoutGlobalScopes()
            ->with([
                'company',
                'company.settings',
                'items',
                'client' => function ($query) {
                    $query->withoutGlobalScopes();
                }
            ])
            ->where('share_token', $token)
            ->firstOrFail();

        // Record the first time the client opened the link. Sharing the link is itself
        // the act of sending, so a quotation still sitting in draft is promoted to sent
        // here — that keeps the status honest and makes the accept path below coherent.
        if (!$quotation->viewed_at) {
            $quotation->update([
                'viewed_at' => now(),
                'sent_at' => $quotation->sent_at ?? now(),
                'status' => $quotation->status === Quotation::STATUS_DRAFT
                    ? Quotation::STATUS_SENT
                    : $quotation->status,
            ]);
        }

        $company = $quotation->company;
        $settings = $company->settings;

        $paymentMethods = [];
        if ($settings) {
            if ($settings->accept_bank_transfer) {
                $paymentMethods[] = 'bank_transfer';
            }
            // Stripe is available if credentials exist and is enabled
            if ($this->hasStripeCredentials($settings)) {
                $paymentMethods[] = 'stripe';
            }
        }

        return view('quotations.public', compact('quotation', 'company', 'settings', 'paymentMethods'));
    }

    public function accept(Request $request, string $token)
    {
        $quotation = Quotation::withoutGlobalScopes()
            ->with([
                'company',
                'client' => function ($query) {
                    $query->withoutGlobalScopes();
                }
            ])
            ->where('share_token', $token)
            ->firstOrFail();

        if (!$quotation->canTransitionTo(Quotation::STATUS_ACCEPTED)) {
            return back()->with('error', 'This quotation cannot be accepted.');
        }

        if ($quotation->isExpired()) {
            return back()->with('error', 'This quotation has expired.');
        }

        $quotation->update([
            'status' => Quotation::STATUS_ACCEPTED,
            'responded_at' => now(),
            'response_notes' => $request->input('notes'),
            'response_ip' => $request->ip(),
        ]);

        // Notify company users
        $this->notifyCompany($quotation, 'accepted');

        return back()->with('success', 'Quotation accepted successfully! The company will contact you shortly.');
    }

    public function reject(Request $request, string $token)
    {
        $quotation = Quotation::withoutGlobalScopes()
            ->with([
                'company',
                'client' => function ($query) {
                    $query->withoutGlobalScopes();
                }
            ])
            ->where('share_token', $token)
            ->firstOrFail();

        if (!$quotation->canTransitionTo(Quotation::STATUS_REJECTED)) {
            return back()->with('error', 'This quotation cannot be rejected.');
        }

        $request->validate([
            'rejection_reason' => 'nullable|string|max:1000',
        ]);

        $quotation->update([
            'status' => Quotation::STATUS_REJECTED,
            'responded_at' => now(),
            'response_notes' => $request->input('rejection_reason'),
            'response_ip' => $request->ip(),
        ]);

        // Notify company users
        $this->notifyCompany($quotation, 'rejected');

        return back()->with('success', 'Quotation rejected. Thank you for your response.');
    }

    public function selectPayment(Request $request, string $token)
    {
        $quotation = Quotation::withoutGlobalScopes()
            ->with([
                'company',
                'company.settings',
                'items',
                'client' => function ($query) {
                    $query->withoutGlobalScopes();
                }
            ])
            ->where('share_token', $token)
            ->firstOrFail();

        if ($quotation->status !== Quotation::STATUS_ACCEPTED) {
            return back()->with('error', 'Please accept the quotation first before proceeding to payment.');
        }

        $paymentMethod = $request->input('payment_method');
        $settings = $quotation->company->settings;

        if ($paymentMethod === 'bank_transfer') {
            return view('quotations.payment-bank', compact('quotation', 'settings'));
        }

        if ($paymentMethod === 'stripe') {
            return $this->initiateStripePayment($quotation, $settings);
        }

        return back()->with('error', 'Invalid payment method selected.');
    }

    public function createStripeIntent(string $token)
    {
        $quotation = Quotation::withoutGlobalScopes()
            ->with(['company', 'company.settings'])
            ->where('share_token', $token)
            ->firstOrFail();

        if ($quotation->status !== Quotation::STATUS_ACCEPTED) {
            return response()->json(['error' => 'Quotation not accepted'], 400);
        }

        $settings = $quotation->company->settings;
        $stripeSecretKey = $this->getStripeSecretKey($settings);

        if (!$stripeSecretKey) {
            return response()->json(['error' => 'Stripe not configured'], 400);
        }

        Stripe::setApiKey($stripeSecretKey);

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) ($quotation->total * 100),
                'currency' => strtolower($quotation->company->currency ?? 'usd'),
                'metadata' => [
                    'quotation_id' => $quotation->id,
                    'company_id' => $quotation->company_id,
                    'client_id' => $quotation->client_id,
                ],
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'publishableKey' => $this->getStripePublishableKey($settings),
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function stripeSuccess(Request $request, string $token)
    {
        $quotation = Quotation::withoutGlobalScopes()
            ->with([
                'company',
                'company.settings',
                'items',
                'client' => function ($query) {
                    $query->withoutGlobalScopes();
                }
            ])
            ->where('share_token', $token)
            ->firstOrFail();

        $paymentIntentId = $request->input('payment_intent');

        if (!$paymentIntentId) {
            return redirect()->route('quotations.public', $token)->with('error', 'Payment verification failed.');
        }

        $settings = $quotation->company->settings;
        $stripeSecretKey = $this->getStripeSecretKey($settings);

        Stripe::setApiKey($stripeSecretKey);

        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            if ($paymentIntent->status === 'succeeded') {
                // Convert quotation to invoice and record payment
                $invoice = $this->convertToInvoiceAndPay($quotation, $paymentIntent);

                return view('quotations.payment-success', compact('quotation', 'invoice', 'paymentIntent'));
            }
        } catch (\Exception $e) {
            return redirect()->route('quotations.public', $token)->with('error', 'Payment verification failed: ' . $e->getMessage());
        }

        return redirect()->route('quotations.public', $token)->with('error', 'Payment was not successful.');
    }

    protected function initiateStripePayment(Quotation $quotation, $settings)
    {
        $publishableKey = $this->getStripePublishableKey($settings);

        if (!$publishableKey) {
            return back()->with('error', 'Stripe is not configured properly.');
        }

        return view('quotations.payment-stripe', compact('quotation', 'settings', 'publishableKey'));
    }

    protected function hasStripeCredentials($settings): bool
    {
        if (!$settings) {
            return !empty(config('services.stripe.secret')) && !empty(config('services.stripe.key'));
        }

        // Check if Stripe is enabled
        $stripeEnabled = $settings->accept_stripe || $settings->stripe_enabled;
        if (!$stripeEnabled) {
            return false;
        }

        // If using own gateway, check tenant's keys
        if ($settings->payment_gateway_mode === 'own') {
            return !empty($settings->stripe_public_key) && !empty($settings->stripe_secret_key);
        }

        // Use app-level Stripe
        return !empty(config('services.stripe.secret')) && !empty(config('services.stripe.key'));
    }

    protected function getStripeSecretKey($settings): ?string
    {
        if ($settings->payment_gateway_mode === 'own' && !empty($settings->stripe_secret_key)) {
            return $settings->stripe_secret_key;
        }

        return config('services.stripe.secret');
    }

    protected function getStripePublishableKey($settings): ?string
    {
        if ($settings->payment_gateway_mode === 'own' && !empty($settings->stripe_public_key)) {
            return $settings->stripe_public_key;
        }

        return config('services.stripe.key');
    }

    protected function notifyCompany(Quotation $quotation, string $action): void
    {
        $users = $quotation->company->users;

        foreach ($users as $user) {
            try {
                $user->notify(new QuotationResponseNotification($quotation, $action));
            } catch (\Exception $e) {
                \Log::error('Failed to send quotation notification: ' . $e->getMessage());
            }
        }
    }

    protected function convertToInvoiceAndPay(Quotation $quotation, $paymentIntent): Invoice
    {
        // Check if already converted
        if ($quotation->converted_invoice_id) {
            return Invoice::withoutGlobalScopes()->find($quotation->converted_invoice_id);
        }

        $company = \App\Models\Company::withoutGlobalScopes()->findOrFail($quotation->company_id);

        // The Stripe charge has already succeeded by this point, so the invoice, payment,
        // receipt and quotation status must all land together. Without this, a failure
        // part-way through would leave the quotation unconverted and a retry (e.g. the
        // client refreshing the success page) would bill a second invoice and receipt
        // against the same payment intent.
        return \Illuminate\Support\Facades\DB::transaction(function () use ($quotation, $paymentIntent, $company) {
            return $this->buildPaidInvoice($quotation, $paymentIntent, $company);
        });
    }

    protected function buildPaidInvoice(Quotation $quotation, $paymentIntent, $company): Invoice
    {
        // Create invoice from quotation
        $invoice = Invoice::withoutGlobalScopes()->create([
            'company_id' => $quotation->company_id,
            'client_id' => $quotation->client_id,
            'created_by' => $quotation->user_id,
            'invoice_number' => $company->getNextInvoiceNumber(),
            'reference' => $quotation->reference,
            'issue_date' => now(),
            'due_date' => now()->addDays($company->payment_terms ?? 30),
            'status' => 'paid',
            'subtotal' => $quotation->subtotal,
            'tax_amount' => $quotation->tax_amount,
            'discount_amount' => $quotation->discount_amount,
            'discount_type' => $quotation->discount_type,
            'total' => $quotation->total,
            'paid_amount' => $quotation->total,
            'notes' => $quotation->notes,
            'terms' => $quotation->terms,
            'paid_at' => now(),
        ]);

        // Copy items
        foreach ($quotation->items as $item) {
            $invoice->items()->create([
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'tax_rate' => $item->tax_rate,
                'unit' => $item->unit,
            ]);
        }

        // Check if using app gateway (not own gateway)
        $settings = $quotation->company->settings;
        $viaAppGateway = !($settings && $settings->payment_gateway_mode === 'own');

        // Record payment
        $payment = Payment::create([
            'company_id' => $quotation->company_id,
            'invoice_id' => $invoice->id,
            'client_id' => $quotation->client_id,
            'payment_number' => $company->getNextPaymentNumber(),
            'payment_method' => 'stripe',
            'transaction_id' => $paymentIntent->id,
            'amount' => $quotation->total,
            'currency' => $quotation->company->currency ?? 'USD',
            'status' => 'completed',
            'via_app_gateway' => $viaAppGateway,
            'payment_date' => now(),
        ]);

        // Create receipt
        Receipt::create([
            'company_id' => $quotation->company_id,
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'client_id' => $quotation->client_id,
            'receipt_number' => Receipt::generateReceiptNumber($quotation->company_id),
            'amount' => $quotation->total,
            'payment_method' => 'stripe',
            'issued_at' => now(),
        ]);

        // Update quotation
        $quotation->update([
            'status' => Quotation::STATUS_CONVERTED,
            'converted_invoice_id' => $invoice->id,
        ]);

        return $invoice;
    }
}
