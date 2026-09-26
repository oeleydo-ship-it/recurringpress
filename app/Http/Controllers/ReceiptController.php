<?php

namespace App\Http\Controllers;

use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    public function index(Request $request)
    {
        $query = Receipt::with(['client', 'invoice', 'payment'])
            ->orderBy('issued_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('invoice', function ($q) use ($search) {
                        $q->where('invoice_number', 'like', "%{$search}%");
                    });
            });
        }

        $receipts = $query->paginate(15);

        return view('receipts.index', compact('receipts'));
    }

    public function show(Receipt $receipt)
    {
        $receipt->load(['client', 'invoice', 'payment', 'company']);
        return view('receipts.show', compact('receipt'));
    }

    public function download(Receipt $receipt)
    {
        $receipt->load(['client', 'invoice', 'payment', 'company']);
        
        $pdf = Pdf::loadView('receipts.pdf', compact('receipt'));
        
        return $pdf->download('receipt-' . $receipt->receipt_number . '.pdf');
    }
}
