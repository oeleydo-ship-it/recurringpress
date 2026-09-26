@extends('layouts.app')

@section('title', 'Create Recurring Invoice - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Create Recurring Invoice')
@section('page-subtitle', 'Set up automatic recurring billing for your clients')

@section('content')
    <div class="mb-6">
        <a href="{{ route('recurring-invoices.index') }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-2"></i> Back to Recurring Invoices
        </a>
    </div>

    <form action="{{ route('recurring-invoices.store') }}" method="POST" x-data="recurringInvoiceForm()" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Subscription Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div class="lg:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Subscription Name *</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required
                        placeholder="e.g., Monthly Hosting Fee, Annual Support"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="client_id" class="block text-sm font-medium text-gray-700">Client *</label>
                        @include('partials.quick-add-client')
                    </div>
                    <select name="client_id" id="client_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Select Client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id', request('client_id')) == $client->id ? 'selected' : '' }}>{{ $client->select_label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="billing_cycle" class="block text-sm font-medium text-gray-700 mb-1">Billing Cycle *</label>
                    <select name="billing_cycle" id="billing_cycle" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        @foreach($billingCycles as $value => $label)
                            <option value="{{ $value }}" {{ old('billing_cycle', 'monthly') == $value ? 'selected' : '' }}>
                                {{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
                    <input type="date" name="start_date" id="start_date" value="{{ old('start_date', date('Y-m-d')) }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date (Optional)</label>
                    <input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <p class="text-xs text-gray-500 mt-1">Leave empty for indefinite billing</p>
                </div>

                <div>
                    <label for="billing_cycle_count" class="block text-sm font-medium text-gray-700 mb-1">Number of Cycles
                        (Optional)</label>
                    <input type="number" name="billing_cycle_count" id="billing_cycle_count"
                        value="{{ old('billing_cycle_count') }}" min="1" placeholder="e.g., 12"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <p class="text-xs text-gray-500 mt-1">Leave empty for unlimited cycles</p>
                </div>

                <div class="flex items-center">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="auto_send" value="1" {{ old('auto_send') ? 'checked' : '' }}
                            class="rounded border-gray-300 text-primary shadow-sm focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">Auto-send invoices when generated</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Line Items</h2>
                <button type="button" @click="addItem()"
                    class="text-sm text-primary text-primary-hover font-medium">
                    <i class="fas fa-plus mr-1"></i> Add Item
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase w-1/3">Description
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Unit</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tax %</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="(item, index) in items" :key="index">
                            <tr>
                                <td class="px-4 py-3">
                                    <input type="text" :name="'items['+index+'][name]'" x-model="item.name" required
                                        placeholder="Item name"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                                    <input type="text" :name="'items['+index+'][description]'" x-model="item.description"
                                        placeholder="Description (optional)"
                                        class="w-full mt-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" :name="'items['+index+'][quantity]'" x-model="item.quantity"
                                        @input="calculateTotal()" step="0.01" min="0.01" required
                                        class="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                                </td>
                                <td class="px-4 py-3">
                                    <select :name="'items['+index+'][unit]'" x-model="item.unit"
                                        class="w-24 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                                        <option value="unit">Unit</option>
                                        <option value="hour">Hour</option>
                                        <option value="day">Day</option>
                                        <option value="month">Month</option>
                                        <option value="project">Project</option>
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" :name="'items['+index+'][price]'" x-model="item.price"
                                        @input="calculateTotal()" step="0.01" min="0" required
                                        class="w-28 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                                </td>
                                <td class="px-4 py-3">
                                    <input type="number" :name="'items['+index+'][tax_rate]'" x-model="item.tax_rate"
                                        @input="calculateTotal()" step="0.01" min="0" max="100"
                                        class="w-20 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                                </td>
                                <td class="px-4 py-3 font-medium text-gray-900"
                                    x-text="currencySymbol + itemTotal(item).toFixed(2)"></td>
                                <td class="px-4 py-3">
                                    <button type="button" @click="removeItem(index)" x-show="items.length > 1"
                                        class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <label class="text-sm text-gray-600">Quick add from products:</label>
                    <select @change="addFromProduct($event.target.value); $event.target.value = ''"
                        class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                        <option value="">Select product...</option>
                        @foreach($products as $product)
                            <option value="{{ json_encode($product) }}">
                                {{ $product->name }} -
                                {{ auth()->user()->company->currency_symbol }}{{ number_format($product->price, 2) }}
                                @if($product->is_recurring) (Recurring: {{ $product->billing_cycle_label }}) @endif
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Notes & Terms</h2>

                <div class="space-y-4">
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" id="notes" rows="3" placeholder="Notes visible to client..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('notes', $settings->default_notes) }}</textarea>
                    </div>

                    <div>
                        <label for="terms" class="block text-sm font-medium text-gray-700 mb-1">Terms & Conditions</label>
                        <textarea name="terms" id="terms" rows="3" placeholder="Payment terms..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('terms', $settings->default_terms) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Summary (Per Cycle)</h2>

                <div class="space-y-3">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span x-text="currencySymbol + subtotal.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Tax</span>
                        <span x-text="currencySymbol + taxAmount.toFixed(2)"></span>
                    </div>
                    <div class="flex items-center justify-between text-gray-600">
                        <span>Discount</span>
                        <div class="flex items-center space-x-2">
                            <input type="number" name="discount_amount" x-model="discount" @input="calculateTotal()"
                                step="0.01" min="0"
                                class="w-24 px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                            <select name="discount_type" x-model="discountType" @change="calculateTotal()"
                                class="px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                                <option value="fixed">{{ auth()->user()->company->currency_symbol }}</option>
                                <option value="percentage">%</option>
                            </select>
                        </div>
                    </div>
                    <hr>
                    <div class="flex justify-between text-lg font-bold text-gray-900">
                        <span>Total per Cycle</span>
                        <span x-text="currencySymbol + total.toFixed(2)"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-4">
            <a href="{{ route('recurring-invoices.index') }}" class="px-6 py-2 text-gray-700 hover:text-gray-900">Cancel</a>
            <button type="submit"
                class="px-6 py-2 bg-primary text-white font-medium rounded-lg bg-primary-hover transition">
                Create Recurring Invoice
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        function recurringInvoiceForm() {
            const defaultTaxRate = {{ auth()->user()->company->settings->default_tax_rate ?? 0 }};
            return {
                items: [{ name: '', description: '', quantity: 1, price: 0, tax_rate: defaultTaxRate, unit: 'unit' }],
                discount: 0,
                discountType: 'fixed',
                subtotal: 0,
                taxAmount: 0,
                total: 0,
                currencySymbol: '{{ auth()->user()->company->currency_symbol }}',
                defaultTaxRate: defaultTaxRate,

                addItem() {
                    this.items.push({ name: '', description: '', quantity: 1, price: 0, tax_rate: this.defaultTaxRate, unit: 'unit' });
                },

                removeItem(index) {
                    this.items.splice(index, 1);
                    this.calculateTotal();
                },

                addFromProduct(productJson) {
                    if (!productJson) return;
                    const product = JSON.parse(productJson);
                    this.items.push({
                        name: product.name,
                        description: product.description || '',
                        quantity: 1,
                        price: parseFloat(product.price),
                        tax_rate: parseFloat(product.tax_rate),
                        unit: product.unit,
                        product_id: product.id
                    });
                    this.calculateTotal();
                },

                itemTotal(item) {
                    const subtotal = parseFloat(item.quantity || 0) * parseFloat(item.price || 0);
                    const tax = subtotal * (parseFloat(item.tax_rate || 0) / 100);
                    return subtotal + tax;
                },

                calculateTotal() {
                    this.subtotal = this.items.reduce((sum, item) => {
                        return sum + (parseFloat(item.quantity || 0) * parseFloat(item.price || 0));
                    }, 0);

                    this.taxAmount = this.items.reduce((sum, item) => {
                        const itemSubtotal = parseFloat(item.quantity || 0) * parseFloat(item.price || 0);
                        return sum + (itemSubtotal * (parseFloat(item.tax_rate || 0) / 100));
                    }, 0);

                    let discountAmount = parseFloat(this.discount || 0);
                    if (this.discountType === 'percentage') {
                        discountAmount = this.subtotal * (discountAmount / 100);
                    }

                    this.total = this.subtotal + this.taxAmount - discountAmount;
                }
            }
        }
    </script>
@endpush