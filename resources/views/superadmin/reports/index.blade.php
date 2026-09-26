@extends('superadmin.layout')

@section('title', 'Reports')

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <p class="text-gray-600">Platform analytics and business metrics</p>
        </div>
        <form action="{{ route('superadmin.reports.index') }}" method="GET" class="flex items-center gap-2">
            <select name="date_range" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                <option value="today" {{ $dateRange === 'today' ? 'selected' : '' }}>Today</option>
                <option value="yesterday" {{ $dateRange === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                <option value="this_week" {{ $dateRange === 'this_week' ? 'selected' : '' }}>This Week</option>
                <option value="last_week" {{ $dateRange === 'last_week' ? 'selected' : '' }}>Last Week</option>
                <option value="this_month" {{ $dateRange === 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="last_month" {{ $dateRange === 'last_month' ? 'selected' : '' }}>Last Month</option>
                <option value="this_year" {{ $dateRange === 'this_year' ? 'selected' : '' }}>This Year</option>
                <option value="last_year" {{ $dateRange === 'last_year' ? 'selected' : '' }}>Last Year</option>
            </select>
        </form>
    </div>

    <!-- Users/Companies Stats -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-users mr-2 text-sky-500"></i> Users & Companies</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-4 bg-sky-50 rounded-lg">
                <p class="text-sm text-gray-500">Total Companies</p>
                <p class="text-3xl font-bold text-sky-600">{{ number_format($totalCompanies) }}</p>
            </div>
            <div class="p-4 bg-green-50 rounded-lg">
                <p class="text-sm text-gray-500">New (Selected Period)</p>
                <p class="text-3xl font-bold text-green-600">{{ number_format($newCompanies) }}</p>
            </div>
            <div class="p-4 bg-indigo-50 rounded-lg">
                <p class="text-sm text-gray-500">Active Subscriptions</p>
                <p class="text-3xl font-bold text-indigo-600">{{ number_format($activeCompanies) }}</p>
            </div>
        </div>
    </div>

    <!-- Subscription Stats -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-crown mr-2 text-yellow-500"></i> Subscriptions</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="p-4 bg-emerald-50 rounded-lg">
                <p class="text-sm text-gray-500">Monthly Recurring Revenue (MRR)</p>
                <p class="text-3xl font-bold text-emerald-600">${{ number_format($totalMRR, 2) }}</p>
            </div>
            <div class="p-4 bg-purple-50 rounded-lg">
                <p class="text-sm text-gray-500">Annual Recurring Revenue (ARR)</p>
                <p class="text-3xl font-bold text-purple-600">${{ number_format($totalARR, 2) }}</p>
            </div>
        </div>
        
        <h3 class="font-medium text-gray-700 mb-3">Subscribers by Plan</h3>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Billing</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Active Subscribers</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($subscriptionsByPlan as $plan)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $plan->name }}</td>
                        <td class="px-4 py-3 text-gray-600">${{ number_format($plan->price, 2) }}</td>
                        <td class="px-4 py-3 text-gray-600">{{ ucfirst($plan->billing_cycle ?? 'monthly') }}</td>
                        <td class="px-4 py-3 text-right">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-sky-100 text-sky-800">
                                {{ $plan->companies_count }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payment Stats -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-credit-card mr-2 text-green-500"></i> Payments (Via App Gateway)</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="p-4 bg-green-50 rounded-lg">
                <p class="text-sm text-gray-500">Total All Time</p>
                <p class="text-2xl font-bold text-green-600">${{ number_format($totalPayments, 2) }}</p>
            </div>
            <div class="p-4 bg-blue-50 rounded-lg">
                <p class="text-sm text-gray-500">Selected Period</p>
                <p class="text-2xl font-bold text-blue-600">${{ number_format($periodPayments, 2) }}</p>
            </div>
            <div class="p-4 bg-indigo-50 rounded-lg">
                <p class="text-sm text-gray-500">Transactions (Period)</p>
                <p class="text-2xl font-bold text-indigo-600">{{ number_format($paymentCount) }}</p>
            </div>
            <div class="p-4 bg-orange-50 rounded-lg">
                <p class="text-sm text-gray-500">Pending Payouts</p>
                <p class="text-2xl font-bold text-orange-600">${{ number_format($pendingPayouts, 2) }}</p>
            </div>
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-chart-line mr-2 text-indigo-500"></i> Revenue (Last 12 Months)</h2>
        <div class="h-64 flex items-end justify-between gap-2">
            @php
                $maxRevenue = $revenueByMonth->max('total') ?: 1;
            @endphp
            @foreach($revenueByMonth as $month)
            @php
                $height = ($month->total / $maxRevenue) * 100;
                $monthName = \Carbon\Carbon::createFromDate($month->year, $month->month, 1)->format('M');
            @endphp
            <div class="flex-1 flex flex-col items-center">
                <div class="w-full bg-sky-500 rounded-t transition-all hover:bg-sky-600" style="height: {{ max($height, 5) }}%"
                    title="${{ number_format($month->total, 2) }}"></div>
                <span class="text-xs text-gray-500 mt-2">{{ $monthName }}</span>
            </div>
            @endforeach
            @if($revenueByMonth->isEmpty())
            <div class="flex-1 flex items-center justify-center text-gray-400">
                <p>No revenue data available</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Top Companies -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-trophy mr-2 text-yellow-500"></i> Top Companies by Payment Volume</h2>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Company</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Transactions</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total Volume</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($topCompanies as $index => $item)
                    <tr>
                        <td class="px-4 py-3 text-gray-500">{{ $index + 1 }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium text-gray-900">{{ $item->company->name ?? 'Unknown' }}</div>
                            <div class="text-sm text-gray-500">{{ $item->company->email ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3 text-right text-gray-600">{{ number_format($item->payment_count) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-gray-900">${{ number_format($item->total_payments, 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-gray-500">No payment data available</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Payout Summary -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-money-check-alt mr-2 text-emerald-500"></i> Payout Summary</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="p-4 bg-emerald-50 rounded-lg">
                <p class="text-sm text-gray-500">Total Paid Out</p>
                <p class="text-2xl font-bold text-emerald-600">${{ number_format($totalPayouts, 2) }}</p>
            </div>
            <div class="p-4 bg-orange-50 rounded-lg">
                <p class="text-sm text-gray-500">Pending Payouts</p>
                <p class="text-2xl font-bold text-orange-600">${{ number_format($pendingPayouts, 2) }}</p>
            </div>
            <div class="p-4 bg-sky-50 rounded-lg">
                <p class="text-sm text-gray-500">Platform Revenue</p>
                <p class="text-2xl font-bold text-sky-600">${{ number_format($totalPayments - $totalPayouts, 2) }}</p>
                <p class="text-xs text-gray-500 mt-1">Total Payments - Payouts</p>
            </div>
        </div>
    </div>
</div>
@endsection
