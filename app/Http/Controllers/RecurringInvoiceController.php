<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Product;
use App\Models\RecurringInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class RecurringInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = RecurringInvoice::with('client');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('client', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('company_name', 'like', "%{$search}%"));
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $recurring = $query->latest()->paginate(15)->withQueryString();

        // Normalised monthly recurring revenue across active schedules.
        $perMonth = ['weekly' => 52 / 12, 'monthly' => 1, 'quarterly' => 1 / 3, 'semi_annual' => 1 / 6, 'annual' => 1 / 12];
        $mrr = RecurringInvoice::where('status', 'active')->get(['billing_cycle', 'total'])
            ->sum(fn ($r) => (float) $r->total * ($perMonth[$r->billing_cycle] ?? 1));

        return Inertia::render('RecurringInvoices/Index', [
            'recurring' => $recurring->through(fn (RecurringInvoice $r) => $this->row($r)),
            'filters' => $request->only('search', 'status', 'client_id'),
            'statuses' => RecurringInvoice::statuses(),
            'billingCycles' => RecurringInvoice::billingCycles(),
            'counts' => RecurringInvoice::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status'),
            'mrr' => round($mrr, 2),
        ]);
    }

    public function create(Request $request)
    {
        return Inertia::render('RecurringInvoices/Form', [
            'recurring' => null,
            'selectedClientId' => $request->integer('client_id') ?: null,
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateRecurring($request);

        $recurringInvoice = DB::transaction(function () use ($validated, $request) {
            $recurringInvoice = RecurringInvoice::create([
                'company_id' => auth()->user()->company_id,
                'client_id' => $validated['client_id'],
                'created_by' => auth()->id(),
                'name' => $validated['name'],
                'billing_cycle' => $validated['billing_cycle'],
                'billing_cycle_count' => $validated['billing_cycle_count'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'next_invoice_date' => $validated['start_date'],
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'discount_type' => $validated['discount_type'] ?? 'fixed',
                'auto_send' => $request->boolean('auto_send'),
                'status' => 'active',
            ]);

            $this->syncItems($recurringInvoice, $validated['items']);

            return $recurringInvoice;
        });

        return redirect()->route('recurring-invoices.show', $recurringInvoice)
            ->with('success', 'Recurring invoice created successfully.');
    }

    public function show(RecurringInvoice $recurringInvoice)
    {
        $recurringInvoice->load(['client', 'items', 'creator', 'invoices' => fn ($q) => $q->latest('issue_date')->latest('id')]);

        return Inertia::render('RecurringInvoices/Show', [
            'recurring' => [
                ...$this->row($recurringInvoice),
                'start_date' => $recurringInvoice->start_date?->toDateString(),
                'end_date' => $recurringInvoice->end_date?->toDateString(),
                'last_invoice_date' => $recurringInvoice->last_invoice_date?->toDateString(),
                'billing_cycle_count' => $recurringInvoice->billing_cycle_count,
                'subtotal' => (float) $recurringInvoice->subtotal,
                'tax_amount' => (float) $recurringInvoice->tax_amount,
                'discount_amount' => (float) $recurringInvoice->discount_amount,
                'discount_type' => $recurringInvoice->discount_type,
                'notes' => $recurringInvoice->notes,
                'terms' => $recurringInvoice->terms,
                'created_by' => $recurringInvoice->creator?->name,
                'client' => $recurringInvoice->client ? [
                    'id' => $recurringInvoice->client->id,
                    'name' => $recurringInvoice->client->bill_to_name,
                    'email' => $recurringInvoice->client->email,
                ] : null,
                'items' => $recurringInvoice->items->map(fn ($i) => [
                    'id' => $i->id,
                    'name' => $i->name,
                    'description' => $i->description,
                    'quantity' => (float) $i->quantity,
                    'unit' => $i->unit,
                    'price' => (float) $i->price,
                    'tax_rate' => (float) $i->tax_rate,
                ]),
            ],
            'upcoming' => $this->upcomingDates($recurringInvoice),
            'invoices' => $recurringInvoice->invoices->map(fn ($i) => [
                'id' => $i->id,
                'invoice_number' => $i->invoice_number,
                'issue_date' => $i->issue_date?->toDateString(),
                'due_date' => $i->due_date?->toDateString(),
                'total' => (float) $i->total,
                'status' => $i->status,
            ]),
        ]);
    }

    public function edit(RecurringInvoice $recurringInvoice)
    {
        if ($recurringInvoice->status === 'cancelled') {
            return back()->with('error', 'Cannot edit a cancelled recurring invoice.');
        }

        $recurringInvoice->load('items');

        return Inertia::render('RecurringInvoices/Form', [
            'recurring' => [
                'id' => $recurringInvoice->id,
                'client_id' => $recurringInvoice->client_id,
                'name' => $recurringInvoice->name,
                'billing_cycle' => $recurringInvoice->billing_cycle,
                'billing_cycle_count' => $recurringInvoice->billing_cycle_count,
                'start_date' => $recurringInvoice->start_date?->toDateString(),
                'end_date' => $recurringInvoice->end_date?->toDateString(),
                'auto_send' => (bool) $recurringInvoice->auto_send,
                'notes' => $recurringInvoice->notes,
                'terms' => $recurringInvoice->terms,
                'discount_amount' => (float) $recurringInvoice->discount_amount,
                'discount_type' => $recurringInvoice->discount_type ?? 'fixed',
                'items' => $recurringInvoice->items->map(fn ($i) => [
                    'product_id' => $i->product_id,
                    'name' => $i->name,
                    'description' => $i->description,
                    'quantity' => (float) $i->quantity,
                    'unit' => $i->unit,
                    'price' => (float) $i->price,
                    'tax_rate' => (float) $i->tax_rate,
                ]),
            ],
            'selectedClientId' => $recurringInvoice->client_id,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, RecurringInvoice $recurringInvoice)
    {
        if ($recurringInvoice->status === 'cancelled') {
            return back()->with('error', 'Cannot update a cancelled recurring invoice.');
        }

        $validated = $this->validateRecurring($request, $recurringInvoice);

        DB::transaction(function () use ($validated, $request, $recurringInvoice) {
            $recurringInvoice->update([
                'client_id' => $validated['client_id'],
                'name' => $validated['name'],
                'billing_cycle' => $validated['billing_cycle'],
                'billing_cycle_count' => $validated['billing_cycle_count'] ?? null,
                'end_date' => $validated['end_date'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'terms' => $validated['terms'] ?? null,
                'discount_amount' => $validated['discount_amount'] ?? 0,
                'discount_type' => $validated['discount_type'] ?? 'fixed',
                'auto_send' => $request->boolean('auto_send'),
            ]);

            $recurringInvoice->items()->delete();
            $recurringInvoice->unsetRelation('items');
            $this->syncItems($recurringInvoice, $validated['items']);
        });

        return redirect()->route('recurring-invoices.show', $recurringInvoice)
            ->with('success', 'Recurring invoice updated successfully.');
    }

    public function destroy(RecurringInvoice $recurringInvoice)
    {
        $recurringInvoice->delete();

        return redirect()->route('recurring-invoices.index')
            ->with('success', 'Recurring invoice deleted successfully.');
    }

    public function pause(RecurringInvoice $recurringInvoice)
    {
        if ($recurringInvoice->status !== 'active') {
            return back()->with('error', 'Only active recurring invoices can be paused.');
        }

        $recurringInvoice->pause();

        return back()->with('success', 'Recurring invoice paused.');
    }

    public function resume(RecurringInvoice $recurringInvoice)
    {
        if ($recurringInvoice->status !== 'paused') {
            return back()->with('error', 'Only paused recurring invoices can be resumed.');
        }

        $recurringInvoice->resume();

        return back()->with('success', 'Recurring invoice resumed.');
    }

    public function cancel(RecurringInvoice $recurringInvoice)
    {
        if (in_array($recurringInvoice->status, ['cancelled', 'completed'])) {
            return back()->with('error', 'This recurring invoice cannot be cancelled.');
        }

        $recurringInvoice->cancel();

        return back()->with('success', 'Recurring invoice cancelled.');
    }

    public function generateNow(RecurringInvoice $recurringInvoice)
    {
        if ($recurringInvoice->status !== 'active') {
            return back()->with('error', 'Only active recurring invoices can generate invoices.');
        }

        $recurringInvoice->next_invoice_date = now();
        $invoice = $recurringInvoice->generateInvoice();

        if ($invoice) {
            return redirect()->route('invoices.show', $invoice)
                ->with('success', 'Invoice generated from the recurring schedule.');
        }

        return back()->with('error', 'Failed to generate invoice (the schedule may have ended).');
    }

    /* ------------------------------------------------------------------ */

    private function row(RecurringInvoice $r): array
    {
        return [
            'id' => $r->id,
            'name' => $r->name,
            'client_name' => $r->client?->bill_to_name,
            'billing_cycle' => $r->billing_cycle,
            'next_invoice_date' => $r->next_invoice_date?->toDateString(),
            'invoices_generated' => (int) $r->invoices_generated,
            'total' => (float) $r->total,
            'status' => $r->status,
            'auto_send' => (bool) $r->auto_send,
        ];
    }

    /** The next few scheduled invoice dates, honouring end date and cycle count. */
    private function upcomingDates(RecurringInvoice $r): array
    {
        if ($r->status !== 'active' || ! $r->next_invoice_date) {
            return [];
        }

        $dates = [];
        $probe = $r->replicate();
        $probe->next_invoice_date = $r->next_invoice_date->copy();
        $remaining = $r->billing_cycle_count ? max(0, $r->billing_cycle_count - $r->invoices_generated) : 6;

        for ($i = 0; $i < min(6, $remaining); $i++) {
            if ($r->end_date && $probe->next_invoice_date->gt($r->end_date)) {
                break;
            }
            $dates[] = $probe->next_invoice_date->toDateString();
            $probe->next_invoice_date = $probe->getNextBillingDate();
        }

        return $dates;
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
            'billingCycles' => RecurringInvoice::billingCycles(),
            'defaults' => [
                'notes' => $settings->default_notes ?? '',
                'terms' => $settings->default_terms ?? '',
                'tax_rate' => $settings?->tax_enabled ? (float) $settings->default_tax_rate : 0,
            ],
        ];
    }

    private function validateRecurring(Request $request, ?RecurringInvoice $existing = null): array
    {
        $companyId = auth()->user()->company_id;

        $rules = [
            // Scoped to this company (was exists:clients,id — cross-tenant).
            'client_id' => ['required', Rule::exists('clients', 'id')->where('company_id', $companyId)],
            'name' => 'required|string|max:255',
            'billing_cycle' => ['required', Rule::in(array_keys(RecurringInvoice::billingCycles()))],
            'billing_cycle_count' => 'nullable|integer|min:1|max:1000',
            'notes' => 'nullable|string|max:5000',
            'terms' => 'nullable|string|max:5000',
            'discount_amount' => [
                'nullable', 'numeric', 'min:0',
                Rule::when(fn () => $request->input('discount_type') === 'percentage', ['max:100']),
            ],
            'discount_type' => 'in:fixed,percentage',
            'auto_send' => 'boolean',
            'items' => 'required|array|min:1|max:200',
            'items.*.product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string|max:2000',
            'items.*.quantity' => 'required|numeric|min:0.01|max:1000000',
            'items.*.price' => 'required|numeric|min:0|max:999999999',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.unit' => 'nullable|string|max:50',
        ];

        if ($existing) {
            // start_date is fixed once created; the end date must follow it.
            $rules['end_date'] = ['nullable', 'date', 'after:' . $existing->start_date->toDateString()];
        } else {
            $rules['start_date'] = 'required|date|after_or_equal:today';
            $rules['end_date'] = 'nullable|date|after:start_date';
        }

        return $request->validate($rules, [
            'items.required' => 'Add at least one line item.',
            'items.*.name.required' => 'Each line item needs a name.',
        ]);
    }

    private function syncItems(RecurringInvoice $recurringInvoice, array $items): void
    {
        foreach (array_values($items) as $index => $item) {
            $recurringInvoice->items()->create([
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

        $recurringInvoice->calculateTotals();
        $recurringInvoice->save();
    }
}
