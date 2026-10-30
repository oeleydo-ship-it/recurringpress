<?php

namespace App\Http\Controllers;

use App\Jobs\SendDocumentEmail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Support\CustomFields;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $quotations = $this->filtered($request)->with(['client', 'user'])
            ->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('Quotations/Index', [
            'quotations' => $quotations->through(fn (Quotation $q) => [...$this->listRow($q), 'custom_values' => $q->custom_values]),
            'customColumns' => CustomFields::listColumns('quotation'),
            'filters' => $request->only('search', 'status'),
            'counts' => Quotation::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status'),
            'summary' => [
                'open_value' => (float) Quotation::whereIn('status', ['draft', 'sent'])->sum('total'),
                'accepted_value' => (float) Quotation::whereIn('status', ['accepted', 'converted'])->sum('total'),
            ],
        ]);
    }

    public function export(Request $request)
    {
        $quotations = $this->filtered($request)->with('client')->orderBy('created_at', 'desc')->get();
        $filename = 'quotations-' . now()->format('Y-m-d-His') . '.csv';
        $fields = CustomFields::definitions('quotation');

        return response()->stream(function () use ($quotations, $fields) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Quotation Number', 'Client', 'Issue Date', 'Valid Until', 'Total', 'Status', ...$fields->pluck('label')]);

            foreach ($quotations as $quotation) {
                fputcsv($handle, array_map([$this, 'csvSafe'], [
                    $quotation->quotation_number,
                    $quotation->client?->bill_to_name ?? 'N/A',
                    optional($quotation->issue_date)->format('Y-m-d'),
                    optional($quotation->valid_until)->format('Y-m-d'),
                    number_format($quotation->total, 2, '.', ''),
                    ucfirst($quotation->status),
                    ...CustomFields::csvRow($fields, $quotation->custom_values),
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

        return Inertia::render('Quotations/Form', [
            'quotation' => null,
            'nextNumber' => $company->getNextQuotationNumber(),
            'selectedClientId' => $request->integer('client_id') ?: null,
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request)
    {
        [$validated, $custom] = CustomFields::validateWith($request, 'quotation', fn () => $this->validateQuotation($request));
        $company = auth()->user()->company;

        // Created together with its items and totals so a failure can't leave a
        // zero-total quotation behind.
        $quotation = DB::transaction(function () use ($validated, $custom, $company) {
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
                'custom_values' => $custom ?: null,
                'status' => Quotation::STATUS_DRAFT,
            ]);

            $this->syncItems($quotation, $validated['items']);
            $quotation->calculateTotals();

            return $quotation;
        });

        if ($request->boolean('send_now')) {
            return $this->queueEmail($quotation, 'Quotation created and queued for delivery.');
        }

        return redirect()->route('quotations.show', $quotation)
            ->with('success', 'Quotation created successfully.');
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['client', 'items', 'user']);

        return Inertia::render('Quotations/Show', [
            'quotation' => [
                ...$this->listRow($quotation),
                'reference' => $quotation->reference,
                'subtotal' => (float) $quotation->subtotal,
                'tax_amount' => (float) $quotation->tax_amount,
                'discount_amount' => (float) $quotation->discount_amount,
                'sent_at' => $quotation->sent_at?->toIso8601String(),
                'viewed_at' => $quotation->viewed_at?->toIso8601String(),
                'responded_at' => $quotation->responded_at?->toIso8601String(),
                'response_notes' => $quotation->response_notes,
                'created_at' => $quotation->created_at?->toIso8601String(),
                'created_by' => $quotation->user?->name,
                'share_url' => $quotation->share_url,
                'converted_invoice_id' => $quotation->converted_invoice_id,
                'custom_values' => $quotation->custom_values,
                'client' => $quotation->client ? [
                    'id' => $quotation->client->id,
                    'name' => $quotation->client->bill_to_name,
                    'email' => $quotation->client->email,
                ] : null,
            ],
            'can' => [
                'edit' => $quotation->isEditable(),
                'convert' => $quotation->canConvert(),
                'send' => $quotation->canTransitionTo(Quotation::STATUS_SENT),
                'accept' => $quotation->canTransitionTo(Quotation::STATUS_ACCEPTED),
                'reject' => $quotation->canTransitionTo(Quotation::STATUS_REJECTED),
            ],
            'customFields' => CustomFields::forForm('quotation'),
        ]);
    }

    /** Quotation rendered with the company template as HTML (exact preview of the PDF). */
    public function preview(Quotation $quotation)
    {
        $quotation->load(['client', 'items', 'company']);

        return view($this->templateView($quotation), compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        if (! $quotation->isEditable()) {
            return redirect()->route('quotations.show', $quotation)
                ->with('error', 'Cannot edit a converted quotation.');
        }

        $quotation->load('items');

        return Inertia::render('Quotations/Form', [
            'quotation' => [
                'id' => $quotation->id,
                'quotation_number' => $quotation->quotation_number,
                'client_id' => $quotation->client_id,
                'reference' => $quotation->reference,
                'issue_date' => $quotation->issue_date?->toDateString(),
                'valid_until' => $quotation->valid_until?->toDateString(),
                'notes' => $quotation->notes,
                'terms' => $quotation->terms,
                'discount_amount' => (float) $quotation->discount_amount,
                'discount_type' => $quotation->discount_type ?? 'fixed',
                'custom_values' => $quotation->custom_values,
                'items' => $quotation->items->map(fn ($i) => [
                    'product_id' => $i->product_id,
                    'name' => $i->name,
                    'description' => $i->description,
                    'quantity' => (float) $i->quantity,
                    'unit' => $i->unit,
                    'price' => (float) $i->price,
                    'tax_rate' => (float) $i->tax_rate,
                ]),
            ],
            'nextNumber' => $quotation->quotation_number,
            'selectedClientId' => $quotation->client_id,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Quotation $quotation)
    {
        if (! $quotation->isEditable()) {
            return redirect()->route('quotations.show', $quotation)
                ->with('error', 'Cannot update a converted quotation.');
        }

        [$validated, $custom] = CustomFields::validateWith($request, 'quotation', fn () => $this->validateQuotation($request, $quotation), $quotation->custom_values);

        // Items are replaced wholesale, so the delete and re-insert share a transaction.
        DB::transaction(function () use ($quotation, $validated, $custom) {
            $quotation->update([
                'client_id' => $validated['client_id'],
                'issue_date' => $validated['issue_date'],
                'valid_until' => $validated['valid_until'],
                'reference' => $validated['reference'] ?? null,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'discount_type' => $validated['discount_type'] ?? 'fixed',
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'custom_values' => $custom ?: null,
            ]);

            $quotation->items()->delete();
            $quotation->unsetRelation('items');
            $this->syncItems($quotation, $validated['items']);
            $quotation->calculateTotals();
        });

        if ($request->boolean('send_now')) {
            return $this->queueEmail($quotation, 'Quotation saved and queued for delivery.');
        }

        return redirect()->route('quotations.show', $quotation)
            ->with('success', 'Quotation updated successfully.');
    }

    public function destroy(Quotation $quotation)
    {
        if (! $quotation->isEditable()) {
            return redirect()->route('quotations.index')
                ->with('error', 'Cannot delete a converted quotation.');
        }

        $quotation->delete();

        return redirect()->route('quotations.index')
            ->with('success', 'Quotation deleted successfully.');
    }

    public function send(Quotation $quotation)
    {
        if (! $quotation->canTransitionTo(Quotation::STATUS_SENT)) {
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
        $quotation->load('client');

        if (! $quotation->client || ! $quotation->client->email) {
            return back()->with('error', 'Client email address is missing.');
        }

        return $this->queueEmail($quotation, "Quotation queued for delivery to {$quotation->client->email}.");
    }

    public function accept(Quotation $quotation)
    {
        if (! $quotation->canTransitionTo(Quotation::STATUS_ACCEPTED)) {
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
        if (! $quotation->canTransitionTo(Quotation::STATUS_REJECTED)) {
            return back()->with('error', "A {$quotation->status} quotation cannot be marked as rejected.");
        }

        $quotation->update([
            'status' => Quotation::STATUS_REJECTED,
            'responded_at' => $quotation->responded_at ?? now(),
        ]);

        return back()->with('success', 'Quotation marked as rejected.');
    }

    public function convert(Quotation $quotation)
    {
        $company = auth()->user()->company;

        // Lock the quotation row so a double-click / retried request can't create two
        // invoices from the same quotation (previously no transaction or lock).
        $invoice = DB::transaction(function () use ($quotation, $company) {
            $locked = Quotation::whereKey($quotation->id)->lockForUpdate()->first();

            if (! $locked || ! $locked->canConvert()) {
                return null;
            }

            $invoice = Invoice::create([
                'company_id' => $company->id,
                'client_id' => $locked->client_id,
                'created_by' => auth()->id(),
                'invoice_number' => $company->getNextInvoiceNumber(),
                'reference' => $locked->reference ?: $locked->quotation_number,
                'issue_date' => now(),
                'due_date' => now()->addDays((int) ($company->payment_terms ?: 30)),
                'discount_amount' => $locked->discount_amount,
                'discount_type' => $locked->discount_type,
                'notes' => $locked->notes,
                'terms' => $locked->terms,
                // Values carry over where the invoice defines a field with the same key.
                'custom_values' => CustomFields::carryOver($locked->custom_values, 'invoice', $company->id),
                'status' => 'draft',
            ]);

            foreach ($locked->items as $index => $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'unit' => $item->unit,
                    'price' => $item->price,
                    'tax_rate' => $item->tax_rate,
                    'sort_order' => $index,
                ]);
            }

            $invoice->calculateTotals();
            $invoice->save();

            $locked->update([
                'status' => Quotation::STATUS_CONVERTED,
                'converted_invoice_id' => $invoice->id,
            ]);

            return $invoice;
        });

        if (! $invoice) {
            return back()->with('error', 'This quotation cannot be converted to an invoice.');
        }

        return redirect()->route('invoices.show', $invoice)
            ->with('success', 'Quotation converted to invoice successfully.');
    }

    public function download(Quotation $quotation)
    {
        $quotation->load(['client', 'items', 'company']);

        return Pdf::loadView($this->templateView($quotation), compact('quotation'))
            ->download("quotation-{$quotation->quotation_number}.pdf");
    }

    public function duplicate(Quotation $quotation)
    {
        $newQuotation = DB::transaction(function () use ($quotation) {
            $company = auth()->user()->company;

            // Reset lifecycle fields; previously sent/viewed/response data was copied.
            $newQuotation = $quotation->replicate(['sent_at', 'viewed_at', 'responded_at', 'response_notes', 'response_ip', 'converted_invoice_id', 'share_token']);
            $newQuotation->quotation_number = $company->getNextQuotationNumber();
            $newQuotation->status = Quotation::STATUS_DRAFT;
            $newQuotation->user_id = auth()->id();
            $newQuotation->issue_date = now();
            $newQuotation->valid_until = now()->addDays((int) ($company->quotation_validity_days ?: 30));
            $newQuotation->share_token = Str::random(64);
            $newQuotation->save();

            foreach ($quotation->items as $item) {
                $newItem = $item->replicate();
                $newItem->quotation_id = $newQuotation->id;
                $newItem->save();
            }

            return $newQuotation;
        });

        return redirect()->route('quotations.edit', $newQuotation)
            ->with('success', 'Quotation duplicated — review and save.');
    }

    /* ------------------------------------------------------------------ */

    private function filtered(Request $request): Builder
    {
        $query = Quotation::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('quotation_number', 'like', "%{$search}%")
                    ->orWhere('reference', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('company_name', 'like', "%{$search}%"));
            });
        }

        return $query;
    }

    private function listRow(Quotation $q): array
    {
        return [
            'id' => $q->id,
            'quotation_number' => $q->quotation_number,
            'client_name' => $q->client?->bill_to_name,
            'client_email' => $q->client?->email,
            'issue_date' => $q->issue_date?->toDateString(),
            'valid_until' => $q->valid_until?->toDateString(),
            'total' => (float) $q->total,
            'status' => $q->status,
            'is_expired' => $q->valid_until && $q->valid_until->isPast() && in_array($q->status, ['draft', 'sent']),
            'viewed' => (bool) $q->viewed_at,
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
            'customFields' => CustomFields::forForm('quotation'),
            'clientCustomFields' => CustomFields::forForm('client'),
            'defaults' => [
                'validity_days' => (int) ($company->quotation_validity_days ?: 30),
                'notes' => $settings->default_notes ?? '',
                'terms' => $settings->default_terms ?? '',
                'tax_rate' => $settings?->tax_enabled ? (float) $settings->default_tax_rate : 0,
            ],
        ];
    }

    private function validateQuotation(Request $request, ?Quotation $quotation = null): array
    {
        $companyId = auth()->user()->company_id;

        $rules = [
            // Scoped to this company (was exists:clients,id — cross-tenant).
            'client_id' => ['required', Rule::exists('clients', 'id')->where('company_id', $companyId)],
            'issue_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:issue_date',
            'reference' => 'nullable|string|max:255',
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
            'items.*.unit' => 'nullable|string|max:50',
            'items.*.price' => 'required|numeric|min:0|max:999999999',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ];

        if (! $quotation) {
            $rules['quotation_number'] = [
                'required', 'string', 'max:50',
                Rule::unique('quotations')->where(fn ($q) => $q->where('company_id', $companyId)),
            ];
        }

        return $request->validate($rules, [
            'items.required' => 'Add at least one line item.',
            'items.*.name.required' => 'Each line item needs a name.',
        ]);
    }

    private function syncItems(Quotation $quotation, array $items): void
    {
        foreach (array_values($items) as $item) {
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

    private function queueEmail(Quotation $quotation, string $message)
    {
        $quotation->loadMissing('client');

        if (! $quotation->client?->email) {
            return redirect()->route('quotations.show', $quotation)->with('warning', 'Saved, but the client has no email address.');
        }

        SendDocumentEmail::dispatch($quotation);

        $quotation->update([
            'sent_at' => $quotation->sent_at ?? now(),
            'status' => $quotation->canTransitionTo(Quotation::STATUS_SENT) ? Quotation::STATUS_SENT : $quotation->status,
        ]);

        return redirect()->route('quotations.show', $quotation)->with('success', $message);
    }

    private function templateView(Quotation $quotation): string
    {
        $template = $quotation->company->settings->invoice_template ?? 'classic';

        // Not every invoice template has a quotation version; fall back to classic.
        return view()->exists("quotations.pdf.{$template}") ? "quotations.pdf.{$template}" : 'quotations.pdf.classic';
    }

    /** Neutralise spreadsheet formula injection in CSV exports. */
    private function csvSafe($value)
    {
        return is_string($value) && preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
    }
}
