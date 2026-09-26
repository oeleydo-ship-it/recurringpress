@extends('layouts.app')

@section('title', 'Edit Product - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Edit Product')

@section('content')
    <div class="mb-6">
        <a href="{{ route('products.index') }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-2"></i> Back to Products
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 max-w-2xl">
        <form action="{{ route('products.update', $product) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Product Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" id="description" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('description', $product->description) }}</textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="type" class="block text-sm font-medium text-gray-700 mb-1">Product Type *</label>
                        <select name="type" id="type" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary"
                            onchange="toggleStockFields()">
                            @foreach(\App\Models\Product::productTypes() as $value => $label)
                                <option value="{{ $value }}" {{ old('type', $product->type ?? 'service') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price *</label>
                        <div class="relative">
                            <span class="absolute left-3 top-2 text-gray-500">{{ auth()->user()->company->currency_symbol }}</span>
                            <input type="number" name="price" id="price" value="{{ old('price', $product->price) }}"
                                step="0.01" min="0" required
                                class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                    </div>

                    <div>
                        <label for="unit" class="block text-sm font-medium text-gray-700 mb-1">Unit *</label>
                        <select name="unit" id="unit" required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            @foreach($units as $value => $label)
                                <option value="{{ $value }}" {{ old('unit', $product->unit) === $value ? 'selected' : '' }}>
                                    {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="tax_rate" class="block text-sm font-medium text-gray-700 mb-1">Tax Rate (%)</label>
                        <input type="number" name="tax_rate" id="tax_rate" value="{{ old('tax_rate', $product->tax_rate) }}"
                            step="0.01" min="0" max="100"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>

                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700 mb-1">SKU</label>
                        <input type="text" name="sku" id="sku" value="{{ old('sku', $product->sku) }}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                </div>

                <!-- Recurring/Subscription Settings -->
                <div class="border-t pt-6 mt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Recurring/Subscription Settings</h3>
                    <div class="space-y-4">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_recurring" id="is_recurring" value="1" {{ old('is_recurring', $product->is_recurring) ? 'checked' : '' }}
                                class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
                                onchange="toggleRecurringFields()">
                            <span class="ml-2 text-sm text-gray-700">This is a recurring/subscription product</span>
                        </label>
                        
                        <div id="recurringFields" class="{{ old('is_recurring', $product->is_recurring) ? '' : 'hidden' }}">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-4">
                                <div>
                                    <label for="billing_cycle" class="block text-sm font-medium text-gray-700 mb-1">Billing Cycle</label>
                                    <select name="billing_cycle" id="billing_cycle"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                        <option value="">Select billing cycle...</option>
                                        @foreach(\App\Models\Product::billingCycles() as $value => $label)
                                            <option value="{{ $value }}" {{ old('billing_cycle', $product->billing_cycle) === $value ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="billing_cycle_count" class="block text-sm font-medium text-gray-700 mb-1">Number of Cycles</label>
                                    <input type="number" name="billing_cycle_count" id="billing_cycle_count" value="{{ old('billing_cycle_count', $product->billing_cycle_count) }}"
                                        min="1" placeholder="Leave empty for unlimited"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                    <p class="text-xs text-gray-500 mt-1">Leave empty for unlimited billing cycles</p>
                                </div>
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="has_trial" value="1" {{ old('has_trial', $product->has_trial) ? 'checked' : '' }}
                                            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary"
                                            onchange="toggleTrialFields()">
                                        <span class="ml-2 text-sm text-gray-700">Offer free trial period</span>
                                    </label>
                                </div>
                                <div id="trialFields" class="{{ old('has_trial', $product->has_trial) ? '' : 'hidden' }}">
                                    <label for="trial_days" class="block text-sm font-medium text-gray-700 mb-1">Trial Days</label>
                                    <input type="number" name="trial_days" id="trial_days" value="{{ old('trial_days', $product->trial_days ?? 14) }}"
                                        min="1"
                                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                                </div>
                                <div>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="auto_renew" value="1" {{ old('auto_renew', $product->auto_renew ?? true) ? 'checked' : '' }}
                                            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                                        <span class="ml-2 text-sm text-gray-700">Auto-renew by default</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stock Management (only for physical products) -->
                <div id="stockFields" class="border-t pt-6 mt-6 {{ old('type', $product->type ?? 'service') !== 'physical' ? 'hidden' : '' }}">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Stock Management</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="flex items-center mb-4">
                                <input type="checkbox" name="track_stock" value="1" {{ old('track_stock', $product->track_stock) ? 'checked' : '' }}
                                    class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                                <span class="ml-2 text-sm text-gray-700">Track Stock Quantity</span>
                            </label>
                        </div>
                        <div>
                            <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-1">Stock Quantity</label>
                            <input type="number" name="stock_quantity" id="stock_quantity" value="{{ old('stock_quantity', $product->stock_quantity ?? 0) }}"
                                min="0" step="1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        </div>
                        <div>
                            <label for="low_stock_threshold" class="block text-sm font-medium text-gray-700 mb-1">Low Stock Alert</label>
                            <input type="number" name="low_stock_threshold" id="low_stock_threshold" value="{{ old('low_stock_threshold', $product->low_stock_threshold ?? 5) }}"
                                min="0" step="1"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            <p class="text-xs text-gray-500 mt-1">Alert when stock falls below this number</p>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }}
                            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">Active Product</span>
                    </label>
                </div>
            </div>

            @push('scripts')
            <script>
                function toggleStockFields() {
                    const type = document.getElementById('type').value;
                    const stockFields = document.getElementById('stockFields');
                    if (type === 'physical') {
                        stockFields.classList.remove('hidden');
                    } else {
                        stockFields.classList.add('hidden');
                    }
                }

                function toggleRecurringFields() {
                    const isRecurring = document.getElementById('is_recurring').checked;
                    const recurringFields = document.getElementById('recurringFields');
                    if (isRecurring) {
                        recurringFields.classList.remove('hidden');
                    } else {
                        recurringFields.classList.add('hidden');
                    }
                }

                function toggleTrialFields() {
                    const hasTrial = document.querySelector('input[name="has_trial"]').checked;
                    const trialFields = document.getElementById('trialFields');
                    if (hasTrial) {
                        trialFields.classList.remove('hidden');
                    } else {
                        trialFields.classList.add('hidden');
                    }
                }
            </script>
            @endpush

            <div class="flex items-center justify-end space-x-4 pt-4 border-t">
                <a href="{{ route('products.index') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">Cancel</a>
                <button type="submit"
                    class="px-6 py-2 bg-primary text-white font-medium rounded-lg bg-primary-hover transition">
                    Update Product
                </button>
            </div>
        </form>
    </div>
@endsection