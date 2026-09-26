<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = Receipt::with(['client', 'invoice', 'payment'])->orderBy('issued_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('company_name', 'like', "%{$search}%"))
                    ->orWhereHas('invoice', fn ($q) => $q->where('invoice_number', 'like', "%{$search}%"));
            });
        }

        $receipts = $query->paginate(20)->withQueryString();

        return Inertia::render('Receipts/Index', [
            'receipts' => $receipts->through(fn (Receipt $r) => $this->row($r)),
            'filters' => $request->only('search'),
        ]);
    }

    public function show(Receipt $receipt)
    {
        $receipt->load(['client', 'invoice', 'payment']);

        return Inertia::render('Receipts/Show', [
            'receipt' => [
                ...$this->row($receipt),
                'notes' => $receipt->notes,
                'client_email' => $receipt->client?->email,
                'payment_id' => $receipt->payment_id,
                'payment_number' => $receipt->payment?->payment_number,
                'transaction_id' => $receipt->payment?->transaction_id,
            ],
        ]);
    }

    /** Receipt rendered as HTML with the same template as the PDF. */
    public function preview(Receipt $receipt)
    {
        $receipt->load(['client', 'invoice', 'payment', 'company']);

        return view('receipts.pdf', ['receipt' => $receipt, 'payment' => $receipt->payment]);
    }

    public function download(Receipt $receipt)
    {
        $receipt->load(['client', 'invoice', 'payment', 'company']);

        return Pdf::loadView('receipts.pdf', ['receipt' => $receipt, 'payment' => $receipt->payment])
            ->download('receipt-' . $receipt->receipt_number . '.pdf');
    }

    private function row(Receipt $r): array
    {
        return [
            'id' => $r->id,
            'receipt_number' => $r->receipt_number,
            'amount' => (float) $r->amount,
            'payment_method' => $r->payment_method,
            'payment_method_label' => Payment::paymentMethods()[$r->payment_method] ?? ucwords(str_replace('_', ' ', (string) $r->payment_method)),
            'issued_at' => $r->issued_at?->toIso8601String(),
            'client_name' => $r->client?->bill_to_name,
            'client_id' => $r->client_id,
            'invoice_id' => $r->invoice_id,
            'invoice_number' => $r->invoice?->invoice_number,
        ];
    }
}
