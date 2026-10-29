<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Notifications\QuotationResponseNotification;
use App\Services\StripeGateway;
use App\Services\TenantNotifier;
use App\Support\PublicDocument;
use App\Support\TenantDomain;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Stripe\PaymentIntent;
use Stripe\Stripe;

/**
 * Client-facing quotation page: review, accept / reject, and optionally pay up-front.
 */
class PublicQuotationController extends Controller
{
    public function show(string $token)
    {
        $quotation = $this->find($token);

        // Opening the link counts as delivery: record the first view and promote drafts to sent.
        if (! $quotation->viewed_at) {
            $quotation->update([
                'viewed_at' => now(),
                'sent_at' => $quotation->sent_at ?? now(),
                'status' => $quotation->status === Quotation::STATUS_DRAFT ? Quotation::STATUS_SENT : $quotation->status,
            ]);

            if (auth()->user()?->company_id !== $quotation->company_id) {
                TenantNotifier::notify(
                    $quotation->company_id,
                    'quotation_viewed',
                    "Quotation viewed: {$quotation->quotation_number}",
                    ($quotation->client?->bill_to_name ?? 'Your client') . ' opened the quotation link.',
                    TenantNotifier::path('quotations.show', $quotation->id),
                );
            }
        }

        $settings = $quotation->company->settings;
        $accepted = $quotation->status === Quotation::STATUS_ACCEPTED;

        return Inertia::render('Public/Quotation', [
            'quotation' => [
                'number' => $quotation->quotation_number,
                'status' => $quotation->isExpired() && in_array($quotation->status, ['draft', 'sent'], true) ? 'expired' : $quotation->status,
                'issue_date' => $quotation->issue_date?->toDateString(),
                'valid_until' => $quotation->valid_until?->toDateString(),
                'total' => (float) $quotation->total,
                'currency' => $quotation->company->currency ?? 'USD',
                'client_name' => $quotation->client?->bill_to_name,
                'responded_at' => $quotation->responded_at?->toIso8601String(),
                'can_respond' => ! $quotation->isExpired() && $quotation->canTransitionTo(Quotation::STATUS_ACCEPTED) && in_array($quotation->status, ['draft', 'sent', 'expired'], true),
            ],
            'company' => PublicDocument::company($quotation->company),
            'payment' => $accepted && ! $quotation->converted_invoice_id ? PublicDocument::paymentOptions($settings) : null,
            'urls' => [
                'document' => route('quotations.public.document', $token),
                'pdf' => route('quotations.public.download', $token),
                'accept' => route('quotations.public.accept', $token),
                'reject' => route('quotations.public.reject', $token),
                'intent' => route('quotations.public.stripe.intent', $token),
                'return' => route('quotations.public.stripe.success', $token),
            ],
        ]);
    }

    public function document(string $token)
    {
        $quotation = $this->find($token);

        return view($this->templateView($quotation), compact('quotation'));
    }

    public function download(string $token)
    {
        $quotation = $this->find($token);

        return Pdf::loadView($this->templateView($quotation), compact('quotation'))
            ->download("quotation-{$quotation->quotation_number}.pdf");
    }

    public function accept(Request $request, string $token)
    {
        $quotation = $this->find($token);
        $request->validate(['notes' => 'nullable|string|max:1000']);

        if ($quotation->isExpired()) {
            return back()->with('error', 'This quotation has expired.');
        }

        if (! $quotation->canTransitionTo(Quotation::STATUS_ACCEPTED)) {
            return back()->with('error', 'This quotation cannot be accepted.');
        }

        $quotation->update([
            'status' => Quotation::STATUS_ACCEPTED,
            'responded_at' => now(),
            'response_notes' => $request->input('notes'),
            'response_ip' => $request->ip(),
        ]);

        $this->notifyCompany($quotation, 'accepted');

        return back()->with('success', 'Quotation accepted — thank you! ' . $quotation->company->name . ' has been notified.');
    }

    public function reject(Request $request, string $token)
    {
        $quotation = $this->find($token);
        $request->validate(['rejection_reason' => 'nullable|string|max:1000']);

        if (! $quotation->canTransitionTo(Quotation::STATUS_REJECTED)) {
            return back()->with('error', 'This quotation cannot be declined.');
        }

        $quotation->update([
            'status' => Quotation::STATUS_REJECTED,
            'responded_at' => now(),
            'response_notes' => $request->input('rejection_reason'),
            'response_ip' => $request->ip(),
        ]);

        $this->notifyCompany($quotation, 'rejected');

        return back()->with('success', 'Thanks for letting us know.');
    }

    /** Legacy entry point; payment now happens on the quotation page. */
    public function selectPayment(Request $request, string $token)
    {
        return redirect()->route('quotations.public', $token);
    }

    public function createStripeIntent(string $token)
    {
        $quotation = $this->find($token);
        $settings = $quotation->company->settings;

        if ($quotation->status !== Quotation::STATUS_ACCEPTED || $quotation->converted_invoice_id) {
            return response()->json(['error' => 'Please accept the quotation first.'], 400);
        }

        if ($quotation->company->hasReachedLimit('invoices')) {
            return response()->json(['error' => 'The business cannot issue another invoice on its current plan. Please contact them before paying.'], 409);
        }

        if (! StripeGateway::available($settings)) {
            return response()->json(['error' => 'Card payments are not available.'], 400);
        }

        Stripe::setApiKey(StripeGateway::secretKey($settings));

        try {
            $paymentIntent = PaymentIntent::create([
                'amount' => (int) round((float) $quotation->total * 100),
                'currency' => strtolower($quotation->company->currency ?? 'usd'),
                'description' => "Quotation {$quotation->quotation_number}",
                'metadata' => [
                    'quotation_id' => $quotation->id,
                    'company_id' => $quotation->company_id,
                    'client_id' => $quotation->client_id,
                ],
            ]);

            return response()->json([
                'clientSecret' => $paymentIntent->client_secret,
                'publishableKey' => StripeGateway::publishableKey($settings),
            ]);
        } catch (\Throwable $e) {
            Log::error('Public quotation PaymentIntent failed', ['quotation_id' => $quotation->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Could not start the card payment. Please try again.'], 500);
        }
    }

    /**
     * Previously any succeeded PaymentIntent id converted the quotation into a PAID invoice
     * (no metadata or amount check — on the shared platform account any other payment's id
     * worked). The intent must now be for this quotation, this company and its full total.
     */
    public function stripeSuccess(Request $request, string $token)
    {
        $quotation = $this->find($token);
        $intentId = (string) $request->input('payment_intent');

        if (! str_starts_with($intentId, 'pi_')) {
            return redirect()->route('quotations.public', $token)->with('error', 'Payment verification failed.');
        }

        Stripe::setApiKey(StripeGateway::secretKey($quotation->company->settings));

        try {
            $intent = PaymentIntent::retrieve($intentId);
        } catch (\Throwable $e) {
            Log::warning('Public quotation payment verification failed', ['quotation_id' => $quotation->id, 'error' => $e->getMessage()]);

            return redirect()->route('quotations.public', $token)->with('error', 'We could not verify the payment. If you were charged, please contact us.');
        }

        if (in_array($intent->status, ['processing', 'requires_capture'], true)) {
            return redirect()->route('quotations.public', $token)->with('info', 'Your payment is processing.');
        }

        if (! StripeGateway::intentMatches($intent, $quotation->company_id, 'quotation_id', $quotation->id, (float) $quotation->total)) {
            Log::warning('Rejected public quotation payment', ['quotation_id' => $quotation->id, 'intent' => $intent->id, 'status' => $intent->status]);

            return redirect()->route('quotations.public', $token)->with('error', 'Payment was not successful.');
        }

        $invoice = StripeGateway::payQuotation($quotation, $intent);

        return redirect()->route('invoices.public', $invoice->share_token)
            ->with('success', 'Payment received — thank you! Here is your paid invoice.');
    }

    private function find(string $token): Quotation
    {
        abort_unless(preg_match('/^[A-Za-z0-9]{16,64}$/', $token), 404);

        return Quotation::withoutGlobalScopes()
            ->with(['company.settings', 'company.users', 'items', 'client' => fn ($q) => $q->withoutGlobalScopes()])
            ->where('share_token', $token)
            ->tap(fn ($query) => TenantDomain::scopeToRequestDomain($query))
            ->firstOrFail();
    }

    private function templateView(Quotation $quotation): string
    {
        $template = $quotation->company->settings->invoice_template ?? 'classic';

        return view()->exists("quotations.pdf.{$template}") ? "quotations.pdf.{$template}" : 'quotations.pdf.classic';
    }

    protected function notifyCompany(Quotation $quotation, string $action): void
    {
        $accepted = $action === 'accepted';
        TenantNotifier::notify(
            $quotation->company_id,
            $accepted ? 'quotation_accepted' : 'quotation_declined',
            ($accepted ? 'Quotation accepted: ' : 'Quotation declined: ') . $quotation->quotation_number,
            ($quotation->client?->bill_to_name ?? 'Your client')
                . ($accepted ? ' approved ' . TenantNotifier::money($quotation->total, $quotation->company->currency) . '.' : ' declined the quotation.')
                . ($quotation->response_notes ? ' “' . Str::limit($quotation->response_notes, 120) . '”' : ''),
            TenantNotifier::path('quotations.show', $quotation->id),
        );

        foreach ($quotation->company->users as $user) {
            try {
                $user->notify(new QuotationResponseNotification($quotation, $action));
            } catch (\Throwable $e) {
                Log::error('Failed to queue quotation notification: ' . $e->getMessage());
            }
        }
    }
}
