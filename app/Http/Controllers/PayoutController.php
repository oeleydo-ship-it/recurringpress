<?php

namespace App\Http\Controllers;

use App\Models\Payout;
use App\Models\Payment;
use Illuminate\Http\Request;

class PayoutController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company;
        $settings = $company->settings;

        // Only show payouts if using app gateway
        if ($settings && $settings->payment_gateway_mode === 'own') {
            return redirect()->route('dashboard')->with('error', 'Payouts are only available when using the app payment gateway.');
        }

        $payouts = Payout::with('payments')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        // Calculate available balance (payments via app gateway not yet paid out)
        $availableBalance = Payment::where('company_id', $company->id)
            ->where('via_app_gateway', true)
            ->where('status', 'completed')
            ->whereNull('payout_id')
            ->sum('amount');

        // Calculate pending payout amount
        $pendingPayouts = Payout::where('status', Payout::STATUS_PENDING)
            ->orWhere('status', Payout::STATUS_PROCESSING)
            ->sum('amount');

        // Total paid out
        $totalPaidOut = Payout::where('status', Payout::STATUS_COMPLETED)->sum('amount');

        return view('payouts.index', compact('payouts', 'availableBalance', 'pendingPayouts', 'totalPaidOut', 'settings'));
    }

    public function create()
    {
        $company = auth()->user()->company;
        $settings = $company->settings;

        if ($settings && $settings->payment_gateway_mode === 'own') {
            return redirect()->route('payouts.index')->with('error', 'Payouts are only available when using the app payment gateway.');
        }

        $availableBalance = Payment::where('company_id', $company->id)
            ->where('via_app_gateway', true)
            ->where('status', 'completed')
            ->whereNull('payout_id')
            ->sum('amount');

        if ($availableBalance <= 0) {
            return redirect()->route('payouts.index')->with('error', 'No available balance to request payout.');
        }

        $feePercentage = (float) \App\Models\SystemSetting::get('payout_fee_percentage', 0);
        $minimumAmount = (float) \App\Models\SystemSetting::get('payout_minimum_amount', 50);

        return view('payouts.create', compact('availableBalance', 'settings', 'feePercentage', 'minimumAmount'));
    }

    public function store(Request $request)
    {
        $company = auth()->user()->company;
        $settings = $company->settings;

        $availableBalance = Payment::where('company_id', $company->id)
            ->where('via_app_gateway', true)
            ->where('status', 'completed')
            ->whereNull('payout_id')
            ->sum('amount');

        // Get minimum payout amount from system settings
        $minimumAmount = (float) \App\Models\SystemSetting::get('payout_minimum_amount', 50);

        $validated = $request->validate([
            'amount' => 'required|numeric|min:' . $minimumAmount . '|max:' . $availableBalance,
            'payout_method' => 'required|in:bank_transfer,paypal',
            'bank_name' => 'required_if:payout_method,bank_transfer|nullable|string|max:255',
            'bank_account_name' => 'required_if:payout_method,bank_transfer|nullable|string|max:255',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_routing_number' => 'nullable|string|max:100',
            'bank_iban' => 'nullable|string|max:100',
            'bank_swift' => 'nullable|string|max:50',
            'paypal_email' => 'required_if:payout_method,paypal|nullable|email|max:255',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Calculate payout fee
        $feePercentage = (float) \App\Models\SystemSetting::get('payout_fee_percentage', 0);
        $grossAmount = (float) $validated['amount'];
        $feeAmount = round($grossAmount * ($feePercentage / 100), 2);
        $netAmount = $grossAmount - $feeAmount;

        $payout = Payout::create([
            'company_id' => $company->id,
            'payout_number' => Payout::generatePayoutNumber($company->id),
            'gross_amount' => $grossAmount,
            'fee_percentage' => $feePercentage,
            'fee_amount' => $feeAmount,
            'amount' => $netAmount, // Net amount tenant receives
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

        // Mark payments as included in this payout (up to the requested amount)
        $remainingAmount = $validated['amount'];
        $payments = Payment::where('company_id', $company->id)
            ->where('via_app_gateway', true)
            ->where('status', 'completed')
            ->whereNull('payout_id')
            ->orderBy('payment_date', 'asc')
            ->get();

        foreach ($payments as $payment) {
            if ($remainingAmount <= 0)
                break;

            $payment->update(['payout_id' => $payout->id]);
            $remainingAmount -= $payment->amount;
        }

        return redirect()->route('payouts.index')->with('success', 'Payout request submitted successfully. We will process it within 3-5 business days.');
    }

    public function show(Payout $payout)
    {
        $payout->load('payments.invoice', 'payments.client');
        return view('payouts.show', compact('payout'));
    }
}
