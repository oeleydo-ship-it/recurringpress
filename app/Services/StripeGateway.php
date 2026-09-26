<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Receipt;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;

/**
 * Stripe key resolution (platform account vs. a tenant's own account) and the single,
 * idempotent code path that records a successful PaymentIntent against an invoice.
 */
class StripeGateway
{
    public static function usesOwnAccount(?CompanySetting $settings): bool
    {
        return $settings && $settings->payment_gateway_mode === 'own';
    }

    public static function secretKey(?CompanySetting $settings): ?string
    {
        if (self::usesOwnAccount($settings) && filled($settings->stripe_secret_key)) {
            return $settings->stripe_secret_key;
        }

        return self::platformKey('secret');
    }

    public static function publishableKey(?CompanySetting $settings): ?string
    {
        if (self::usesOwnAccount($settings) && filled($settings->stripe_public_key)) {
            return $settings->stripe_public_key;
        }

        return self::platformKey('publishable');
    }

    /** Whether card payments can be offered for this company right now. */
    public static function available(?CompanySetting $settings): bool
    {
        if ($settings && ! ($settings->accept_stripe || $settings->stripe_enabled)) {
            return false;
        }

        return filled(self::secretKey($settings)) && filled(self::publishableKey($settings));
    }

    public static function platformWebhookSecret(): ?string
    {
        return SystemSetting::get('stripe_webhook_secret') ?: null;
    }

    private static function platformKey(string $type): ?string
    {
        if (! SystemSetting::get('stripe_enabled')) {
            return null;
        }

        $mode = SystemSetting::get('stripe_mode', 'test') === 'live' ? 'live' : 'test';

        return SystemSetting::get("stripe_{$mode}_{$type}_key") ?: null;
    }

    /**
     * Record a succeeded PaymentIntent against an invoice exactly once.
     *
     * Called from both the client's success redirect and the Stripe webhook, which can race;
     * the invoice row lock + transaction_id re-check inside the transaction make it safe.
     * Returns the (new or existing) Payment.
     */
    public static function recordInvoicePayment(Invoice $invoice, object $paymentIntent): Payment
    {
        return DB::transaction(function () use ($invoice, $paymentIntent) {
            $locked = Invoice::withoutGlobalScopes()->whereKey($invoice->id)->lockForUpdate()->firstOrFail();

            $existing = Payment::withoutGlobalScopes()
                ->where('company_id', $locked->company_id)
                ->where('transaction_id', $paymentIntent->id)
                ->lockForUpdate()
                ->first();

            if ($existing && $existing->status === Payment::STATUS_COMPLETED) {
                return $existing;
            }

            $company = Company::withoutGlobalScopes()->with('settings')->findOrFail($locked->company_id);
            $amount = round(((int) $paymentIntent->amount_received ?: (int) $paymentIntent->amount) / 100, 2);

            $payment = $existing ?? new Payment([
                'company_id' => $locked->company_id,
                'invoice_id' => $locked->id,
                'client_id' => $locked->client_id,
                'payment_number' => $company->getNextPaymentNumber(),
                'payment_method' => 'stripe',
                'transaction_id' => $paymentIntent->id,
            ]);

            $payment->fill([
                'amount' => $amount,
                'currency' => strtoupper($paymentIntent->currency ?? $company->currency ?? 'USD'),
                'status' => Payment::STATUS_COMPLETED,
                'via_app_gateway' => ! self::usesOwnAccount($company->settings),
                'payment_date' => now(),
            ])->save();

            Receipt::create([
                'company_id' => $locked->company_id,
                'payment_id' => $payment->id,
                'invoice_id' => $locked->id,
                'client_id' => $locked->client_id,
                'receipt_number' => Receipt::generateReceiptNumber($locked->company_id),
                'amount' => $amount,
                'payment_method' => 'stripe',
                'issued_at' => now(),
            ]);

            $locked->addPayment($amount);

            return $payment;
        });
    }
}
