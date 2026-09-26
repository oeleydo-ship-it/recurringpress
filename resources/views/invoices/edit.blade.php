@extends('layouts.app')

@section('title', 'Edit Invoice - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Edit Invoice ' . $invoice->invoice_number)

@section('content')
    <div class="mb-6">
        <a href="{{ route('invoices.show', $invoice) }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-2"></i> Back to Invoice
        </a>
    </div>

    <form action="{{ route('invoices.update', $invoice) }}" method="POST" x-data="invoiceForm()" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Invoice Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Invoice Number</label>
                    <input type="text" value="{{ $invoice->invoice_number }}" disabled
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-50 text-gray-500">
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="client_id" class="block text-sm font-medium text-gray-700">Client *</label>
                        @include('partials.quick-add-client')
                    </div>
                    <select name="client_id" id="client_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id', $invoice->client_id) == $client->id ? 'selected' : '' }}>{{ $client->select_label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="issue_date" class="block text-sm font-medium text-gray-700 mb-1">Issue Date *</label>
                    <input type="date" name="issue_date" id="issue_date"
                        value="{{ old('issue_date', $invoice->issue_date->format('Y-m-d')) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="due_date" class="block text-sm font-medium text-gray-700 mb-1">Due Date *</label>
                    <input type="date" name="due_date" id="due_date"
                        value="{{ old('due_date', $invoice->due_date->format('Y-m-d')) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div class="lg:col-span-2">
                    <label for="reference" class="block text-sm font-medium text-gray-700 mb-1">Reference / PO
                        Number</label>
                    <input type="text" name="reference" id="reference" value="{{ old('reference', $invoice->reference) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Line Items</h2>
                <div class="flex items-center space-x-4">
                    <select @change="addProduct($event)"
                        class="text-sm border-gray-300 rounded-lg focus:ring-primary focus:border-primary py-1 pl-3 pr-8">
                        <option value="">Select Product to Add...</option>
                        <template x-for="product in products" :key="product.id">
                            <option :value="product.id"
                                x-text="product.name + ' (' + '$' + parseFloat(product.price).toFixed(2) + ')'"></option>
                        </template>
                    </select>
                    <button type="button" @click="addItem()"
                        class="text-sm text-primary text-primary-hover font-medium whitespace-nowrap">
                        <i class="fas fa-plus mr-1"></i> Add Blank Item
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
                                    <input type="hidden" :name="'items['+index+'][product_id]'" x-model="item.product_id">
                                    <input type="text" :name="'items['+index+'][name]'" x-model="item.name" required
                                        placeholder="Item name"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                                    <input type="text" :name="'items['+index+'][description]'" x-model="item.description"
                                        placeholder="Description"
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
                                <td class="px-4 py-3 font-medium text-gray-900" x-text="'$' + itemTotal(item).toFixed(2)">
                                </td>
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
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Notes & Terms</h2>

                <div class="space-y-4">
                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea name="notes" id="notes" rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('notes', $invoice->notes) }}</textarea>
                    </div>

                    <div>
                        <label for="terms" class="block text-sm font-medium text-gray-700 mb-1">Terms & Conditions</label>
                        <textarea name="terms" id="terms" rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('terms', $invoice->terms) }}</textarea>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Summary</h2>

                <div class="space-y-3">
                    <div class="flex justify-between text-gray-600">
                        <span>Subtotal</span>
                        <span x-text="'$' + subtotal.toFixed(2)"></span>
                    </div>
                    <div class="flex justify-between text-gray-600">
                        <span>Tax</span>
                        <span x-text="'$' + taxAmount.toFixed(2)"></span>
                    </div>
                    <div class="flex items-center justify-between text-gray-600">
                        <span>Discount</span>
                        <div class="flex items-center space-x-2">
                            <input type="number" name="discount_amount" x-model="discount" @input="calculateTotal()"
                                step="0.01" min="0"
                                class="w-24 px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                            <select name="discount_type" x-model="discountType" @change="calculateTotal()"
                                class="px-3 py-1 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary text-sm">
                                <option value="fixed">$</option>
                                <option value="percentage">%</option>
                            </select>
                        </div>
                    </div>
                    <hr>
                    <div class="flex justify-between text-lg font-bold text-gray-900">
                        <span>Total</span>
                        <span x-text="'$' + total.toFixed(2)"></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-4">
            <a href="{{ route('invoices.show', $invoice) }}" class="px-6 py-2 text-gray-700 hover:text-gray-900">Cancel</a>
            <button type="submit"
                class="px-6 py-2 bg-primary text-white font-medium rounded-lg bg-primary-hover transition">
                Update Invoice
            </button>
        </div>
    </form>
@endsection

@php
    $invoiceItems = $invoice->items->map(function ($i) {
        return [
            'product_id' => $i->product_id,
            'name' => $i->name,
            'description' => $i->description,
            'quantity' => $i->quantity,
            'price' => $i->price,
            'tax_rate' => $i->tax_rate,
            'unit' => $i->unit
        ];
    });
@endphp

@push('scripts')
    <script>
        function invoiceForm() {
            return {
                items: @json($invoiceItems),
                products: @json($products),
                discount: {{ $invoice->discount_amount }},
                discountType: '{{ $invoice->discount_type }}',
                subtotal: 0,
                taxAmount: 0,
                total: 0,
                init() {
                    this.calculateTotal();
                },

                addItem() {
                    this.items.push({ product_id: null, name: '', description: '', quantity: 1, price: 0, tax_rate: 0, unit: 'unit' });
                },

                addProduct(event) {
                    const productId = event.target.value;
                    if (!productId) return;

                    const product = this.products.find(p => p.id == productId);
                    if (product) {
                        this.items.push({
                            product_id: product.id,
                            name: product.name,
                            description: product.description || '',
                            quantity: 1,
                            price: product.price,
                            tax_rate: product.tax_rate || 0,
                            unit: product.unit || 'unit'
                        });
                        this.calculateTotal();
                    }

                    // Reset the select dropdown
                    event.target.value = "";
                },

                removeItem(index) {
                    this.items.splice(index, 1);
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