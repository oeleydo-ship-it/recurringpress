<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('client');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            if ($request->payment_status === 'paid') {
                $query->where('status', Invoice::STATUS_PAID);
            } elseif ($request->payment_status === 'unpaid') {
                $query->where('status', '!=', Invoice::STATUS_PAID);
            }
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('date_from')) {
            $query->where('issue_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('issue_date', '<=', $request->date_to);
        }

        $invoices = $query->latest()->paginate(15)->withQueryString();
        $clients = Client::orderBy('name')->get();
        $statuses = Invoice::statuses();

        return view('invoices.index', compact('invoices', 'clients', 'statuses'));
    }

    public function export(Request $request)
    {
        $query = Invoice::with('client');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('payment_status')) {
            if ($request->payment_status === 'paid') {
                $query->where('status', Invoice::STATUS_PAID);
            } elseif ($request->payment_status === 'unpaid') {
                $query->where('status', '!=', Invoice::STATUS_PAID);
            }
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('date_from')) {
            $query->where('issue_date', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('issue_date', '<=', $request->date_to);
        }

        $invoices = $query->latest()->get();

        $filename = 'invoices-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($invoices) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Invoice Number', 'Client', 'Issue Date', 'Due Date', 'Total', 'Paid Amount', 'Balance Due', 'Payment Status', 'Status']);

            foreach ($invoices as $invoice) {
                fputcsv($handle, [
                    $invoice->invoice_number,
                    $invoice->client->name ?? 'N/A',
                    optional($invoice->issue_date)->format('Y-m-d'),
                    optional($invoice->due_date)->format('Y-m-d'),
                    number_format($invoice->total, 2),
                    number_format($invoice->paid_amount, 2),
                    number_format(max(0, $invoice->total - $invoice->paid_amount), 2),
                    $invoice->isFullyPaid() ? 'Paid' : 'Unpaid',
                    ucfirst($invoice->status),
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function create()
    {
        $clients = Client::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $company = auth()->user()->company;
        $nextNumber = $company->getNextInvoiceNumber();
        $settings = $company->settings;

        return view('invoices.create', compact('clients', 'products', 'nextNumber', 'settings'));
    }

    public function store(Request $request)
    {
        $company = auth()->user()->company;
        if ($company->hasReachedLimit('invoices')) {
            return back()->with('error', 'You have reached the invoice limit for your current plan. Please upgrade to create more invoices.');
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'invoice_number' => [
                'required',
                'string',
                \Illuminate\Validation\Rule::unique('invoices')->where(function ($query) {
                    return $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'reference' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'discount_amount' => [
                'nullable', 'numeric', 'min:0',
                \Illuminate\Validation\Rule::when(
                    fn () => $request->input('discount_type') === 'percentage',
                    ['max:100']
                ),
            ],
            'discount_type' => 'in:fixed,percentage',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.unit' => 'nullable|string|max:50',
        ]);

        $invoice = DB::transaction(function () use ($validated, $request) {
            $invoice = Invoice::create([
                'company_id' => auth()->user()->company_id,
                'client_id' => $validated['client_id'],
                'created_by' => auth()->id(),
                'invoice_number' => $validated['invoice_number'],
                'reference' => $validated['reference'] ?? null,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'discount_type' => $validated['discount_type'] ?? 'fixed',
                'status' => 'draft',
            ]);

            foreach ($validated['items'] as $index => $item) {
                $invoice->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'unit' => $item['unit'] ?? 'unit',
                    'sort_order' => $index,
                ]);
            }

            $invoice->calculateTotals();
            $invoice->save();

            return $invoice;
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'items', 'creator', 'company']);

        if ($invoice->items->count() > 0 && ((float) $invoice->total) === 0.0) {
            $invoice->calculateTotals();
            $invoice->save();
        }

        return view('invoices.show', compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Cannot edit a paid invoice.');
        }

        $invoice->load('items');
        $clients = Client::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();

        return view('invoices.edit', compact('invoice', 'clients', 'products'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Cannot edit a paid invoice.');
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'reference' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'discount_amount' => [
                'nullable', 'numeric', 'min:0',
                \Illuminate\Validation\Rule::when(
                    fn () => $request->input('discount_type') === 'percentage',
                    ['max:100']
                ),
            ],
            'discount_type' => 'in:fixed,percentage',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.unit' => 'nullable|string|max:50',
        ]);

        DB::transaction(function () use ($invoice, $validated) {
            $invoice->update([
                'client_id' => $validated['client_id'],
                'reference' => $validated['reference'] ?? null,
                'issue_date' => $validated['issue_date'],
                'due_date' => $validated['due_date'],
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'discount_type' => $validated['discount_type'] ?? 'fixed',
            ]);

            $invoice->items()->delete();

            foreach ($validated['items'] as $index => $item) {
                $invoice->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'name' => $item['name'],
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'unit' => $item['unit'] ?? 'unit',
                    'sort_order' => $index,
                ]);
            }

            $invoice->calculateTotals();
            $invoice->save();
        });

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice updated successfully.');
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Cannot delete a paid invoice.');
        }

        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('success', 'Invoice deleted successfully.');
    }

    public function send(Invoice $invoice)
    {
        $invoice->markAsSent();

        return back()->with('success', 'Invoice marked as sent.');
    }

    public function email(Invoice $invoice)
    {
        $invoice->load(['client', 'items', 'company']);

        if (!$invoice->client || !$invoice->client->email) {
            return back()->with('error', 'Client email address is missing.');
        }

        $template = $invoice->company->settings->invoice_template ?? 'classic';

        $invoiceTemplate = "invoices.pdf.{$template}";
        $fallbackTemplate = 'invoices.pdf.classic';
        $viewPath = view()->exists($invoiceTemplate) ? $invoiceTemplate : $fallbackTemplate;

        $pdf = Pdf::loadView($viewPath, compact('invoice'));

        try {
            Mail::send('emails.invoice', ['invoice' => $invoice], function ($message) use ($invoice, $pdf) {
                $message->to($invoice->client->email);
                $message->subject("Invoice {$invoice->invoice_number}");
                $message->attachData($pdf->output(), "invoice-{$invoice->invoice_number}.pdf");
            });
        } catch (\Exception $e) {
            \Log::error('Invoice email failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to send email. Please check your mail configuration.');
        }

        if ($invoice->status === 'draft') {
            $invoice->markAsSent();
        }

        return back()->with('success', 'Invoice emailed to client successfully.');
    }

    public function markPaid(Request $request, Invoice $invoice)
    {
        if ($invoice->status === Invoice::STATUS_PAID) {
            return back()->with('info', 'Invoice is already fully paid.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->remaining_amount,
            'payment_method' => 'required|in:manual,cash,bank_transfer,credit_card,paypal,stripe,other',
            'notes' => 'nullable|string|max:1000',
        ]);

        $paymentAmount = $request->input('amount');
        $paymentMethod = $request->input('payment_method');
        $notes = $request->input('notes', 'Payment recorded manually');

        DB::transaction(function () use ($invoice, $paymentAmount, $paymentMethod, $notes) {
            // Create Payment Record
            $paymentNumber = $invoice->company->getNextPaymentNumber();

            $payment = \App\Models\Payment::create([
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'payment_number' => $paymentNumber,
                'payment_method' => $paymentMethod,
                'amount' => $paymentAmount,
                'currency' => $invoice->company->currency,
                'status' => 'completed',
                'notes' => $notes,
                'payment_date' => now(),
            ]);

            // Create Receipt
            \App\Models\Receipt::create([
                'company_id' => $payment->company_id,
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'client_id' => $payment->client_id,
                'receipt_number' => \App\Models\Receipt::generateReceiptNumber($payment->company_id),
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'issued_at' => now(),
            ]);

            // Update invoice paid amount and status
            $invoice->addPayment($paymentAmount);
        });

        $message = $invoice->status === Invoice::STATUS_PAID
            ? 'Payment recorded and invoice marked as fully paid.'
            : 'Partial payment recorded. Remaining: ' . $invoice->company->currency_symbol . number_format($invoice->remaining_amount, 2);

        return back()->with('success', $message);
    }

    public function download(Invoice $invoice)
    {
        $invoice->load(['client', 'items', 'company']);
        $template = $invoice->company->settings->invoice_template ?? 'classic';

        $pdf = Pdf::loadView("invoices.pdf.{$template}", compact('invoice'));

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    public function publicView(string $token)
    {
        $invoice = Invoice::where('share_token', $token)
            ->withoutGlobalScope('company')
            ->with(['client', 'items', 'company'])
            ->firstOrFail();

        return view('invoices.public', compact('invoice'));
    }

    public function duplicate(Invoice $invoice)
    {
        $newInvoice = DB::transaction(function () use ($invoice) {
            $company = auth()->user()->company;

            $newInvoice = $invoice->replicate([
                'invoice_number',
                'share_token',
                'status',
                'sent_at',
                'paid_at'
            ]);

            $newInvoice->invoice_number = $company->getNextInvoiceNumber();
            $newInvoice->issue_date = now();
            $newInvoice->due_date = now()->addDays($company->payment_terms);
            $newInvoice->status = 'draft';
            $newInvoice->created_by = auth()->id();
            $newInvoice->save();

            foreach ($invoice->items as $item) {
                $newItem = $item->replicate();
                $newItem->invoice_id = $newInvoice->id;
                $newItem->save();
            }

            return $newInvoice;
        });

        return redirect()->route('invoices.edit', $newInvoice)
            ->with('success', 'Invoice duplicated successfully.');
    }
}
