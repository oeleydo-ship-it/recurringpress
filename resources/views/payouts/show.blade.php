@extends('layouts.app')

@section('title', 'Payout Details - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Payout Details')

@section('content')
<div class="mb-6">
    <a href="{{ route('payouts.index') }}" class="text-primary hover:opacity-80">
        <i class="fas fa-arrow-left mr-2"></i> Back to Payouts
    </a>
</div>

<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex justify-between items-center">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $payout->payout_number }}</h1>
                <p class="text-gray-600 text-sm">Payout Request Details</p>
            </div>
            @if($payout->status === 'completed')
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                <i class="fas fa-check-circle mr-1"></i> Completed
            </span>
            @elseif($payout->status === 'pending')
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-amber-100 text-amber-800">
                <i class="fas fa-clock mr-1"></i> Pending
            </span>
            @elseif($payout->status === 'processing')
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                <i class="fas fa-spinner mr-1"></i> Processing
            </span>
            @elseif($payout->status === 'failed')
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                <i class="fas fa-times-circle mr-1"></i> Failed
            </span>
            @endif
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-500 mb-1">Payout Amount</p>
                    <p class="text-2xl font-bold text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($payout->amount, 2) }}</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-500 mb-1">Request Date</p>
                    <p class="text-lg font-semibold text-gray-900">{{ $payout->requested_at ? $payout->requested_at->format('F d, Y') : '-' }}</p>
                </div>
            </div>

            <div class="border-t pt-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Bank Account Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Bank Name</p>
                        <p class="font-medium text-gray-900">{{ $payout->bank_name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Account Holder</p>
                        <p class="font-medium text-gray-900">{{ $payout->bank_account_name ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Account Number</p>
                        <p class="font-medium text-gray-900 font-mono">{{ $payout->bank_account_number ?? '-' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Routing Number</p>
                        <p class="font-medium text-gray-900 font-mono">{{ $payout->bank_routing_number ?? '-' }}</p>
                    </div>
                </div>
            </div>

            @if($payout->transaction_id)
            <div class="border-t pt-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Transaction Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Transaction ID</p>
                        <p class="font-medium text-gray-900 font-mono">{{ $payout->transaction_id }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Processed Date</p>
                        <p class="font-medium text-gray-900">{{ $payout->processed_at ? $payout->processed_at->format('F d, Y') : '-' }}</p>
                    </div>
                </div>
            </div>
            @endif

            @if($payout->notes)
            <div class="border-t pt-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Notes</h3>
                <p class="text-gray-600">{{ $payout->notes }}</p>
            </div>
            @endif

            @if($payout->payments->count() > 0)
            <div class="border-t pt-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Included Payments</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Payment #</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($payout->payments as $payment)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $payment->payment_number }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $payment->invoice->invoice_number ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $payment->client->name ?? '-' }}</td>
                                <td class="px-4 py-3 text-sm text-right font-medium text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($payment->amount, 2) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $payment->payment_date ? $payment->payment_date->format('M d, Y') : '-' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
