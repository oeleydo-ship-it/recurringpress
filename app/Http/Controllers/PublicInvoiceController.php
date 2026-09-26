<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PublicInvoiceController extends Controller
{
    public function show(string $token)
    {
        $invoice = Invoice::withoutGlobalScopes()
            ->with(['company', 'company.settings', 'client', 'items'])
            ->where('share_token', $token)
            ->firstOrFail();

        $company = $invoice->company;
        $settings = $company->settings;

        $paymentMethods = [];
        if ($settings && $invoice->status !== 'paid') {
            if ($settings->accept_bank_transfer) {
                $paymentMethods[] = 'bank_transfer';
            }
            // Stripe is available if: accept_stripe is enabled OR stripe_enabled is true, AND credentials exist
            if ($this->hasStripeCredentials($settings)) {
                $paymentMethods[] = 'stripe';
            }
        }

        $amountDue = $invoice->total - ($invoice->paid_amount ?? 0);

        return view('invoices.public', compact('invoice', 'company', 'settings', 'paymentMethods', 'amountDue'));
    }

    public function selectPayment(Request $request, string $token)
    {
        if ($request->isMethod('get')) {
            return redirect()->route('invoices.public', $token);
        }

        $invoice = Invoice::withoutGlobalScopes()
            ->with(['company', 'company.settings', 'client', 'items'])
            ->where('share_token', $token)
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return back()->with('error', 'This invoice has already been paid.');
        }

        $paymentMethod = $request->input('payment_method');
        $settings = $invoice->company->settings;
        $amountDue = $invoice->total - ($invoice->paid_amount ?? 0);

        if ($paymentMethod === 'bank_transfer') {
            return view('invoices.payment-bank', compact('invoice', 'settings', 'amountDue'));
        }

        if ($paymentMethod === 'stripe') {
            return $this->initiateStripePayment($invoice, $settings, $amountDue);
        }

        return back()->with('error', 'Invalid payment method selected.');
    }

    public function createStripeIntent(string $token)
    {
        $invoice = Invoice::withoutGlobalScopes()
            ->with(['company', 'company.settings'])
            ->where('share_token', $token)
            ->firstOrFail();

        if ($invoice->status === 'paid') {
            return response()->json(['error' => 'Invoice already paid'], 400);
        }

        $settings = $invoice->company->settings;
        $stripeSecretKey = $this->getStripeSecretKey($settings);

        if (!$stripeSecretKey) {
            return response()->json(['error' => 'Stripe not configured'], 400);
        }

        $amountDue = $invoice->total - ($invoice->paid_amount ?? 0);

        Stripe::setApiKey($stripeSecretKey);

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) round($amountDue * 100),
                'currency' => strtolower($invoice->company->currency ?? 'usd'),
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'company_id' => $invoice->company_id,
                    'client_id' => $invoice->client_id,
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
        $invoice = Invoice::withoutGlobalScopes()
            ->with(['company', 'company.settings', 'client', 'items'])
            ->where('share_token', $token)
            ->firstOrFail();

        $paymentIntentId = $request->input('payment_intent');

        if (!$paymentIntentId) {
            return redirect()->route('invoices.public', $token)->with('error', 'Payment verification failed.');
        }

        $settings = $invoice->company->settings;
        $stripeSecretKey = $this->getStripeSecretKey($settings);

        Stripe::setApiKey($stripeSecretKey);

        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);

            $metadataInvoiceId = (int) ($paymentIntent->metadata->invoice_id ?? 0);
            $metadataCompanyId = (int) ($paymentIntent->metadata->company_id ?? 0);

            if ($metadataInvoiceId !== $invoice->id || $metadataCompanyId !== $invoice->company_id) {
                return redirect()->route('invoices.public', $token)->with('error', 'Payment verification failed.');
            }

            if ($paymentIntent->status === 'succeeded') {
                $amountPaid = $paymentIntent->amount / 100;

                // Check if payment already exists for this transaction
                $existingPayment = Payment::withoutGlobalScopes()
                    ->where('transaction_id', $paymentIntent->id)
                    ->where('company_id', $invoice->company_id)
                    ->first();

                if ($existingPayment) {
                    return view('invoices.payment-success', compact('invoice', 'existingPayment', 'paymentIntent'))
                        ->with('payment', $existingPayment);
                }

                // Check if using app gateway (not own gateway)
                $viaAppGateway = !($settings && $settings->payment_gateway_mode === 'own');

                // Record payment
                $payment = Payment::create([
                    'company_id' => $invoice->company_id,
                    'invoice_id' => $invoice->id,
                    'client_id' => $invoice->client_id,
                    'payment_number' => $this->generatePaymentNumber($invoice->company_id),
                    'payment_method' => 'stripe',
                    'transaction_id' => $paymentIntent->id,
                    'amount' => $amountPaid,
                    'currency' => $invoice->company->currency ?? 'USD',
                    'status' => 'completed',
                    'via_app_gateway' => $viaAppGateway,
                    'payment_date' => now(),
                ]);

                // Create receipt
                Receipt::create([
                    'company_id' => $invoice->company_id,
                    'payment_id' => $payment->id,
                    'invoice_id' => $invoice->id,
                    'client_id' => $invoice->client_id,
                    'receipt_number' => Receipt::generateReceiptNumber($invoice->company_id),
                    'amount' => $amountPaid,
                    'payment_method' => 'stripe',
                    'issued_at' => now(),
                ]);

                // Update invoice
                $invoice->addPayment($amountPaid);

                return view('invoices.payment-success', compact('invoice', 'payment', 'paymentIntent'));
            }
        } catch (\Exception $e) {
            return redirect()->route('invoices.public', $token)->with('error', 'Payment verification failed: ' . $e->getMessage());
        }

        return redirect()->route('invoices.public', $token)->with('error', 'Payment was not successful.');
    }

    protected function initiateStripePayment(Invoice $invoice, $settings, $amountDue)
    {
        $publishableKey = $this->getStripePublishableKey($settings);

        if (!$publishableKey) {
            return back()->with('error', 'Stripe is not configured properly.');
        }

        return view('invoices.payment-stripe', compact('invoice', 'settings', 'publishableKey', 'amountDue'));
    }

    protected function hasStripeCredentials($settings): bool
    {
        if (!$settings) {
            // Fall back to platform credentials if no settings
            return $this->hasPlatformStripeCredentials();
        }

        // Check if Stripe is enabled (either accept_stripe or stripe_enabled)
        $stripeEnabled = $settings->accept_stripe || $settings->stripe_enabled;
        if (!$stripeEnabled) {
            return false;
        }

        // If using own gateway, check tenant's keys
        if ($settings->payment_gateway_mode === 'own') {
            return !empty($settings->stripe_public_key) && !empty($settings->stripe_secret_key);
        }

        // Using app gateway - check platform's keys from SystemSetting
        return $this->hasPlatformStripeCredentials();
    }

    protected function hasPlatformStripeCredentials(): bool
    {
        $stripeEnabled = SystemSetting::get('stripe_enabled');
        if (!$stripeEnabled) {
            return false;
        }

        $mode = SystemSetting::get('stripe_mode', 'test');
        
        if ($mode === 'live') {
            return !empty(SystemSetting::get('stripe_live_secret_key')) && 
                   !empty(SystemSetting::get('stripe_live_publishable_key'));
        }
        
        return !empty(SystemSetting::get('stripe_test_secret_key')) && 
               !empty(SystemSetting::get('stripe_test_publishable_key'));
    }

    protected function getStripeSecretKey($settings): ?string
    {
        if ($settings && $settings->payment_gateway_mode === 'own' && !empty($settings->stripe_secret_key)) {
            return $settings->stripe_secret_key;
        }

        // Use platform's Stripe keys from SystemSetting
        return $this->getPlatformStripeSecretKey();
    }

    protected function getStripePublishableKey($settings): ?string
    {
        if ($settings && $settings->payment_gateway_mode === 'own' && !empty($settings->stripe_public_key)) {
            return $settings->stripe_public_key;
        }

        // Use platform's Stripe keys from SystemSetting
        return $this->getPlatformStripePublishableKey();
    }

    protected function getPlatformStripeSecretKey(): ?string
    {
        $stripeEnabled = SystemSetting::get('stripe_enabled');
        if (!$stripeEnabled) {
            return null;
        }

        $mode = SystemSetting::get('stripe_mode', 'test');
        
        if ($mode === 'live') {
            return SystemSetting::get('stripe_live_secret_key');
        }
        
        return SystemSetting::get('stripe_test_secret_key');
    }

    protected function getPlatformStripePublishableKey(): ?string
    {
        $stripeEnabled = SystemSetting::get('stripe_enabled');
        if (!$stripeEnabled) {
            return null;
        }

        $mode = SystemSetting::get('stripe_mode', 'test');
        
        if ($mode === 'live') {
            return SystemSetting::get('stripe_live_publishable_key');
        }
        
        return SystemSetting::get('stripe_test_publishable_key');
    }

    protected function generatePaymentNumber($companyId): string
    {
        $company = \App\Models\Company::withoutGlobalScopes()->findOrFail($companyId);
        return $company->getNextPaymentNumber();
    }
}
