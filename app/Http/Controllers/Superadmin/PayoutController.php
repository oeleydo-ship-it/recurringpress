<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\Payout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PayoutController extends Controller
{
    public function index(Request $request)
    {
        $query = Payout::withoutGlobalScopes()->with('company');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payout_number', 'like', "%{$search}%")
                    ->orWhereHas('company', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $payouts = $query->orderBy('created_at', 'desc')->paginate(15);

        $stats = [
            'pending' => Payout::withoutGlobalScopes()->where('status', 'pending')->count(),
            'processing' => Payout::withoutGlobalScopes()->where('status', 'processing')->count(),
            'completed' => Payout::withoutGlobalScopes()->where('status', 'completed')->count(),
            'total_pending_amount' => Payout::withoutGlobalScopes()->whereIn('status', ['pending', 'processing'])->sum('amount'),
            'total_paid_amount' => Payout::withoutGlobalScopes()->where('status', 'completed')->sum('amount'),
            'total_fee_earned' => Payout::withoutGlobalScopes()->where('status', 'completed')->sum('fee_amount'),
        ];

        return view('superadmin.payouts.index', compact('payouts', 'stats'));
    }

    public function show(Payout $payout)
    {
        $payout = Payout::withoutGlobalScopes()->with(['company', 'payments.invoice', 'payments.client'])->findOrFail($payout->id);
        return view('superadmin.payouts.show', compact('payout'));
    }

    public function process(Request $request, Payout $payout)
    {
        $payout = Payout::withoutGlobalScopes()->findOrFail($payout->id);

        if ($payout->status !== Payout::STATUS_PENDING) {
            return back()->with('error', 'This payout cannot be processed.');
        }

        $payout->update([
            'status' => Payout::STATUS_PROCESSING,
        ]);

        return back()->with('success', 'Payout marked as processing.');
    }

    public function complete(Request $request, Payout $payout)
    {
        $payout = Payout::withoutGlobalScopes()->findOrFail($payout->id);

        if (!in_array($payout->status, [Payout::STATUS_PENDING, Payout::STATUS_PROCESSING])) {
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

        return redirect()->route('superadmin.payouts.index')->with('success', 'Payout completed successfully.');
    }

    public function reject(Request $request, Payout $payout)
    {
        $payout = Payout::withoutGlobalScopes()->findOrFail($payout->id);

        if (!in_array($payout->status, [Payout::STATUS_PENDING, Payout::STATUS_PROCESSING])) {
            return back()->with('error', 'This payout cannot be rejected.');
        }

        $validated = $request->validate([
            'admin_notes' => 'required|string|max:1000',
        ]);

        // Release payments back to available balance
        $payout->payments()->update(['payout_id' => null]);

        $payout->update([
            'status' => Payout::STATUS_FAILED,
            'admin_notes' => $validated['admin_notes'],
            'processed_at' => now(),
            'processed_by' => Auth::guard('superadmin')->id(),
        ]);

        return redirect()->route('superadmin.payouts.index')->with('success', 'Payout rejected and payments released.');
    }
}
