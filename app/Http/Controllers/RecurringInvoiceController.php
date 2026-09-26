<?php

namespace App\Http\Controllers;

use App\Models\RecurringInvoice;
use App\Models\RecurringInvoiceItem;
use App\Models\Client;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecurringInvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = RecurringInvoice::with('client');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('client', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $recurringInvoices = $query->latest()->paginate(15);
        $clients = Client::orderBy('name')->get();
        $statuses = RecurringInvoice::statuses();

        return view('recurring-invoices.index', compact('recurringInvoices', 'clients', 'statuses'));
    }

    public function create()
    {
        $clients = Client::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $billingCycles = RecurringInvoice::billingCycles();
        $company = auth()->user()->company;
        $settings = $company->settings;

        return view('recurring-invoices.create', compact('clients', 'products', 'billingCycles', 'settings'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'billing_cycle' => 'required|in:weekly,monthly,quarterly,semi_annual,annual',
            'billing_cycle_count' => 'nullable|integer|min:1',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'in:fixed,percentage',
            'auto_send' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.unit' => 'nullable|string|max:50',
        ]);

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

            foreach ($validated['items'] as $index => $item) {
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

            return $recurringInvoice;
        });

        return redirect()->route('recurring-invoices.show', $recurringInvoice)
            ->with('success', 'Recurring invoice created successfully.');
    }

    public function show(RecurringInvoice $recurringInvoice)
    {
        $recurringInvoice->load(['client', 'items', 'creator', 'company', 'invoices']);

        return view('recurring-invoices.show', compact('recurringInvoice'));
    }

    public function edit(RecurringInvoice $recurringInvoice)
    {
        if ($recurringInvoice->status === 'cancelled') {
            return back()->with('error', 'Cannot edit a cancelled recurring invoice.');
        }

        $recurringInvoice->load('items');
        $clients = Client::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)->orderBy('name')->get();
        $billingCycles = RecurringInvoice::billingCycles();

        return view('recurring-invoices.edit', compact('recurringInvoice', 'clients', 'products', 'billingCycles'));
    }

    public function update(Request $request, RecurringInvoice $recurringInvoice)
    {
        if ($recurringInvoice->status === 'cancelled') {
            return back()->with('error', 'Cannot update a cancelled recurring invoice.');
        }

        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'name' => 'required|string|max:255',
            'billing_cycle' => 'required|in:weekly,monthly,quarterly,semi_annual,annual',
            'billing_cycle_count' => 'nullable|integer|min:1',
            'end_date' => 'nullable|date|after:start_date',
            'notes' => 'nullable|string',
            'terms' => 'nullable|string',
            'discount_amount' => 'nullable|numeric|min:0',
            'discount_type' => 'in:fixed,percentage',
            'auto_send' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:255',
            'items.*.description' => 'nullable|string',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.price' => 'required|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.unit' => 'nullable|string|max:50',
        ]);

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

            foreach ($validated['items'] as $index => $item) {
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

        return back()->with('success', 'Recurring invoice paused successfully.');
    }

    public function resume(RecurringInvoice $recurringInvoice)
    {
        if ($recurringInvoice->status !== 'paused') {
            return back()->with('error', 'Only paused recurring invoices can be resumed.');
        }

        $recurringInvoice->resume();

        return back()->with('success', 'Recurring invoice resumed successfully.');
    }

    public function cancel(RecurringInvoice $recurringInvoice)
    {
        if (in_array($recurringInvoice->status, ['cancelled', 'completed'])) {
            return back()->with('error', 'This recurring invoice cannot be cancelled.');
        }

        $recurringInvoice->cancel();

        return back()->with('success', 'Recurring invoice cancelled successfully.');
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
                ->with('success', 'Invoice generated successfully from recurring invoice.');
        }

        return back()->with('error', 'Failed to generate invoice.');
    }
}
