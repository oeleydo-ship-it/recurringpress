@extends('layouts.app')

@section('title', 'Create Invoice - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Create New Invoice')

@section('content')
    <div class="mb-6">
        <a href="{{ route('invoices.index') }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-2"></i> Back to Invoices
        </a>
    </div>

    <form action="{{ route('invoices.store') }}" method="POST" x-data="invoiceForm()" class="space-y-6">
        @csrf

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Invoice Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <label for="invoice_number" class="block text-sm font-medium text-gray-700 mb-1">Invoice Number
                        *</label>
                    <input type="text" name="invoice_number" id="invoice_number"
                        value="{{ old('invoice_number', $nextNumber) }}" required
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
                    <label for="issue_date" class="block text-sm font-medium text-gray-700 mb-1">Issue Date *</label>
                    <input type="date" name="issue_date" id="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}"
                        required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">Due Date *</label>
                    <input type="date" name="due_date" id="due_date"
                        value="{{ old('due_date', date('Y-m-d', strtotime('+30 days'))) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div class="lg:col-span-2">
                    <label for="reference" class="block text-sm font-medium text-gray-700 mb-1">Reference / PO
                        Number</label>
                    <input type="text" name="reference" id="reference" value="{{ old('reference') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Line Items</h2>
                <div class="flex items-center space-x-4">
                    <div class="relative" x-data="{ 
                                open: false, 
                                search: '',
                                products: {{ Js::from($products->map(fn($p) => ['name' => $p->name, 'description' => $p->description, 'price' => $p->price, 'unit' => $p->unit, 'tax_rate' => $p->tax_rate, 'sku' => $p->sku])) }},
                                get filteredProducts() {
                                    if (this.search.trim() === '') return [];
                                    return this.products.filter(p => 
                                        p.name.toLowerCase().includes(this.search.toLowerCase())
                                    ).slice(0, 5);
                                }
                            }">
                        <button type="button" @click="open = !open; if(open) $nextTick(() => $refs.searchInput.focus())"
                            class="text-sm text-primary text-primary-hover font-medium">
                            <i class="fas fa-box mr-1"></i> Add from Products
                        </button>
                        <div x-show="open" @click.away="open = false; search = ''" x-cloak
                            class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-lg border border-gray-200 z-50">
                            <div class="p-3 border-b">
                                <input type="text" x-ref="searchInput" x-model="search"
                                    placeholder="Type to search products..."
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-primary focus:border-primary">
                            </div>
                            <div class="max-h-64 overflow-y-auto">
                                <!-- Show message when no search query -->
                                <template x-if="search.trim() === ''">
                                    <div class="px-4 py-3 text-gray-500 text-sm text-center">
                                        <i class="fas fa-search mr-1"></i> Start typing to search products
                                    </div>
                                </template>

                                <!-- Show filtered products -->
                                <template x-if="search.trim() !== '' && filteredProducts.length > 0">
                                    <div>
                                        <template x-for="(product, idx) in filteredProducts" :key="idx">
                                            <button type="button"
                                                @click="addFromProduct(JSON.stringify(product)); open = false; search = ''"
                                                class="w-full px-4 py-3 text-left hover:bg-gray-50 border-b border-gray-100 last:border-0">
                                                <div class="font-medium text-gray-900" x-text="product.name"></div>
                                                <div class="text-sm text-gray-500 flex justify-between">
                                                    <span x-text="product.sku || 'No SKU'"></span>
                                                    <span class="font-medium text-primary"
                                                        x-text="'{{ auth()->user()->company->currency_symbol }}' + parseFloat(product.price).toFixed(2)"></span>
                                                </div>
                                            </button>
                                        </template>
                                    </div>
                                </template>

                                <!-- Show no results message -->
                                <template x-if="search.trim() !== '' && filteredProducts.length === 0">
                                    <div class="px-4 py-3 text-gray-500 text-sm text-center">
                                        <i class="fas fa-info-circle mr-1"></i> No products found matching "<span
                                            x-text="search"></span>"
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                    <button type="button" @click="addItem()"
                        class="text-sm text-primary text-primary-hover font-medium">
                        <i class="fas fa-plus mr-1"></i> Add Item
                    </button>
                </div>
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

            @if($products->where('is_recurring', true)->count() > 0)
                <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">
                        <i class="fas fa-sync-alt text-primary mr-1"></i>
                        <strong>Tip:</strong> For recurring/subscription products, consider creating a
                        <a href="{{ route('recurring-invoices.create') }}"
                            class="text-primary text-primary-hover underline">recurring invoice</a>
                        to automatically generate invoices on a schedule.
                    </p>
                </div>
            @endif
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
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Summary</h2>

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
                        <span>Total</span>
                        <span x-text="currencySymbol + total.toFixed(2)"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-4">
            <a href="{{ route('invoices.index') }}" class="px-6 py-2 text-gray-700 hover:text-gray-900">Cancel</a>
            <button type="submit"
                class="px-6 py-2 bg-primary text-white font-medium rounded-lg bg-primary-hover transition">
                Create Invoice
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        function invoiceForm() {
            const defaultTaxRate = {{ optional(auth()->user()->company->settings)->default_tax_rate ?? 0 }};
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
                        tax_rate: parseFloat(product.tax_rate) || this.defaultTaxRate,
                        unit: product.unit || 'unit'
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