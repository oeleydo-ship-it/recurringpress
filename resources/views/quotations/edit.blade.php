@extends('layouts.app')

@section('title', 'Edit Quotation - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Edit ' . $quotation->quotation_number)

@section('content')
    <div class="mb-6">
        <a href="{{ route('quotations.show', $quotation) }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-2"></i> Back to Quotation
        </a>
    </div>

    <form action="{{ route('quotations.update', $quotation) }}" method="POST"
        x-data="{ ...quotationForm(), submitting: false }" @submit="submitting = true" class="space-y-6">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Quotation Details</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quotation Number</label>
                    <input type="text" name="quotation_number"
                        value="{{ old('quotation_number', $quotation->quotation_number) }}"
                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-primary focus:border-primary {{ $errors->has('quotation_number') ? 'border-red-300 text-red-900 placeholder-red-300 focus:ring-red-500 focus:border-red-500' : 'border-gray-300' }}">
                    @error('quotation_number')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label for="client_id" class="block text-sm font-medium text-gray-700">Client *</label>
                        @include('partials.quick-add-client')
                    </div>
                    <select name="client_id" id="client_id" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ old('client_id', $quotation->client_id) == $client->id ? 'selected' : '' }}>{{ $client->select_label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="issue_date" class="block text-sm font-medium text-gray-700 mb-1">Issue Date *</label>
                    <input type="date" name="issue_date" id="issue_date"
                        value="{{ old('issue_date', $quotation->issue_date->format('Y-m-d')) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="valid_until" class="block text-sm font-medium text-gray-700 mb-1">Valid Until *</label>
                    <input type="date" name="valid_until" id="valid_until"
                        value="{{ old('valid_until', $quotation->valid_until->format('Y-m-d')) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div class="lg:col-span-2">
                    <label for="reference" class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
                    <input type="text" name="reference" id="reference" value="{{ old('reference', $quotation->reference) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Line Items</h2>
                <button type="button" @click="addItem()" class="text-sm text-primary hover:opacity-80 font-medium">
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
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('notes', $quotation->notes) }}</textarea>
                    </div>

                    <div>
                        <label for="terms" class="block text-sm font-medium text-gray-700 mb-1">Terms & Conditions</label>
                        <textarea name="terms" id="terms" rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('terms', $quotation->terms) }}</textarea>
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
            <a href="{{ route('quotations.show', $quotation) }}"
                class="px-6 py-2 text-gray-700 hover:text-gray-900">Cancel</a>
            <button type="submit" :disabled="submitting"
                class="px-6 py-2 bg-primary text-white font-medium rounded-lg hover:opacity-90 transition disabled:opacity-50 disabled:cursor-not-allowed">
                <span x-show="!submitting">Update Quotation</span>
                <span x-show="submitting" class="flex items-center">
                    <i class="fas fa-spinner fa-spin mr-2"></i>Updating...
                </span>
            </button>
        </div>
    </form>
@endsection

@push('scripts')
    <script>
        function quotationForm() {
            return {
                items: @json($quotation->items->map(fn($i) => ['name' => $i->name, 'description' => $i->description, 'quantity' => $i->quantity, 'price' => $i->price, 'tax_rate' => $i->tax_rate, 'unit' => $i->unit])),
                discount: {{ $quotation->discount_amount }},
                discountType: '{{ $quotation->discount_type }}',
                subtotal: 0,
                taxAmount: 0,
                total: 0,

                init() {
                    this.calculateTotal();
                },

                addItem() {
                    this.items.push({ name: '', description: '', quantity: 1, price: 0, tax_rate: 0, unit: 'unit' });
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