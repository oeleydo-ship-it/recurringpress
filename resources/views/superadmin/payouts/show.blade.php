@extends('superadmin.layout')

@section('title', 'Payout Details')

@section('content')
<div class="max-w-4xl space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('superadmin.payouts.index') }}" class="text-gray-600 hover:text-gray-900">
            <i class="fas fa-arrow-left mr-2"></i> Back to Payouts
        </a>
        @php
            $statusColors = [
                'pending' => 'bg-yellow-100 text-yellow-800',
                'processing' => 'bg-blue-100 text-blue-800',
                'completed' => 'bg-green-100 text-green-800',
                'failed' => 'bg-red-100 text-red-800',
                'cancelled' => 'bg-gray-100 text-gray-800',
            ];
        @endphp
        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$payout->status] ?? 'bg-gray-100 text-gray-800' }}">
            {{ ucfirst($payout->status) }}
        </span>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $payout->payout_number }}</h2>
                    <p class="text-gray-500">Requested {{ $payout->requested_at ? $payout->requested_at->format('F d, Y \a\t h:i A') : $payout->created_at->format('F d, Y \a\t h:i A') }}</p>
                </div>
                <div class="text-right">
                    <p class="text-3xl font-bold text-gray-900">{{ $payout->currency }} {{ number_format($payout->amount, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Company Details</h3>
                <div class="space-y-3">
                    <div>
                        <p class="text-sm text-gray-500">Company Name</p>
                        <p class="font-medium text-gray-900">{{ $payout->company->name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Email</p>
                        <p class="font-medium text-gray-900">{{ $payout->company->email ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Payout Method</h3>
                @if($payout->payout_method === 'paypal')
                <div class="p-4 bg-blue-50 rounded-lg">
                    <div class="flex items-center mb-3">
                        <i class="fab fa-paypal text-2xl text-blue-600 mr-3"></i>
                        <span class="font-semibold text-blue-800">PayPal</span>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">PayPal Email</p>
                        <p class="font-medium text-gray-900">{{ $payout->paypal_email ?? 'N/A' }}</p>
                    </div>
                </div>
                @else
                <div class="p-4 bg-gray-50 rounded-lg space-y-3">
                    <div class="flex items-center mb-3">
                        <i class="fas fa-university text-2xl text-gray-600 mr-3"></i>
                        <span class="font-semibold text-gray-800">Bank Transfer</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <p class="text-sm text-gray-500">Bank Name</p>
                            <p class="font-medium text-gray-900">{{ $payout->bank_name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500">Account Name</p>
                            <p class="font-medium text-gray-900">{{ $payout->bank_account_name ?? 'N/A' }}</p>
                        </div>
                        @if($payout->bank_iban)
                        <div>
                            <p class="text-sm text-gray-500">IBAN</p>
                            <p class="font-medium text-gray-900 font-mono">{{ $payout->bank_iban }}</p>
                        </div>
                        @endif
                        @if($payout->bank_swift)
                        <div>
                            <p class="text-sm text-gray-500">SWIFT/BIC</p>
                            <p class="font-medium text-gray-900 font-mono">{{ $payout->bank_swift }}</p>
                        </div>
                        @endif
                        @if($payout->bank_account_number)
                        <div>
                            <p class="text-sm text-gray-500">Account Number</p>
                            <p class="font-medium text-gray-900 font-mono">{{ $payout->bank_account_number }}</p>
                        </div>
                        @endif
                        @if($payout->bank_routing_number)
                        <div>
                            <p class="text-sm text-gray-500">Routing Number</p>
                            <p class="font-medium text-gray-900 font-mono">{{ $payout->bank_routing_number }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>
        </div>

        @if($payout->notes)
        <div class="px-6 pb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Company Notes</h3>
            <p class="text-gray-700 bg-gray-50 p-4 rounded-lg">{{ $payout->notes }}</p>
        </div>
        @endif

        @if($payout->payments->count() > 0)
        <div class="px-6 pb-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Related Payments ({{ $payout->payments->count() }})</h3>
            <div class="overflow-x-auto border rounded-lg">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment #</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($payout->payments as $payment)
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $payment->payment_number }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $payment->invoice->invoice_number ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $payment->client->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-900 text-right font-medium">{{ $payment->currency }} {{ number_format($payment->amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif
    </div>

    @if(in_array($payout->status, ['pending', 'processing']))
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Process Payout</h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <form action="{{ route('superadmin.payouts.complete', $payout) }}" method="POST" class="p-4 border border-green-200 rounded-lg bg-green-50">
                @csrf
                <h4 class="font-medium text-green-800 mb-4"><i class="fas fa-check-circle mr-2"></i> Mark as Completed</h4>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Transaction ID (optional)</label>
                        <input type="text" name="transaction_id" placeholder="e.g., TXN123456789"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Admin Notes (optional)</label>
                        <textarea name="admin_notes" rows="2" placeholder="Any notes about the payout..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500"></textarea>
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-check mr-2"></i> Complete Payout
                    </button>
                </div>
            </form>

            <form action="{{ route('superadmin.payouts.reject', $payout) }}" method="POST" class="p-4 border border-red-200 rounded-lg bg-red-50">
                @csrf
                <h4 class="font-medium text-red-800 mb-4"><i class="fas fa-times-circle mr-2"></i> Reject Payout</h4>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Rejection <span class="text-red-500">*</span></label>
                        <textarea name="admin_notes" rows="4" required placeholder="Please provide a reason for rejecting this payout..."
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"></textarea>
                    </div>
                    <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition"
                        onclick="return confirm('Are you sure you want to reject this payout? The payments will be released back to the company\'s available balance.')">
                        <i class="fas fa-times mr-2"></i> Reject Payout
                    </button>
                </div>
            </form>
        </div>

        @if($payout->status === 'pending')
        <div class="mt-4 pt-4 border-t">
            <form action="{{ route('superadmin.payouts.process', $payout) }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                    <i class="fas fa-spinner mr-2"></i> Mark as Processing
                </button>
            </form>
        </div>
        @endif
    </div>
    @endif

    @if($payout->status === 'completed')
    <div class="bg-green-50 border border-green-200 rounded-xl p-6">
        <div class="flex items-start">
            <div class="p-2 bg-green-100 rounded-lg">
                <i class="fas fa-check-circle text-green-600 text-2xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="font-semibold text-green-800">Payout Completed</h3>
                <p class="text-green-700 mt-1">Processed on {{ $payout->processed_at ? $payout->processed_at->format('F d, Y \a\t h:i A') : 'N/A' }}</p>
                @if($payout->transaction_id)
                <p class="text-green-700 mt-1">Transaction ID: <span class="font-mono">{{ $payout->transaction_id }}</span></p>
                @endif
                @if($payout->admin_notes)
                <p class="text-green-700 mt-2"><strong>Notes:</strong> {{ $payout->admin_notes }}</p>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if($payout->status === 'failed')
    <div class="bg-red-50 border border-red-200 rounded-xl p-6">
        <div class="flex items-start">
            <div class="p-2 bg-red-100 rounded-lg">
                <i class="fas fa-times-circle text-red-600 text-2xl"></i>
            </div>
            <div class="ml-4">
                <h3 class="font-semibold text-red-800">Payout Rejected</h3>
                <p class="text-red-700 mt-1">Rejected on {{ $payout->processed_at ? $payout->processed_at->format('F d, Y \a\t h:i A') : 'N/A' }}</p>
                @if($payout->admin_notes)
                <p class="text-red-700 mt-2"><strong>Reason:</strong> {{ $payout->admin_notes }}</p>
                @endif
            </div>
        </div>
    </div>
    @endif
</div>
@endsection
