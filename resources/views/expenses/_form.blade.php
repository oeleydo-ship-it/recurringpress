@csrf
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
        <input type="text" name="title" value="{{ old('title', $expense->title ?? '') }}" required
            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
        <select name="category" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            @foreach($categories as $key => $label)
                <option value="{{ $key }}" {{ old('category', $expense->category ?? 'other') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Expense Date <span class="text-red-500">*</span></label>
        <input type="date" name="expense_date" required
            value="{{ old('expense_date', isset($expense) ? $expense->expense_date->format('Y-m-d') : now()->format('Y-m-d')) }}"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Amount <span class="text-red-500">*</span></label>
        <input type="number" step="0.01" min="0.01" name="amount" required
            value="{{ old('amount', $expense->amount ?? '') }}"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Tax Amount</label>
        <input type="number" step="0.01" min="0" name="tax_amount"
            value="{{ old('tax_amount', $expense->tax_amount ?? 0) }}"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Vendor</label>
        <input type="text" name="vendor" value="{{ old('vendor', $expense->vendor ?? '') }}"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
        <select name="payment_method" required class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            @foreach($paymentMethods as $key => $label)
                <option value="{{ $key }}" {{ old('payment_method', $expense->payment_method ?? 'cash') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Reference</label>
        <input type="text" name="reference" value="{{ old('reference', $expense->reference ?? '') }}"
            class="w-full px-4 py-2 border border-gray-300 rounded-lg">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Related Invoice</label>
        <select name="invoice_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
            <option value="">— None —</option>
            @foreach($invoices as $inv)
                <option value="{{ $inv->id }}" {{ old('invoice_id', $expense->invoice_id ?? '') == $inv->id ? 'selected' : '' }}>
                    {{ $inv->invoice_number }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="md:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea name="description" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg">{{ old('description', $expense->description ?? '') }}</textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Receipt (JPG/PNG/PDF, max 5MB)</label>
        <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.pdf" class="w-full text-sm">
        @if(isset($expense) && $expense->receipt_path)
            <a href="{{ asset('storage/' . $expense->receipt_path) }}" target="_blank" class="text-sky-600 text-sm mt-1 inline-block">View current receipt</a>
        @endif
    </div>

    <div class="flex items-center space-x-6 pt-6">
        <label class="inline-flex items-center">
            <input type="checkbox" name="is_billable" value="1" {{ old('is_billable', $expense->is_billable ?? false) ? 'checked' : '' }}>
            <span class="ml-2 text-sm">Billable to client</span>
        </label>
        <label class="inline-flex items-center">
            <input type="checkbox" name="is_reimbursed" value="1" {{ old('is_reimbursed', $expense->is_reimbursed ?? false) ? 'checked' : '' }}>
            <span class="ml-2 text-sm">Reimbursed</span>
        </label>
    </div>
</div>

<div class="mt-6 flex justify-end space-x-3">
    <a href="{{ route('expenses.index') }}" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">Cancel</a>
    <button type="submit" class="px-4 py-2 bg-sky-500 text-white rounded-lg hover:bg-sky-600">Save</button>
</div>
