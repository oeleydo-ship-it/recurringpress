<?php

namespace App\Http\Controllers;

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Client;
use App\Models\Product;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $query = Quotation::with(['client', 'user'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quotation_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $quotations = $query->paginate(15)->withQueryString();

        return view('quotations.index', compact('quotations'));
    }

    public function export(Request $request)
    {
        $query = Quotation::with('client')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quotation_number', 'like', "%{$search}%")
                    ->orWhereHas('client', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        $quotations = $query->get();

        $filename = 'quotations-' . now()->format('Y-m-d-His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($quotations) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Quotation Number', 'Client', 'Issue Date', 'Valid Until', 'Total', 'Status']);

            foreach ($quotations as $quotation) {
                fputcsv($handle, [
                    $quotation->quotation_number,
                    $quotation->client->name ?? 'N/A',
                    optional($quotation->issue_date)->format('Y-m-d'),
                    optional($quotation->valid_until)->format('Y-m-d'),
                    number_format($quotation->total, 2),
                    ucfirst($quotation->status),
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
        $settings = $company->settings;

        $nextNumber = $company->getNextQuotationNumber();
        $validityDays = $company->quotation_validity_days ?? 30;

        return view('quotations.create', compact('clients', 'products', 'nextNumber', 'settings', 'validityDays'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'quotation_number' => [
                'required',
                'string',
                \Illuminate\Validation\Rule::unique('quotations')->where(function ($query) {
                    return $query->where('company_id', auth()->user()->company_id);
                }),
            ],
            'issue_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:issue_date',
            'reference' => 'nullable|string|max:255',
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
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        $company = auth()->user()->company;

        // The quotation is meaningless without its items and computed totals, so create
        // them together — a failure part-way through would otherwise leave a zero-total
        // quotation behind.
        $quotation = DB::transaction(function () use ($validated, $request, $company) {
            $quotation = Quotation::create([
                'company_id' => $company->id,
                'client_id' => $validated['client_id'],
                'user_id' => auth()->id(),
                'quotation_number' => $validated['quotation_number'],
                'reference' => $validated['reference'] ?? null,
                'issue_date' => $validated['issue_date'],
                'valid_until' => $validated['valid_until'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'discount_type' => $validated['discount_type'] ?? 'fixed',
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'status' => Quotation::STATUS_DRAFT,
            ]);

            $this->syncItems($quotation, $request->items);

            $quotation->calculateTotals();

            return $quotation;
        });

        return redirect()->route('quotations.show', $quotation)
            ->with('success', 'Quotation created successfully.');
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['client', 'items', 'user', 'company']);
        return view('quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        if (!$quotation->isEditable()) {
            return redirect()->route('quotations.show', $quotation)
                ->with('error', 'Cannot edit a converted quotation.');
        }

        $clients = Client::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $quotation->load('items');

        return view('quotations.edit', compact('quotation', 'clients', 'products'));
    }

    public function update(Request $request, Quotation $quotation)
    {
        if (!$quotation->isEditable()) {
            return redirect()->route('quotations.show', $quotation)
                ->with('error', 'Cannot update a converted quotation.');
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'issue_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:issue_date',
            'reference' => 'nullable|string|max:255',
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
            'items.*.name' => 'required|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ]);

        // Items are replaced wholesale, so the delete and the re-insert must share a
        // transaction — a failure between them would strip the quotation of its items.
        DB::transaction(function () use ($quotation, $validated, $request) {
            $quotation->update([
                'client_id' => $validated['client_id'],
                'issue_date' => $validated['issue_date'],
                'valid_until' => $validated['valid_until'],
                'reference' => $validated['reference'] ?? null,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'discount_type' => $validated['discount_type'] ?? 'fixed',
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
            ]);

            $quotation->items()->delete();
            $quotation->unsetRelation('items');

            $this->syncItems($quotation, $request->items);

            $quotation->calculateTotals();
        });

        return redirect()->route('quotations.show', $quotation)
            ->with('success', 'Quotation updated successfully.');
    }

    /**
     * Persist the submitted line items against a quotation.
     */
    protected function syncItems(Quotation $quotation, array $items): void
    {
        foreach ($items as $item) {
            QuotationItem::create([
                'quotation_id' => $quotation->id,
                'product_id' => $item['product_id'] ?? null,
                'name' => $item['name'],
                'description' => $item['description'] ?? null,
                'quantity' => $item['quantity'],
                'unit' => $item['unit'] ?? 'unit',
                'price' => $item['price'],
                'tax_rate' => $item['tax_rate'] ?? 0,
            ]);
        }

        $quotation->unsetRelation('items');
    }

    public function destroy(Quotation $quotation)
    {
        if (!$quotation->isEditable()) {
            return redirect()->route('quotations.index')
                ->with('error', 'Cannot delete a converted quotation.');
        }

        $quotation->delete();

        return redirect()->route('quotations.index')
            ->with('success', 'Quotation deleted successfully.');
    }

    public function send(Quotation $quotation)
    {
        if (!$quotation->canTransitionTo(Quotation::STATUS_SENT)) {
            return back()->with('error', "A {$quotation->status} quotation cannot be marked as sent.");
        }

        $quotation->update([
            'status' => Quotation::STATUS_SENT,
            'sent_at' => $quotation->sent_at ?? now(),
        ]);

        return back()->with('success', 'Quotation marked as sent.');
    }

    public function email(Quotation $quotation)
    {
        $quotation->load(['client', 'items', 'company']);

        if (!$quotation->client || !$quotation->client->email) {
            return back()->with('error', 'Client email address is missing.');
        }

        $template = $quotation->company->settings->invoice_template ?? 'classic';

        $quotationTemplate = "quotations.pdf.{$template}";
        $fallbackTemplate = 'quotations.pdf.classic';
        $viewPath = view()->exists($quotationTemplate) ? $quotationTemplate : $fallbackTemplate;

        $pdf = Pdf::loadView($viewPath, compact('quotation'));

        try {
            Mail::send('emails.quotation', ['quotation' => $quotation], function ($message) use ($quotation, $pdf) {
                $message->to($quotation->client->email);
                $message->subject("Quotation {$quotation->quotation_number}");
                $message->attachData($pdf->output(), "quotation-{$quotation->quotation_number}.pdf");
            });
        } catch (\Exception $e) {
            \Log::error('Quotation email failed: ' . $e->getMessage());
            return back()->with('error', 'Failed to send email. Please check your mail configuration.');
        }

        $quotation->update([
            'sent_at' => $quotation->sent_at ?? now(),
            'status' => $quotation->canTransitionTo(Quotation::STATUS_SENT)
                ? Quotation::STATUS_SENT
                : $quotation->status,
        ]);

        return back()->with('success', 'Quotation emailed to client successfully.');
    }

    public function accept(Quotation $quotation)
    {
        if (!$quotation->canTransitionTo(Quotation::STATUS_ACCEPTED)) {
            return back()->with('error', "A {$quotation->status} quotation cannot be marked as accepted.");
        }

        $quotation->update([
            'status' => Quotation::STATUS_ACCEPTED,
            'responded_at' => $quotation->responded_at ?? now(),
        ]);

        return back()->with('success', 'Quotation marked as accepted.');
    }

    public function reject(Quotation $quotation)
    {
        if (!$quotation->canTransitionTo(Quotation::STATUS_REJECTED)) {
            return back()->with('error', "A {$quotation->status} quotation cannot be marked as rejected.");
        }

        $quotation->update([
            'status' => Quotation::STATUS_REJECTED,
            'responded_at' => $quotation->responded_at ?? now(),
        ]);

        return back()->with('success', 'Quotation marked as rejected.');
    }

    public function convert(Request $request, Quotation $quotation)
    {
        if ($request->isMethod('get')) {
            return redirect()->route('quotations.show', $quotation);
        }
        if (!$quotation->canConvert()) {
            return back()->with('error', 'This quotation cannot be converted to an invoice.');
        }

        $company = auth()->user()->company;
        $invoiceNumber = $company->getNextInvoiceNumber();

        $invoice = Invoice::create([
            'company_id' => $company->id,
            'client_id' => $quotation->client_id,
            'created_by' => auth()->id(),
            'invoice_number' => $invoiceNumber,
            'reference' => $quotation->reference,
            'issue_date' => now(),
            'due_date' => now()->addDays($company->payment_terms ?? 30),
            'discount_amount' => $quotation->discount_amount,
            'discount_type' => $quotation->discount_type,
            'notes' => $quotation->notes,
            'terms' => $quotation->terms,
            'status' => 'draft',
        ]);

        foreach ($quotation->items as $item) {
            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'product_id' => $item->product_id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit' => $item->unit,
                'price' => $item->price,
                'tax_rate' => $item->tax_rate,
            ]);
        }

        $invoice->calculateTotals();
        $invoice->save();

        $quotation->update([
            'status' => 'converted',
            'converted_invoice_id' => $invoice->id,
        ]);

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Quotation converted to invoice successfully.');
    }

    public function download(Quotation $quotation)
    {
        $quotation->load(['client', 'items', 'company']);
        $template = $quotation->company->settings->invoice_template ?? 'classic';

        // Quotation templates take a $quotation variable, not $invoice, so fall back to
        // a quotation-specific template (classic) rather than the invoice template of the
        // same name if this template has no quotation version.
        $quotationTemplate = "quotations.pdf.{$template}";
        $fallbackTemplate = 'quotations.pdf.classic';

        $viewPath = view()->exists($quotationTemplate) ? $quotationTemplate : $fallbackTemplate;

        $pdf = Pdf::loadView($viewPath, compact('quotation'));

        return $pdf->download("quotation-{$quotation->quotation_number}.pdf");
    }

    public function publicView(string $token)
    {
        $quotation = Quotation::withoutGlobalScopes()
            ->where('share_token', $token)
            ->with(['client', 'items', 'company'])
            ->firstOrFail();

        return view('quotations.public', compact('quotation'));
    }

    public function duplicate(Quotation $quotation)
    {
        $company = auth()->user()->company;
        $newNumber = $company->getNextQuotationNumber();

        $newQuotation = $quotation->replicate();
        $newQuotation->quotation_number = $newNumber;
        $newQuotation->status = 'draft';
        $newQuotation->issue_date = now();
        $newQuotation->valid_until = now()->addDays($quotation->company->quotation_validity_days ?? 30);
        $newQuotation->share_token = Str::random(64);
        $newQuotation->converted_invoice_id = null;
        $newQuotation->save();

        foreach ($quotation->items as $item) {
            $newItem = $item->replicate();
            $newItem->quotation_id = $newQuotation->id;
            $newItem->save();
        }

        return redirect()->route('quotations.edit', $newQuotation)
            ->with('success', 'Quotation duplicated successfully.');
    }
}
