@extends('layouts.app')

@section('title', 'Products - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Products & Services')
@section('page-subtitle', 'Manage your product catalog')

@section('content')
    @php
        $company = auth()->user()->company;
        $plan = $company->subscriptionPlan;
        $productCount = $company->products()->count();
        $maxProducts = $plan->max_products ?? null;
        $hasReachedLimit = $company->hasReachedLimit('products');
    @endphp

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end mb-6">
        <div class="mt-4 sm:mt-0 flex items-center gap-4">
            @if($maxProducts !== null)
                <div class="text-sm text-gray-600">
                    <span class="{{ $hasReachedLimit ? 'text-red-600 font-semibold' : '' }}">{{ $productCount }}</span> / {{ $maxProducts }} products
                </div>
            @endif
            @if($hasReachedLimit)
                <a href="{{ route('settings.subscription') }}"
                    class="inline-flex items-center px-4 py-2 bg-orange-500 text-white text-sm font-medium rounded-lg hover:bg-orange-600 transition">
                    <i class="fas fa-arrow-up mr-2"></i> Upgrade Plan
                </a>
            @else
                <a href="{{ route('products.create') }}"
                    class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg bg-primary-hover transition">
                    <i class="fas fa-plus mr-2"></i> Add Product
                </a>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-4 border-b border-gray-100">
            <form action="{{ route('products.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search products..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <select name="status"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    <i class="fas fa-search mr-2"></i> Search
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Stock</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $product->name }}</div>
                                @if($product->sku)
                                    <div class="text-sm text-gray-500">SKU: {{ $product->sku }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    {{ $product->type === 'physical' ? 'bg-blue-100 text-blue-800' : ($product->type === 'digital' ? 'bg-purple-100 text-purple-800' : 'bg-gray-100 text-gray-800') }}">
                                    <i class="fas fa-{{ $product->type === 'physical' ? 'box' : ($product->type === 'digital' ? 'cloud' : 'briefcase') }} mr-1"></i>
                                    {{ ucfirst($product->type ?? 'service') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($product->price, 2) }}</td>
                            <td class="px-6 py-4">
                                @if($product->type === 'physical' && $product->track_stock)
                                    @if($product->isOutOfStock())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            Out of Stock
                                        </span>
                                    @elseif($product->isLowStock())
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            {{ $product->stock_quantity }} (Low)
                                        </span>
                                    @else
                                        <span class="text-gray-900">{{ $product->stock_quantity }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($product->is_active)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('products.edit', $product) }}"
                                        class="p-2 text-gray-400 text-primary-hover" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('products.destroy', $product) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-gray-400 hover:text-red-600" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-box text-4xl text-gray-300 mb-3"></i>
                                <p>No products found</p>
                                <a href="{{ route('products.create') }}"
                                    class="text-primary text-primary-hover text-sm font-medium">
                                    Add your first product
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $products->links() }}
            </div>
        @endif
    </div>
@endsection