<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\CustomFields;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $products = $query->latest()->paginate(15)->withQueryString();

        return Inertia::render('Products/Index', [
            'products' => $products->through(fn (Product $p) => $this->payload($p)),
            'filters' => $request->only('search', 'status', 'type'),
            'units' => Product::units(),
            'customColumns' => CustomFields::listColumns('product'),
        ]);
    }

    public function create()
    {
        $company = auth()->user()->company;

        if ($company->hasReachedLimit('products')) {
            return redirect()->route('products.index')
                ->with('error', 'You have reached your product limit. Please upgrade your subscription plan to add more products.');
        }

        return Inertia::render('Products/Form', [
            'product' => null,
            'units' => Product::units(),
            'billingCycles' => Product::billingCycles(),
            'customFields' => CustomFields::forForm('product'),
        ]);
    }

    public function store(Request $request)
    {
        $company = auth()->user()->company;

        if ($company->hasReachedLimit('products')) {
            return redirect()->route('products.index')
                ->with('error', 'You have reached your product limit. Please upgrade your subscription plan to add more products.');
        }

        [$validated, $custom] = CustomFields::validateWith($request, 'product', fn () => $this->validated($request));
        $validated['custom_values'] = $custom ?: null;
        $validated['company_id'] = auth()->user()->company_id;

        Product::create($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        return Inertia::render('Products/Show', [
            'product' => $this->payload($product),
            'units' => Product::units(),
            'billingCycles' => Product::billingCycles(),
            'usage' => [
                'invoice_lines' => $product->invoiceItems()->count(),
                'quantity_sold' => (float) $product->invoiceItems()->sum('quantity'),
                'revenue' => (float) $product->invoiceItems()->sum(DB::raw('quantity * price')),
            ],
            'customFields' => CustomFields::forForm('product'),
        ]);
    }

    public function edit(Product $product)
    {
        return Inertia::render('Products/Form', [
            'product' => $this->payload($product),
            'units' => Product::units(),
            'billingCycles' => Product::billingCycles(),
            'customFields' => CustomFields::forForm('product'),
        ]);
    }

    public function update(Request $request, Product $product)
    {
        [$validated, $custom] = CustomFields::validateWith($request, 'product', fn () => $this->validated($request, true), $product->custom_values);
        $validated['custom_values'] = $custom ?: null;
        $product->update($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        if ($product->invoiceItems()->exists()) {
            return back()->with('error', 'Cannot delete product used in invoices.');
        }

        $product->delete();

        return redirect()->route('products.index')
            ->with('success', 'Product deleted successfully.');
    }

    /**
     * Shared validation. Recurring-billing fields were shown in the form but previously never
     * validated or saved; they are now persisted.
     */
    private function validated(Request $request, bool $updating = false): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0|max:999999999',
            'unit' => ['required', 'string', Rule::in(array_keys(Product::units()))],
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'sku' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'type' => 'required|in:physical,digital,service',
            'track_stock' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'is_recurring' => 'boolean',
            'billing_cycle' => ['nullable', 'required_if:is_recurring,true', Rule::in(array_keys(Product::billingCycles()))],
            'billing_cycle_count' => 'nullable|integer|min:1|max:1000',
            'has_trial' => 'boolean',
            'trial_days' => 'nullable|required_if:has_trial,true|integer|min:1|max:365',
            'auto_renew' => 'boolean',
        ]);

        $validated['tax_rate'] = $validated['tax_rate'] ?? 0;
        $validated['track_stock'] = $request->boolean('track_stock');
        $validated['is_recurring'] = $request->boolean('is_recurring');
        $validated['has_trial'] = $validated['is_recurring'] && $request->boolean('has_trial');
        $validated['auto_renew'] = $request->boolean('auto_renew', true);

        if ($updating) {
            $validated['is_active'] = $request->boolean('is_active');
        } else {
            unset($validated['is_active']);
        }

        if ($validated['type'] !== 'physical') {
            $validated['track_stock'] = false;
            $validated['stock_quantity'] = null;
            $validated['low_stock_threshold'] = null;
        }

        if (! $validated['is_recurring']) {
            $validated['billing_cycle'] = null;
            $validated['billing_cycle_count'] = null;
            $validated['trial_days'] = null;
        } elseif (! $validated['has_trial']) {
            $validated['trial_days'] = null;
        }

        return $validated;
    }

    private function payload(Product $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'description' => $p->description,
            'price' => (float) $p->price,
            'unit' => $p->unit,
            'tax_rate' => (float) $p->tax_rate,
            'sku' => $p->sku,
            'is_active' => (bool) $p->is_active,
            'type' => $p->type ?? 'service',
            'track_stock' => (bool) $p->track_stock,
            'stock_quantity' => $p->stock_quantity,
            'low_stock_threshold' => $p->low_stock_threshold,
            'is_recurring' => (bool) $p->is_recurring,
            'billing_cycle' => $p->billing_cycle,
            'billing_cycle_count' => $p->billing_cycle_count,
            'has_trial' => (bool) $p->has_trial,
            'trial_days' => $p->trial_days,
            'auto_renew' => $p->auto_renew === null ? true : (bool) $p->auto_renew,
            'custom_values' => $p->custom_values,
        ];
    }
}
