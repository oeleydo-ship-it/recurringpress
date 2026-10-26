<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Services\StripeGateway;
use App\Support\PublicDocument;
use App\Support\TenantDomain;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Stripe\PaymentIntent;
use Stripe\Stripe;

/**
 * Client-facing invoice page (reached via the unguessable share token).
 */
class PublicInvoiceController extends Controller
{
    public function show(string $token)
    {
        $invoice = $this->find($token);
        $settings = $invoice->company->settings;
        $payable = ! in_array($invoice->status, [Invoice::STATUS_PAID, Invoice::STATUS_CANCELLED, Invoice::STATUS_REFUNDED], true)
            && $invoice->remaining_amount > 0;

        return Inertia::render('Public/Invoice', [
            'invoice' => [
                'number' => $invoice->invoice_number,
                'status' => $invoice->status,
                'issue_date' => $invoice->issue_date?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'total' => (float) $invoice->total,
                'paid' => (float) $invoice->paid_amount,
                'amount_due' => (float) $invoice->remaining_amount,
                'currency' => $invoice->company->currency ?? 'USD',
                'is_overdue' => $payable && $invoice->due_date?->isPast(),
                'client_name' => $invoice->client?->bill_to_name,
            ],
            'company' => PublicDocument::company($invoice->company),
            'payment' => $payable ? PublicDocument::paymentOptions($settings) : null,
            'urls' => [
                'document' => route('invoices.public.document', $token),
                'pdf' => route('invoices.public.download', $token),
                'intent' => route('invoices.public.stripe.intent', $token),
                'return' => route('invoices.public.stripe.success', $token),
            ],
        ]);
    }

    /** The invoice rendered with the company's template (embedded on the public page). */
    public function document(string $token)
    {
        $invoice = $this->find($token);

        return view($this->templateView($invoice), compact('invoice'));
    }

    public function download(string $token)
    {
        $invoice = $this->find($token);

        return Pdf::loadView($this->templateView($invoice), compact('invoice'))
            ->download("invoice-{$invoice->invoice_number}.pdf");
    }

    /** Legacy entry point (old payment-method form); everything now lives on the page. */
    public function selectPayment(Request $request, string $token)
    {
        return redirect()->route('invoices.public', $token);
    }

    public function createStripeIntent(string $token)
    {
        $invoice = $this->find($token);
        $settings = $invoice->company->settings;
        $amountDue = $invoice->remaining_amount;

        if ($invoice->status === Invoice::STATUS_PAID || $amountDue <= 0) {
            return response()->json(['error' => 'This invoice is already paid.'], 400);
        }

        if (! StripeGateway::available($settings)) {
            return response()->json(['error' => 'Card payments are not available for this invoice.'], 400);
        }

        Stripe::setApiKey(StripeGateway::secretKey($settings));

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) round($amountDue * 100),
                'currency' => strtolower($invoice->company->currency ?? 'usd'),
                'description' => "Invoice {$invoice->invoice_number}",
                'metadata' => [
                    'invoice_id' => $invoice->id,
                    'company_id' => $invoice->company_id,
                    'client_id' => $invoice->client_id,
                ],
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'publishableKey' => StripeGateway::publishableKey($settings),
            ]);
        } catch (\Throwable $e) {
            Log::error('Public invoice PaymentIntent failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Could not start the card payment. Please try again.'], 500);
        }
    }

    /**
     * Stripe redirects here after the card is confirmed. The PaymentIntent must belong to
     * this invoice + company; recording is idempotent and race-safe with the webhook.
     */
    public function stripeSuccess(Request $request, string $token)
    {
        $invoice = $this->find($token);
        $intentId = (string) $request->input('payment_intent');

        if (! str_starts_with($intentId, 'pi_')) {
            return redirect()->route('invoices.public', $token)->with('error', 'Payment verification failed.');
        }

        Stripe::setApiKey(StripeGateway::secretKey($invoice->company->settings));

        try {
            $intent = PaymentIntent::retrieve($intentId);
        } catch (\Throwable $e) {
            Log::warning('Public invoice payment verification failed', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);

            return redirect()->route('invoices.public', $token)->with('error', 'We could not verify the payment. If you were charged, please contact us.');
        }

        if (in_array($intent->status, ['processing', 'requires_capture'], true)) {
            return redirect()->route('invoices.public', $token)->with('info', 'Your payment is processing. This page will update once it completes.');
        }

        if (! StripeGateway::intentMatches($intent, $invoice->company_id, 'invoice_id', $invoice->id, 0.01)) {
            Log::warning('Rejected public invoice payment', ['invoice_id' => $invoice->id, 'intent' => $intent->id, 'status' => $intent->status]);

            return redirect()->route('invoices.public', $token)->with('error', 'Payment was not successful.');
        }

        StripeGateway::recordInvoicePayment($invoice, $intent);

        return redirect()->route('invoices.public', $token)->with('success', 'Payment received — thank you! A receipt has been recorded.');
    }

    private function find(string $token): Invoice
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{16,64}$/', $token), 404);

        return Invoice::withoutGlobalScopes()
            ->with(['company.settings', 'client' => fn ($q) => $q->withoutGlobalScopes(), 'items'])
            ->where('share_token', $token)
            ->tap(fn ($query) => TenantDomain::scopeToRequestDomain($query))
            ->firstOrFail();
    }

    private function templateView(Invoice $invoice): string
    {
        $template = $invoice->company->settings->invoice_template ?? 'classic';

        return array_key_exists($template, CompanySetting::invoiceTemplates()) && view()->exists("invoices.pdf.{$template}")
            ? "invoices.pdf.{$template}"
            : 'invoices.pdf.classic';
    }
}
