@extends('layouts.app')

@section('title', 'Transactions - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Transactions')
@section('page-subtitle', 'Manage payments and refunds')

@section('content')

<div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
    <div class="p-4 border-b border-gray-100">
        <form action="{{ route('payments.index') }}" method="GET" class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[200px]">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search payments..."
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
            <div>
                <select name="status" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    <option value="">All Status</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                    <option value="refunded" {{ request('status') === 'refunded' ? 'selected' : '' }}>Refunded</option>
                    <option value="partially_refunded" {{ request('status') === 'partially_refunded' ? 'selected' : '' }}>Partially Refunded</option>
                    <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                </select>
            </div>
            <div>
                <select name="payment_method" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    <option value="">All Methods</option>
                    @foreach($paymentMethods as $value => $label)
                    <option value="{{ $value }}" {{ request('payment_method') === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:opacity-90 transition">
                    <i class="fas fa-search mr-2"></i> Filter
                </button>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Method</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($payments as $payment)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <a href="{{ route('payments.show', $payment) }}" class="font-medium text-primary text-primary-hover">
                            {{ $payment->payment_number ?? 'N/A' }}
                        </a>
                    </td>
                    <td class="px-6 py-4">
                        @if($payment->invoice)
                        <a href="{{ route('invoices.show', $payment->invoice) }}" class="text-gray-600 text-primary-hover">
                            {{ $payment->invoice->invoice_number }}
                        </a>
                        @else
                        <span class="text-gray-400">N/A</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $payment->client->name ?? 'N/A' }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            <i class="fas fa-{{ $payment->payment_method === 'cash' ? 'money-bill' : ($payment->payment_method === 'bank_transfer' ? 'university' : ($payment->payment_method === 'credit_card' ? 'credit-card' : ($payment->payment_method === 'stripe' ? 'stripe-s' : 'wallet'))) }} mr-1"></i>
                            {{ $payment->payment_method_label }}
                        </span>
                    </td>
                    <td class="px-6 py-4 font-medium text-gray-900">
                        {{ auth()->user()->company->currency_symbol }}{{ number_format($payment->amount, 2) }}
                        @if($payment->refunded_amount > 0)
                        <div class="text-xs text-red-600">-{{ auth()->user()->company->currency_symbol }}{{ number_format($payment->refunded_amount, 2) }} refunded</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-gray-600">{{ $payment->payment_date?->format('M d, Y') ?? $payment->created_at->format('M d, Y') }}</td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $payment->status_badge }}">
                            {{ ucfirst(str_replace('_', ' ', $payment->status)) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end space-x-2">
                            <a href="{{ route('payments.show', $payment) }}" class="p-2 text-gray-400 text-primary-hover" title="View">
                                <i class="fas fa-eye"></i>
                            </a>
                            @if($payment->receipt)
                            <a href="{{ route('payments.receipt', $payment) }}" class="p-2 text-gray-400 hover:text-green-600" title="Download Receipt">
                                <i class="fas fa-receipt"></i>
                            </a>
                            @endif
                            @if($payment->canRefund())
                            <button type="button" onclick="openRefundModal({{ $payment->id }}, {{ $payment->remaining_refundable }})" class="p-2 text-gray-400 hover:text-red-600" title="Refund">
                                <i class="fas fa-undo"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                        <i class="fas fa-receipt text-4xl mb-4"></i>
                        <p>No transactions found</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($payments->hasPages())
    <div class="px-6 py-4 border-t border-gray-100">
        {{ $payments->links() }}
    </div>
    @endif
</div>

<!-- Refund Modal -->
<div id="refundModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md mx-4">
        <div class="p-6 border-b border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900">Process Refund</h3>
        </div>
        <form id="refundForm" method="POST">
            @csrf
            <div class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Refund Amount</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-500">{{ auth()->user()->company->currency_symbol }}</span>
                        <input type="number" name="amount" id="refundAmount" step="0.01" min="0.01" required
                               class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Maximum refundable: <span id="maxRefundable"></span></p>
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
    function openRefundModal(paymentId, maxRefundable) {
        document.getElementById('refundForm').action = `/payments/${paymentId}/refund`;
        document.getElementById('refundAmount').max = maxRefundable;
        document.getElementById('refundAmount').value = maxRefundable;
        document.getElementById('maxRefundable').textContent = '{{ auth()->user()->company->currency_symbol }}' + maxRefundable.toFixed(2);
        document.getElementById('refundModal').classList.remove('hidden');
    }

    function closeRefundModal() {
        document.getElementById('refundModal').classList.add('hidden');
    }
</script>
@endpush
@endsection
