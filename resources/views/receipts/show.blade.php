@extends('layouts.app')

@section('title', 'Receipt ' . $receipt->receipt_number . ' - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Receipt ' . $receipt->receipt_number)

@section('content')
<div class="mb-6 flex justify-between items-center">
    <a href="{{ route('receipts.index') }}" class="text-primary hover:opacity-80">
        <i class="fas fa-arrow-left mr-2"></i> Back to Receipts
    </a>
    <a href="{{ route('receipts.download', $receipt) }}" class="px-4 py-2 bg-primary text-white rounded-lg hover:opacity-90 transition">
        <i class="fas fa-download mr-2"></i> Download PDF
    </a>
</div>

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="bg-green-500 px-8 py-6 text-white">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-receipt text-4xl mr-4"></i>
                    <div>
                        <h1 class="text-2xl font-bold">Payment Receipt</h1>
                        <p class="text-green-100">{{ $receipt->receipt_number }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-green-100 text-sm">Date Issued</p>
                    <p class="text-xl font-semibold">{{ $receipt->issued_at ? $receipt->issued_at->format('M d, Y') : '-' }}</p>
                </div>
            </div>
        </div>

        <div class="p-8">
            <div class="grid grid-cols-2 gap-8 mb-8">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">From</h3>
                    <p class="font-semibold text-gray-900">{{ $receipt->company->name ?? 'Company' }}</p>
                    @if($receipt->company->address)
                    <p class="text-gray-600 text-sm">{{ $receipt->company->address }}</p>
                    @endif
                    @if($receipt->company->phone)
                    <p class="text-gray-600 text-sm">{{ $receipt->company->phone }}</p>
                    @endif
                    @if($receipt->company->email)
                    <p class="text-gray-600 text-sm">{{ $receipt->company->email }}</p>
                    @endif
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Bill To</h3>
                    <p class="font-semibold text-gray-900">{{ $receipt->client->name ?? 'Client' }}</p>
                    @if($receipt->client->address)
                    <p class="text-gray-600 text-sm">{{ $receipt->client->address }}</p>
                    @endif
                    @if($receipt->client->email)
                    <p class="text-gray-600 text-sm">{{ $receipt->client->email }}</p>
                    @endif
                </div>
            </div>

            <div class="border-t border-b border-gray-200 py-6 mb-6">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Invoice Reference</p>
                        @if($receipt->invoice)
                        <p class="font-medium text-gray-900">
                            <a href="{{ route('invoices.show', $receipt->invoice) }}" class="text-primary hover:opacity-80">
                                {{ $receipt->invoice->invoice_number }}
                            </a>
                        </p>
                        @else
                        <p class="font-medium text-gray-900">-</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Payment Method</p>
                        <p class="font-medium text-gray-900">
                            @if($receipt->payment_method === 'stripe')
                            <i class="fab fa-stripe mr-1"></i> Card Payment (Stripe)
                            @elseif($receipt->payment_method === 'bank_transfer')
                            <i class="fas fa-university mr-1"></i> Bank Transfer
                            @elseif($receipt->payment_method === 'cash')
                            <i class="fas fa-money-bill mr-1"></i> Cash
                            @else
                            {{ ucfirst(str_replace('_', ' ', $receipt->payment_method ?? 'Unknown')) }}
                            @endif
                        </p>
                    </div>
                    @if($receipt->payment && $receipt->payment->transaction_id)
                    <div class="col-span-2">
                        <p class="text-sm text-gray-500">Transaction ID</p>
                        <p class="font-medium text-gray-900 font-mono text-sm">{{ $receipt->payment->transaction_id }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <div class="flex justify-between items-center p-6 bg-green-50 rounded-lg">
                <div>
                    <p class="text-sm text-gray-500">Amount Paid</p>
                    <p class="text-3xl font-bold text-green-600">{{ auth()->user()->company->currency_symbol }}{{ number_format($receipt->amount, 2) }}</p>
                </div>
                <div class="text-right">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <i class="fas fa-check-circle mr-1"></i> Payment Received
                    </span>
                </div>
            </div>

            @if($receipt->notes)
            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <p class="text-sm font-medium text-gray-500 mb-1">Notes</p>
                <p class="text-gray-700">{{ $receipt->notes }}</p>
            </div>
            @endif
        </div>

        <div class="px-8 py-4 bg-gray-50 border-t border-gray-100 text-center text-sm text-gray-500">
            Thank you for your payment!
        </div>
    </div>
</div>
@endsection
