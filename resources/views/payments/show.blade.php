@extends('layouts.app')

@section('title', 'Payment Details - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Payment Details')

@section('content')
<div class="flex justify-between items-start mb-6">
    <div>
        <a href="{{ route('payments.index') }}" class="text-gray-500 hover:text-gray-700 mb-2 inline-block">
            <i class="fas fa-arrow-left mr-2"></i> Back to Transactions
        </a>
        <h1 class="text-2xl font-bold text-gray-900">Payment {{ $payment->payment_number ?? '#' . $payment->id }}</h1>
        <p class="text-gray-600">{{ $payment->payment_date?->format('F d, Y') ?? $payment->created_at->format('F d, Y') }}</p>
    </div>
    <div class="flex items-center space-x-3">
        @if($payment->receipt)
        <a href="{{ route('payments.receipt', $payment) }}" class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
            <i class="fas fa-receipt mr-2"></i> Download Receipt
        </a>
        @endif
        @if($payment->canRefund())
        <button type="button" onclick="openRefundModal()" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
            <i class="fas fa-undo mr-2"></i> Refund
        </button>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <!-- Payment Details -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Payment Details</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Payment Number</p>
                    <p class="font-medium text-gray-900">{{ $payment->payment_number ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Status</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $payment->status_badge }}">
                        {{ ucfirst(str_replace('_', ' ', $payment->status)) }}
                    </span>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Amount</p>
                    <p class="font-medium text-gray-900 text-lg">{{ $payment->company->currency_symbol ?? '$' }}{{ number_format($payment->amount, 2) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Payment Method</p>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                        <i class="fas fa-{{ $payment->payment_method === 'cash' ? 'money-bill' : ($payment->payment_method === 'bank_transfer' ? 'university' : ($payment->payment_method === 'credit_card' ? 'credit-card' : 'wallet')) }} mr-1"></i>
                        {{ $payment->payment_method_label }}
                    </span>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Transaction ID</p>
                    <p class="font-medium text-gray-900">{{ $payment->transaction_id ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Payment Date</p>
                    <p class="font-medium text-gray-900">{{ $payment->payment_date?->format('M d, Y H:i') ?? $payment->created_at->format('M d, Y H:i') }}</p>
                </div>
            </div>

            @if($payment->notes)
            <div class="mt-4 pt-4 border-t border-gray-100">
                <p class="text-sm text-gray-500">Notes</p>
                <p class="text-gray-700">{{ $payment->notes }}</p>
            </div>
            @endif
        </div>

        <!-- Refund Information -->
        @if($payment->refunded_amount > 0)
        <div class="bg-red-50 rounded-xl border border-red-200 p-6">
            <h2 class="text-lg font-semibold text-red-900 mb-4">Refund Information</h2>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-sm text-red-600">Refunded Amount</p>
                    <p class="font-medium text-red-900 text-lg">{{ $payment->company->currency_symbol ?? '$' }}{{ number_format($payment->refunded_amount, 2) }}</p>
                </div>
                <div>
                    <p class="text-sm text-red-600">Refund Date</p>
                    <p class="font-medium text-red-900">{{ $payment->refunded_at?->format('M d, Y H:i') }}</p>
                </div>
                @if($payment->refund_reason)
                <div class="col-span-2">
                    <p class="text-sm text-red-600">Reason</p>
                    <p class="text-red-800">{{ $payment->refund_reason }}</p>
                </div>
                @endif
            </div>
        </div>
        @endif

        <!-- Invoice Items -->
        @if($payment->invoice && $payment->invoice->items->count())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Invoice Items</h2>
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($payment->invoice->items as $item)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-gray-900">{{ $item->name }}</p>
                            @if($item->description)
                            <p class="text-sm text-gray-500">{{ $item->description }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center text-gray-600">{{ $item->quantity }}</td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ $payment->company->currency_symbol ?? '$' }}{{ number_format($item->price, 2) }}</td>
                        <td class="px-4 py-3 text-right font-medium text-gray-900">{{ $payment->company->currency_symbol ?? '$' }}{{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif
    </div>

    <div class="space-y-6">
        <!-- Client Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Client</h2>
            @if($payment->client)
            <div class="flex items-center space-x-3 mb-4">
                <div class="w-12 h-12 bg-primary-light rounded-full flex items-center justify-center">
                    <span class="text-primary font-semibold text-lg">{{ strtoupper(substr($payment->client->name, 0, 2)) }}</span>
                </div>
                <div>
                    <p class="font-medium text-gray-900">{{ $payment->client->name }}</p>
                    <p class="text-sm text-gray-500">{{ $payment->client->email }}</p>
                </div>
            </div>
            <a href="{{ route('clients.show', $payment->client) }}" class="text-primary text-primary-hover text-sm">
                View Client <i class="fas fa-arrow-right ml-1"></i>
            </a>
            @else
            <p class="text-gray-500">No client information</p>
            @endif
        </div>

        <!-- Invoice Information -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Invoice</h2>
            @if($payment->invoice)
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-500">Invoice #</span>
                    <a href="{{ route('invoices.show', $payment->invoice) }}" class="font-medium text-primary text-primary-hover">
                        {{ $payment->invoice->invoice_number }}
                    </a>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Invoice Total</span>
                    <span class="font-medium">{{ $payment->company->currency_symbol ?? '$' }}{{ number_format($payment->invoice->total, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Status</span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $payment->invoice->status_badge }}">
                        {{ ucfirst($payment->invoice->status) }}
                    </span>
                </div>
            </div>
            @else
            <p class="text-gray-500">No invoice linked</p>
            @endif
        </div>

        <!-- Receipt -->
        @if($payment->receipt)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Receipt</h2>
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-500">Receipt #</span>
                    <span class="font-medium">{{ $payment->receipt->receipt_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-500">Issued</span>
                    <span class="font-medium">{{ $payment->receipt->issued_at->format('M d, Y') }}</span>
                </div>
            </div>
            <a href="{{ route('payments.receipt', $payment) }}" class="mt-4 inline-flex items-center text-primary text-primary-hover text-sm">
                <i class="fas fa-download mr-2"></i> Download Receipt
            </a>
        </div>
        @endif
    </div>
</div>

<!-- Refund Modal -->
@if($payment->canRefund())
<div id="refundModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4">
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Process Refund</h3>
        </div>
        <form action="{{ route('payments.refund', $payment) }}" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Refund Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500">{{ $payment->company->currency_symbol ?? '$' }}</span>
                        <input type="number" name="amount" step="0.01" min="0.01" max="{{ $payment->remaining_refundable }}" value="{{ $payment->remaining_refundable }}" required
                               class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Maximum refundable: {{ $payment->company->currency_symbol ?? '$' }}{{ number_format($payment->remaining_refundable, 2) }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Refund</label>
                    <textarea name="reason" rows="3" required
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary"
                              placeholder="Enter reason for refund..."></textarea>
                </div>
            </div>
            <div class="p-6 border-t border-gray-100 flex justify-end space-x-3">
                <button type="button" onclick="closeRefundModal()" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Process Refund
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
    function openRefundModal() {
        document.getElementById('refundModal').classList.remove('hidden');
    }
    function closeRefundModal() {
        document.getElementById('refundModal').classList.add('hidden');
    }
</script>
@endpush
@endif
@endsection
