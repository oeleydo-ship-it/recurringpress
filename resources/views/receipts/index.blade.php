@extends('layouts.app')

@section('title', 'Receipts - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Receipts')
@section('page-subtitle', 'View payment receipts for completed transactions')

@section('content')

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <form action="{{ route('receipts.index') }}" method="GET" class="flex gap-4">
            <div class="flex-1">
                <input type="text" name="search" value="{{ request('search') }}" 
                       placeholder="Search receipts, clients, invoices..." 
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>
            <button type="submit" class="px-4 py-2 bg-primary text-white rounded-lg hover:opacity-90 transition">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>

    @if($receipts->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Receipt #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payment Method</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($receipts as $receipt)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-medium text-gray-900">{{ $receipt->receipt_number }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-gray-900">{{ $receipt->client->name ?? '-' }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($receipt->invoice)
                        <a href="{{ route('invoices.show', $receipt->invoice) }}" class="text-primary hover:opacity-80">
                            {{ $receipt->invoice->invoice_number }}
                        </a>
                        @else
                        <span class="text-gray-500">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-semibold text-green-600">{{ auth()->user()->company->currency_symbol }}{{ number_format($receipt->amount, 2) }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            @if($receipt->payment_method === 'stripe')
                            <i class="fab fa-stripe mr-1"></i>
                            @elseif($receipt->payment_method === 'bank_transfer')
                            <i class="fas fa-university mr-1"></i>
                            @else
                            <i class="fas fa-money-bill mr-1"></i>
                            @endif
                            {{ ucfirst(str_replace('_', ' ', $receipt->payment_method ?? 'Unknown')) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $receipt->issued_at ? $receipt->issued_at->format('M d, Y') : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                        <a href="{{ route('receipts.show', $receipt) }}" class="text-primary hover:opacity-80" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="{{ route('receipts.download', $receipt) }}" class="text-gray-600 hover:text-gray-900" title="Download PDF">
                            <i class="fas fa-download"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="px-6 py-4 border-t border-gray-100">
        {{ $receipts->links() }}
    </div>
    @else
    <div class="px-6 py-12 text-center">
        <i class="fas fa-receipt text-4xl text-gray-300 mb-4"></i>
        <p class="text-gray-500">No receipts found</p>
        <p class="text-sm text-gray-400 mt-1">Receipts are automatically generated when payments are received</p>
    </div>
    @endif
</div>
@endsection
