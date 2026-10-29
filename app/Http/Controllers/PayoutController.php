<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Payout;
use App\Models\SystemSetting;
use App\Services\TenantNotifier;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PayoutController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company;

        if ($redirect = $this->guardOwnGateway($company)) {
            return $redirect;
        }

        $payouts = Payout::withCount('payments')->orderBy('created_at', 'desc')->paginate(15);

        return Inertia::render('Payouts/Index', [
            'payouts' => $payouts->through(fn (Payout $p) => $this->row($p)),
            'balance' => [
                'available' => $this->availableBalance($company->id),
                'pending' => (float) Payout::whereIn('status', [Payout::STATUS_PENDING, Payout::STATUS_PROCESSING])->sum('amount'),
                'paid_out' => (float) Payout::where('status', Payout::STATUS_COMPLETED)->sum('amount'),
            ],
            'minimum' => (float) SystemSetting::get('payout_minimum_amount', 50),
        ]);
    }

    public function create()
    {
        $company = auth()->user()->company;
        $settings = $company->settings;

        if ($redirect = $this->guardOwnGateway($company)) {
            return $redirect;
        }

        $available = $this->availableBalance($company->id);
        $minimum = (float) SystemSetting::get('payout_minimum_amount', 50);

        if ($available <= 0) {
            return redirect()->route('payouts.index')->with('error', 'No available balance to request a payout.');
        }

        return Inertia::render('Payouts/Create', [
            'available' => $available,
            'minimum' => $minimum,
            'feePercentage' => (float) SystemSetting::get('payout_fee_percentage', 0),
            'paymentCount' => $this->eligiblePayments($company->id)->count(),
            'defaults' => [
                'bank_name' => $settings->bank_name ?? '',
                'bank_account_name' => $settings->bank_account_name ?? '',
                'bank_account_number' => $settings->bank_account_number ?? '',
                'bank_routing_number' => $settings->bank_routing_number ?? '',
                'paypal_email' => $settings->paypal_email ?? '',
            ],
        ]);
    }

    /**
     * Request a payout of the full available balance.
     *
     * Previously the tenant chose an amount but whole payments were attached, so asking for
     * less than the balance silently marked the remainder as paid out (and lost it); two
     * concurrent requests could also withdraw the same payments twice. Payments are now
     * locked and the payout always equals exactly the (refund-adjusted) payments it covers.
     */
    public function store(Request $request)
    {
        $company = auth()->user()->company;

        if ($redirect = $this->guardOwnGateway($company)) {
            return $redirect;
        }

        $validated = $request->validate([
            'payout_method' => 'required|in:bank_transfer,paypal',
            'bank_name' => 'required_if:payout_method,bank_transfer|nullable|string|max:255',
            'bank_account_name' => 'required_if:payout_method,bank_transfer|nullable|string|max:255',
            'bank_account_number' => 'required_without:bank_iban|nullable|string|max:100',
            'bank_routing_number' => 'nullable|string|max:100',
            'bank_iban' => 'nullable|string|max:100',
            'bank_swift' => 'nullable|string|max:50',
            'paypal_email' => 'required_if:payout_method,paypal|nullable|email|max:255',
            'notes' => 'nullable|string|max:1000',
        ], [
            'bank_account_number.required_without' => 'Enter an account number or IBAN.',
        ]);

        if ($validated['payout_method'] === 'paypal') {
            $validated = array_merge($validated, array_fill_keys(['bank_name', 'bank_account_name', 'bank_account_number', 'bank_routing_number', 'bank_iban', 'bank_swift'], null));
        }

        $minimum = (float) SystemSetting::get('payout_minimum_amount', 50);
        $feePercentage = (float) SystemSetting::get('payout_fee_percentage', 0);

        $result = DB::transaction(function () use ($company, $validated, $minimum, $feePercentage) {
            $payments = $this->eligiblePayments($company->id)->lockForUpdate()->get();
            $gross = round($payments->sum(fn ($p) => (float) $p->amount - (float) $p->refunded_amount), 2);

            if ($gross <= 0) {
                return 'No available balance to request a payout.';
            }
            if ($gross < $minimum) {
                return 'The minimum payout is ' . number_format($minimum, 2) . '. Your available balance is ' . number_format($gross, 2) . '.';
            }

            $fee = round($gross * ($feePercentage / 100), 2);

            $payout = Payout::create([
                'company_id' => $company->id,
                'payout_number' => Payout::generatePayoutNumber($company->id),
                'gross_amount' => $gross,
                'fee_percentage' => $feePercentage,
                'fee_amount' => $fee,
                'amount' => $gross - $fee,
                'currency' => $company->currency ?? 'USD',
                'status' => Payout::STATUS_PENDING,
                'payout_method' => $validated['payout_method'],
                'bank_name' => $validated['bank_name'] ?? null,
                'bank_account_name' => $validated['bank_account_name'] ?? null,
                'bank_account_number' => $validated['bank_account_number'] ?? null,
                'bank_routing_number' => $validated['bank_routing_number'] ?? null,
                'bank_iban' => $validated['bank_iban'] ?? null,
                'bank_swift' => $validated['bank_swift'] ?? null,
                'paypal_email' => $validated['paypal_email'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'requested_at' => now(),
            ]);

            Payment::whereIn('id', $payments->pluck('id'))->update(['payout_id' => $payout->id]);

            return $payout;
        });

        if (is_string($result)) {
            return back()->with('error', $result);
        }

        TenantNotifier::notify(
            $company,
            'payout_requested',
            "Payout requested: {$result->payout_number}",
            TenantNotifier::money($result->amount, $result->currency) . ' requested' . TenantNotifier::byActor() . '.',
            TenantNotifier::path('payouts.show', $result->id),
            TenantNotifier::AUDIENCE_ADMINS,
        );

        return redirect()->route('payouts.show', $result)
            ->with('success', 'Payout requested. It is usually processed within 3–5 business days.');
    }

    public function show(Payout $payout)
    {
        $payout->load('payments.invoice', 'payments.client');

        return Inertia::render('Payouts/Show', [
            'payout' => [
                ...$this->row($payout),
                'gross_amount' => (float) $payout->gross_amount,
                'fee_percentage' => (float) $payout->fee_percentage,
                'fee_amount' => (float) $payout->fee_amount,
                'bank_name' => $payout->bank_name,
                'bank_account_name' => $payout->bank_account_name,
                // Only the last 4 digits are shown back to the browser.
                'bank_account_last4' => $payout->bank_account_number ? substr($payout->bank_account_number, -4) : null,
                'bank_iban_last4' => $payout->bank_iban ? substr(preg_replace('/\s+/', '', $payout->bank_iban), -4) : null,
                'paypal_email' => $payout->paypal_email,
                'transaction_id' => $payout->transaction_id,
                'notes' => $payout->notes,
                'admin_notes' => $payout->admin_notes,
                'processed_at' => $payout->processed_at?->toIso8601String(),
            ],
            'payments' => $payout->payments->map(fn (Payment $p) => [
                'id' => $p->id,
                'payment_number' => $p->payment_number,
                'amount' => (float) $p->amount - (float) $p->refunded_amount,
                'payment_date' => $p->payment_date?->toIso8601String(),
                'invoice_id' => $p->invoice_id,
                'invoice_number' => $p->invoice?->invoice_number,
                'client_name' => $p->client?->bill_to_name,
            ]),
        ]);
    }

    /* ------------------------------------------------------------------ */

    /** Platform-gateway payments not yet paid out (fully refunded ones excluded). */
    private function eligiblePayments(int $companyId): Builder
    {
        return Payment::withoutGlobalScopes()
            ->where('company_id', $companyId)
            ->where('via_app_gateway', true)
            ->whereIn('status', [Payment::STATUS_COMPLETED, Payment::STATUS_PARTIALLY_REFUNDED])
            ->whereNull('payout_id')
            ->orderBy('payment_date');
    }

    private function availableBalance(int $companyId): float
    {
        return round((float) $this->eligiblePayments($companyId)->sum(DB::raw('amount - COALESCE(refunded_amount, 0)')), 2);
    }

    private function guardOwnGateway($company)
    {
        if ($company->settings && $company->settings->payment_gateway_mode === 'own') {
            return redirect()->route('dashboard')->with('error', 'Payouts are only available when using the platform payment gateway.');
        }

        return null;
    }

    private function row(Payout $p): array
    {
        return [
            'id' => $p->id,
            'payout_number' => $p->payout_number,
            'amount' => (float) $p->amount,
            'currency' => $p->currency,
            'status' => $p->status,
            'payout_method' => $p->payout_method,
            'requested_at' => ($p->requested_at ?? $p->created_at)?->toIso8601String(),
            'payments_count' => $p->payments_count ?? $p->payments?->count(),
        ];
    }
}
