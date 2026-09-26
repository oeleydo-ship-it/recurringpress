<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * Payout administration. Payouts are loaded by id without tenant scopes: route model
 * binding applies the tenant scope, which broke these pages whenever the superadmin was
 * also signed in as a tenant user in the same browser.
 */
class PayoutController extends Controller
{
    public function index(Request $request)
    {
        $query = Payout::withoutGlobalScopes()->with('company:id,name,email');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payout_number', 'like', "%{$search}%")
                    ->orWhereHas('company', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $payouts = $query->orderByRaw("FIELD(status, 'processing', 'pending') DESC")->orderBy('created_at', 'desc')->paginate(20)->withQueryString();
        $base = fn () => Payout::withoutGlobalScopes();

        return Inertia::render('Superadmin/Payouts/Index', [
            'payouts' => $payouts->through(fn (Payout $p) => [
                'id' => $p->id,
                'payout_number' => $p->payout_number,
                'company' => $p->company?->name,
                'amount' => (float) $p->amount,
                'fee_amount' => (float) $p->fee_amount,
                'currency' => $p->currency,
                'status' => $p->status,
                'payout_method' => $p->payout_method,
                'requested_at' => ($p->requested_at ?? $p->created_at)?->toIso8601String(),
            ]),
            'filters' => $request->only('search', 'status'),
            'stats' => [
                'pending' => $base()->where('status', 'pending')->count(),
                'processing' => $base()->where('status', 'processing')->count(),
                'pending_amount' => (float) $base()->whereIn('status', ['pending', 'processing'])->sum('amount'),
                'paid_amount' => (float) $base()->where('status', 'completed')->sum('amount'),
                'fees_earned' => (float) $base()->where('status', 'completed')->sum('fee_amount'),
            ],
        ]);
    }

    public function show($payout)
    {
        $payout = $this->find($payout)->load('company:id,name,email,phone');
        $payments = Payment::withoutGlobalScopes()->where('payout_id', $payout->id)
            ->with(['invoice' => fn ($i) => $i->withoutGlobalScopes(), 'client' => fn ($c) => $c->withoutGlobalScopes()])
            ->orderBy('payment_date')->get();

        return Inertia::render('Superadmin/Payouts/Show', [
            'payout' => [
                'id' => $payout->id,
                'payout_number' => $payout->payout_number,
                'status' => $payout->status,
                'company' => $payout->company?->only('id', 'name', 'email', 'phone'),
                'gross_amount' => (float) $payout->gross_amount,
                'fee_percentage' => (float) $payout->fee_percentage,
                'fee_amount' => (float) $payout->fee_amount,
                'amount' => (float) $payout->amount,
                'currency' => $payout->currency,
                'payout_method' => $payout->payout_method,
                // Full bank details are needed here to actually send the money.
                'bank' => array_filter($payout->only('bank_name', 'bank_account_name', 'bank_account_number', 'bank_routing_number', 'bank_iban', 'bank_swift')),
                'paypal_email' => $payout->paypal_email,
                'notes' => $payout->notes,
                'admin_notes' => $payout->admin_notes,
                'transaction_id' => $payout->transaction_id,
                'requested_at' => ($payout->requested_at ?? $payout->created_at)?->toIso8601String(),
                'processed_at' => $payout->processed_at?->toIso8601String(),
            ],
            'payments' => $payments->map(fn (Payment $p) => [
                'id' => $p->id,
                'payment_number' => $p->payment_number,
                'amount' => (float) $p->amount - (float) $p->refunded_amount,
                'payment_date' => $p->payment_date?->toIso8601String(),
                'invoice_number' => $p->invoice?->invoice_number,
                'client' => $p->client?->bill_to_name,
            ]),
        ]);
    }

    public function process($payout)
    {
        $payout = $this->find($payout);

        if ($payout->status !== Payout::STATUS_PENDING) {
            return back()->with('error', 'This payout cannot be processed.');
        }

        $payout->update(['status' => Payout::STATUS_PROCESSING]);

        return back()->with('success', 'Payout marked as processing.');
    }

    public function complete(Request $request, $payout)
    {
        $payout = $this->find($payout);

        if (! in_array($payout->status, [Payout::STATUS_PENDING, Payout::STATUS_PROCESSING], true)) {
            return back()->with('error', 'This payout cannot be completed.');
        }

        $validated = $request->validate([
            'transaction_id' => 'nullable|string|max:255',
            'admin_notes' => 'nullable|string|max:1000',
        ]);

        $payout->update([
            'status' => Payout::STATUS_COMPLETED,
            'transaction_id' => $validated['transaction_id'] ?? null,
            'admin_notes' => $validated['admin_notes'] ?? null,
            'processed_at' => now(),
            'processed_by' => Auth::guard('superadmin')->id(),
        ]);

        return redirect()->route('superadmin.payouts.index')->with('success', 'Payout completed.');
    }

    public function reject(Request $request, $payout)
    {
        $payout = $this->find($payout);

        if (! in_array($payout->status, [Payout::STATUS_PENDING, Payout::STATUS_PROCESSING], true)) {
            return back()->with('error', 'This payout cannot be rejected.');
        }

        $validated = $request->validate(['admin_notes' => 'required|string|max:1000']);

        DB::transaction(function () use ($payout, $validated) {
            // Release payments back to the tenant's available balance.
            Payment::withoutGlobalScopes()->where('payout_id', $payout->id)->update(['payout_id' => null]);

            $payout->update([
                'status' => Payout::STATUS_FAILED,
                'admin_notes' => $validated['admin_notes'],
                'processed_at' => now(),
                'processed_by' => Auth::guard('superadmin')->id(),
            ]);
        });

        return redirect()->route('superadmin.payouts.index')->with('success', 'Payout rejected and payments released.');
    }

    private function find($id): Payout
    {
        return Payout::withoutGlobalScopes()->findOrFail((int) (is_object($id) ? $id->getKey() : $id));
    }
}
