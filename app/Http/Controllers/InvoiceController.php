<?php

namespace App\Http\Controllers;

use App\Jobs\SendDocumentEmail;
use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Receipt;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = $this->filtered($request)->with('client')->latest('issue_date')->latest('id')
            ->paginate(15)->withQueryString();

        $counts = Invoice::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status');

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices->through(fn (Invoice $i) => $this->listRow($i)),
            'filters' => $request->only('search', 'status', 'client_id', 'date_from', 'date_to'),
            'statuses' => Invoice::statuses(),
            'counts' => $counts,
            'clients' => Client::orderBy('name')->get(['id', 'name', 'company_name', 'client_type'])
                ->map(fn ($c) => ['id' => $c->id, 'label' => $c->bill_to_name]),
            'summary' => [
                'outstanding' => (float) Invoice::whereIn('status', ['sent', 'viewed', 'partial', 'overdue'])->sum(DB::raw('total - COALESCE(paid_amount, 0)')),
                'overdue' => (float) Invoice::where('status', 'overdue')->sum(DB::raw('total - COALESCE(paid_amount, 0)')),
                'draft' => (int) ($counts['draft'] ?? 0),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $invoices = $this->filtered($request)->with('client')->latest()->get();

        $filename = 'invoices-' . now()->format('Y-m-d-His') . '.csv';

        return response()->stream(function () use ($invoices) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Invoice Number', 'Client', 'Issue Date', 'Due Date', 'Total', 'Paid Amount', 'Balance Due', 'Payment Status', 'Status']);

            foreach ($invoices as $invoice) {
                fputcsv($handle, array_map([$this, 'csvSafe'], [
                    $invoice->invoice_number,
                    $invoice->client?->bill_to_name ?? 'N/A',
                    optional($invoice->issue_date)->format('Y-m-d'),
                    optional($invoice->due_date)->format('Y-m-d'),
                    number_format($invoice->total, 2, '.', ''),
                    number_format($invoice->paid_amount, 2, '.', ''),
                    number_format(max(0, $invoice->total - $invoice->paid_amount), 2, '.', ''),
                    $invoice->isFullyPaid() ? 'Paid' : 'Unpaid',
                    ucfirst($invoice->status),
                ]));
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function create(Request $request)
    {
        $company = auth()->user()->company;

        return Inertia::render('Invoices/Form', [
            'invoice' => null,
            'nextNumber' => $company->getNextInvoiceNumber(),
            'selectedClientId' => $request->integer('client_id') ?: null,
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $company = auth()->user()->company;
        if ($company->hasReachedLimit('invoices')) {
            return back()->with('error', 'You have reached the invoice limit for your current plan. Please upgrade to create more invoices.');
        }

        $validated = $this->validateInvoice($request);

        $invoice = DB::transaction(function () use ($validated) {
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

            $this->syncItems($invoice, $validated['items']);

            return $invoice;
        });

        if ($request->boolean('send_now')) {
            return $this->queueEmail($invoice, 'Invoice created and queued for delivery.');
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Invoice created successfully.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'items', 'creator', 'company', 'payments' => fn ($q) => $q->latest('payment_date')]);

        if ($invoice->items->count() > 0 && ((float) $invoice->total) === 0.0) {
            $invoice->calculateTotals();
            $invoice->save();
        }

        $receipts = Receipt::where('invoice_id', $invoice->id)->pluck('id', 'payment_id');

        return Inertia::render('Invoices/Show', [
            'invoice' => [
                ...$this->listRow($invoice),
                'reference' => $invoice->reference,
                'subtotal' => (float) $invoice->subtotal,
                'tax_amount' => (float) $invoice->tax_amount,
                'discount_amount' => (float) $invoice->discount_amount,
                'discount_type' => $invoice->discount_type,
                'notes' => $invoice->notes,
                'terms' => $invoice->terms,
                'sent_at' => $invoice->sent_at?->toIso8601String(),
                'paid_at' => $invoice->paid_at?->toIso8601String(),
                'created_at' => $invoice->created_at?->toIso8601String(),
                'created_by' => $invoice->creator?->name,
                'share_url' => $invoice->share_url,
                'is_recurring' => $invoice->isFromRecurring(),
                'recurring_invoice_id' => $invoice->recurring_invoice_id,
                'client' => $invoice->client ? [
                    'id' => $invoice->client->id,
                    'name' => $invoice->client->bill_to_name,
                    'email' => $invoice->client->email,
                ] : null,
                'items' => $invoice->items->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'description' => $i->description,
                    'quantity' => (float) $i->quantity,
                    'unit' => $i->unit,
                    'price' => (float) $i->price,
                    'tax_rate' => (float) $i->tax_rate,
                ]),
            ],
            'payments' => $invoice->payments->map(fn (Payment $p) => [
                'id' => $p->id,
                'payment_number' => $p->payment_number,
                'amount' => (float) $p->amount,
                'payment_method' => $p->payment_method,
                'status' => $p->status,
                'payment_date' => $p->payment_date?->toIso8601String(),
                'refunded_amount' => (float) $p->refunded_amount,
                'notes' => $p->notes,
                'receipt_id' => $receipts[$p->id] ?? null,
            ]),
            'paymentMethods' => $this->manualPaymentMethods(),
        ]);
    }

    /**
     * The invoice rendered with the company's chosen template as HTML — an exact preview of
     * the PDF / client copy, embedded in the invoice page.
     */
    public function preview(Invoice $invoice)
    {
        $invoice->load(['client', 'items', 'company']);

        return view($this->templateView($invoice), compact('invoice'));
    }

    public function edit(Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Cannot edit a paid invoice.');
        }

        $invoice->load('items');

        return Inertia::render('Invoices/Form', [
            'invoice' => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'client_id' => $invoice->client_id,
                'reference' => $invoice->reference,
                'issue_date' => $invoice->issue_date?->toDateString(),
                'due_date' => $invoice->due_date?->toDateString(),
                'notes' => $invoice->notes,
                'terms' => $invoice->terms,
                'discount_amount' => (float) $invoice->discount_amount,
                'discount_type' => $invoice->discount_type ?? 'fixed',
                'status' => $invoice->status,
                'items' => $invoice->items->map(fn ($i) => [
                    'product_id' => $i->product_id,
                    'name' => $i->name,
                    'description' => $i->description,
                    'quantity' => (float) $i->quantity,
                    'unit' => $i->unit,
                    'price' => (float) $i->price,
                    'tax_rate' => (float) $i->tax_rate,
                ]),
            ],
            'nextNumber' => $invoice->invoice_number,
            'selectedClientId' => $invoice->client_id,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Invoice $invoice)
    {
        if ($invoice->status === 'paid') {
            return back()->with('error', 'Cannot edit a paid invoice.');
        }

        $validated = $this->validateInvoice($request, $invoice);

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
            $invoice->unsetRelation('items');
            $this->syncItems($invoice, $validated['items']);
        });

        if ($request->boolean('send_now')) {
            return $this->queueEmail($invoice, 'Invoice saved and queued for delivery.');
        }

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
        $invoice->load('client');

        if (! $invoice->client || ! $invoice->client->email) {
            return back()->with('error', 'Client email address is missing.');
        }

        return $this->queueEmail($invoice, "Invoice queued for delivery to {$invoice->client->email}.");
    }

    public function markPaid(Request $request, Invoice $invoice)
    {
        if ($invoice->status === Invoice::STATUS_PAID) {
            return back()->with('info', 'Invoice is already fully paid.');
        }

        $request->validate([
            'amount' => 'required|numeric|min:0.01|max:' . $invoice->remaining_amount,
            'payment_method' => 'required|in:' . implode(',', array_keys($this->manualPaymentMethods())),
            'payment_date' => 'nullable|date|before_or_equal:today',
            'notes' => 'nullable|string|max:1000',
        ]);

        $paymentAmount = (float) $request->input('amount');
        $paymentMethod = $request->input('payment_method');
        $notes = $request->input('notes') ?: 'Payment recorded manually';
        $paymentDate = $request->date('payment_date') ?? now();

        DB::transaction(function () use ($invoice, $paymentAmount, $paymentMethod, $notes, $paymentDate) {
            $payment = Payment::create([
                'company_id' => $invoice->company_id,
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'payment_number' => $invoice->company->getNextPaymentNumber(),
                'payment_method' => $paymentMethod,
                'amount' => $paymentAmount,
                'currency' => $invoice->company->currency,
                'status' => 'completed',
                'notes' => $notes,
                'payment_date' => $paymentDate,
            ]);

            Receipt::create([
                'company_id' => $payment->company_id,
                'payment_id' => $payment->id,
                'invoice_id' => $payment->invoice_id,
                'client_id' => $payment->client_id,
                'receipt_number' => Receipt::generateReceiptNumber($payment->company_id),
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'issued_at' => now(),
            ]);

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

        return Pdf::loadView($this->templateView($invoice), compact('invoice'))
            ->download("invoice-{$invoice->invoice_number}.pdf");
    }

    public function duplicate(Invoice $invoice)
    {
        $newInvoice = DB::transaction(function () use ($invoice) {
            $company = auth()->user()->company;

            $newInvoice = $invoice->replicate(['invoice_number', 'share_token', 'status', 'sent_at', 'paid_at', 'paid_amount', 'recurring_invoice_id']);
            $newInvoice->invoice_number = $company->getNextInvoiceNumber();
            $newInvoice->issue_date = now();
            $newInvoice->due_date = now()->addDays((int) ($company->payment_terms ?: 30));
            $newInvoice->status = 'draft';
            // A copy starts unpaid (previously paid_amount was copied from the original).
            $newInvoice->paid_amount = 0;
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
            ->with('success', 'Invoice duplicated — review and save.');
    }

    /* ------------------------------------------------------------------ */

    private function filtered(Request $request): Builder
    {
        $query = Invoice::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")->orWhere('company_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $request->status === 'unpaid'
                ? $query->whereIn('status', ['sent', 'viewed', 'partial', 'overdue'])
                : $query->where('status', $request->status);
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

        return $query;
    }

    private function listRow(Invoice $i): array
    {
        return [
            'id' => $i->id,
            'invoice_number' => $i->invoice_number,
            'client_name' => $i->client?->bill_to_name,
            'client_email' => $i->client?->email,
            'issue_date' => $i->issue_date?->toDateString(),
            'due_date' => $i->due_date?->toDateString(),
            'total' => (float) $i->total,
            'paid_amount' => (float) $i->paid_amount,
            'balance' => max(0, (float) $i->total - (float) $i->paid_amount),
            'status' => $i->status,
            'is_overdue' => $i->due_date && $i->isOverdue() && ! in_array($i->status, ['draft']),
        ];
    }

    private function formOptions(): array
    {
        $company = auth()->user()->company;
        $settings = $company->settings;

        return [
            'clients' => Client::where('is_active', true)->orderBy('name')->get()
                ->map(fn (Client $c) => ['id' => $c->id, 'label' => $c->bill_to_name, 'email' => $c->email]),
            'products' => Product::where('is_active', true)->orderBy('name')->get()
                ->map(fn (Product $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'description' => $p->description,
                    'price' => (float) $p->price,
                    'unit' => $p->unit,
                    'tax_rate' => (float) $p->tax_rate,
                ]),
            'units' => Product::units(),
            'defaults' => [
                'payment_terms' => (int) ($company->payment_terms ?: 30),
                'notes' => $settings->default_notes ?? '',
                'terms' => $settings->default_terms ?? '',
                'tax_rate' => $settings?->tax_enabled ? (float) $settings->default_tax_rate : 0,
            ],
        ];
    }

    private function validateInvoice(Request $request, ?Invoice $invoice = null): array
    {
        $companyId = auth()->user()->company_id;

        $rules = [
            // Scoped to the current company: previously exists:clients,id let a user attach
            // (and then render) another tenant's client on their invoice.
            'client_id' => ['required', Rule::exists('clients', 'id')->where('company_id', $companyId)],
            'reference' => 'nullable|string|max:255',
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'notes' => 'nullable|string|max:5000',
            'terms' => 'nullable|string|max:5000',
            'discount_amount' => [
                'nullable', 'numeric', 'min:0',
                Rule::when(fn () => $request->input('discount_type') === 'percentage', ['max:100']),
            ],
            'discount_type' => 'in:fixed,percentage',
            'items' => 'required|array|min:1|max:200',
            'items.*.product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:2000',
            'items.*.quantity' => 'required|numeric|min:0.01|max:1000000',
            'items.*.price' => 'required|numeric|min:0|max:999999999',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.unit' => 'nullable|string|max:50',
        ];

        if (! $invoice) {
            $rules['invoice_number'] = [
                'required', 'string', 'max:50',
                Rule::unique('invoices')->where(fn ($q) => $q->where('company_id', $companyId)),
            ];
        }

        return $request->validate($rules, [
            'items.required' => 'Add at least one line item.',
            'items.*.name.required' => 'Each line item needs a name.',
        ]);
    }

    private function syncItems(Invoice $invoice, array $items): void
    {
        foreach (array_values($items) as $index => $item) {
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
    }

    private function queueEmail(Invoice $invoice, string $message)
    {
        $invoice->loadMissing('client');

        if (! $invoice->client?->email) {
            return redirect()->route('invoices.show', $invoice)->with('warning', 'Saved, but the client has no email address.');
        }

        SendDocumentEmail::dispatch($invoice);

        if ($invoice->status === 'draft') {
            $invoice->markAsSent();
        }

        return redirect()->route('invoices.show', $invoice)->with('success', $message);
    }

    private function templateView(Invoice $invoice): string
    {
        $template = $invoice->company->settings->invoice_template ?? 'classic';

        return array_key_exists($template, CompanySetting::invoiceTemplates()) && view()->exists("invoices.pdf.{$template}")
            ? "invoices.pdf.{$template}"
            : 'invoices.pdf.classic';
    }

    private function manualPaymentMethods(): array
    {
        return [
            'bank_transfer' => 'Bank transfer',
            'cash' => 'Cash',
            'credit_card' => 'Card (offline)',
            'paypal' => 'PayPal',
            'manual' => 'Other / manual',
            'other' => 'Other',
        ];
    }

    /** Neutralise spreadsheet formula injection in CSV exports. */
    private function csvSafe($value)
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }
}
