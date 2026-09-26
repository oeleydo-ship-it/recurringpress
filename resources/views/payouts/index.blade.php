@extends('layouts.app')

@section('title', 'Payouts - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Payouts')
@section('page-subtitle', 'Manage your earnings and request payouts')

@section('content')
<div class="mb-6 flex justify-end items-center">
    @if($availableBalance > 0)
    <a href="{{ route('payouts.create') }}" class="px-4 py-2 bg-primary text-white rounded-lg hover:opacity-90 transition">
        <i class="fas fa-plus mr-2"></i> Request Payout
    </a>
    @endif
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Available Balance</p>
                <p class="text-2xl font-bold text-green-600">{{ auth()->user()->company->currency_symbol }}{{ number_format($availableBalance, 2) }}</p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center">
                <i class="fas fa-wallet text-green-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Pending Payouts</p>
                <p class="text-2xl font-bold text-amber-600">{{ auth()->user()->company->currency_symbol }}{{ number_format($pendingPayouts, 2) }}</p>
            </div>
            <div class="w-12 h-12 bg-amber-100 rounded-full flex items-center justify-center">
                <i class="fas fa-clock text-amber-600"></i>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500">Total Paid Out</p>
                <p class="text-2xl font-bold text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($totalPaidOut, 2) }}</p>
            </div>
            <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                <i class="fas fa-check-circle text-gray-600"></i>
            </div>
        </div>
    </div>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-100">
        <h2 class="text-lg font-semibold text-gray-900">Payout History</h2>
    </div>

    @if($payouts->count() > 0)
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Payout #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Requested</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Processed</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($payouts as $payout)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-medium text-gray-900">{{ $payout->payout_number }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="font-semibold text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($payout->amount, 2) }}</span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($payout->status === 'completed')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                            <i class="fas fa-check-circle mr-1"></i> Completed
                        </span>
                        @elseif($payout->status === 'pending')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                            <i class="fas fa-clock mr-1"></i> Pending
                        </span>
                        @elseif($payout->status === 'processing')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            <i class="fas fa-spinner mr-1"></i> Processing
                        </span>
                        @elseif($payout->status === 'failed')
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                            <i class="fas fa-times-circle mr-1"></i> Failed
                        </span>
                        @else
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                            {{ ucfirst($payout->status) }}
                        </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $payout->requested_at ? $payout->requested_at->format('M d, Y') : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                        {{ $payout->processed_at ? $payout->processed_at->format('M d, Y') : '-' }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right">
                        <a href="{{ route('payouts.show', $payout) }}" class="text-primary hover:opacity-80">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="px-6 py-4 border-t border-gray-100">
        {{ $payouts->links() }}
    </div>
    @else
    <div class="px-6 py-12 text-center">
        <i class="fas fa-money-check-alt text-4xl text-gray-300 mb-4"></i>
        <p class="text-gray-500">No payouts yet</p>
        @if($availableBalance > 0)
        <a href="{{ route('payouts.create') }}" class="inline-block mt-4 text-primary hover:opacity-80">
            Request your first payout
        </a>
        @endif
    </div>
    @endif
</div>

<div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
    <div class="flex">
        <i class="fas fa-info-circle text-blue-600 mr-3 mt-1"></i>
        <div>
            <p class="font-medium text-blue-800">About Payouts</p>
            <ul class="text-sm text-blue-700 mt-2 space-y-1">
                <li>• Payouts are processed within 3-5 business days</li>
                <li>• Minimum payout amount is {{ auth()->user()->company->currency_symbol }}1.00</li>
                <li>• Funds are transferred to your bank account</li>
                <li>• Payouts are only available when using the app's payment gateway</li>
            </ul>
        </div>
    </div>
</div>
@endsection
