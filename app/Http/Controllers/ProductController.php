<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

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

        $products = $query->latest()->paginate(15);

        return view('products.index', compact('products'));
    }

    public function create()
    {
        $company = auth()->user()->company;
        
        if ($company->hasReachedLimit('products')) {
            return redirect()->route('products.index')
                ->with('error', 'You have reached your product limit. Please upgrade your subscription plan to add more products.');
        }
        
        $units = Product::units();
        return view('products.create', compact('units'));
    }

    public function store(Request $request)
    {
        $company = auth()->user()->company;
        
        if ($company->hasReachedLimit('products')) {
            return redirect()->route('products.index')
                ->with('error', 'You have reached your product limit. Please upgrade your subscription plan to add more products.');
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'sku' => 'nullable|string|max:100',
            'type' => 'required|in:physical,digital,service',
            'track_stock' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        $validated['company_id'] = auth()->user()->company_id;
        $validated['tax_rate'] = $validated['tax_rate'] ?? 0;
        $validated['track_stock'] = $request->boolean('track_stock');
        
        if ($validated['type'] !== 'physical') {
            $validated['track_stock'] = false;
            $validated['stock_quantity'] = null;
            $validated['low_stock_threshold'] = null;
        }

        Product::create($validated);

        return redirect()->route('products.index')
            ->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        $units = Product::units();
        return view('products.edit', compact('product', 'units'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'sku' => 'nullable|string|max:100',
            'is_active' => 'boolean',
            'type' => 'required|in:physical,digital,service',
            'track_stock' => 'boolean',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['track_stock'] = $request->boolean('track_stock');
        
        if ($validated['type'] !== 'physical') {
            $validated['track_stock'] = false;
            $validated['stock_quantity'] = null;
            $validated['low_stock_threshold'] = null;
        }

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
}
