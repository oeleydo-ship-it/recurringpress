@extends('layouts.app')

@section('title', 'Reports')
@section('page-title', 'Reports')
@section('page-subtitle', 'Business analytics and insights')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end">
        <form action="{{ route('reports.index') }}" method="GET" class="mt-4 sm:mt-0">
            <select name="date_range" onchange="this.form.submit()" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                <option value="today" {{ $dateRange === 'today' ? 'selected' : '' }}>Today</option>
                <option value="yesterday" {{ $dateRange === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                <option value="this_week" {{ $dateRange === 'this_week' ? 'selected' : '' }}>This Week</option>
                <option value="last_week" {{ $dateRange === 'last_week' ? 'selected' : '' }}>Last Week</option>
                <option value="this_month" {{ $dateRange === 'this_month' ? 'selected' : '' }}>This Month</option>
                <option value="last_month" {{ $dateRange === 'last_month' ? 'selected' : '' }}>Last Month</option>
                <option value="this_quarter" {{ $dateRange === 'this_quarter' ? 'selected' : '' }}>This Quarter</option>
                <option value="last_quarter" {{ $dateRange === 'last_quarter' ? 'selected' : '' }}>Last Quarter</option>
                <option value="this_year" {{ $dateRange === 'this_year' ? 'selected' : '' }}>This Year</option>
                <option value="last_year" {{ $dateRange === 'last_year' ? 'selected' : '' }}>Last Year</option>
            </select>
        </form>
    </div>

    <!-- Key Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 bg-green-100 rounded-lg">
                    <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Period Revenue</p>
                    <p class="text-2xl font-bold text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($periodRevenue, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 bg-blue-100 rounded-lg">
                    <i class="fas fa-file-invoice text-blue-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Period Invoices</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $periodInvoices }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 bg-indigo-100 rounded-lg">
                    <i class="fas fa-credit-card text-indigo-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Period Payments</p>
                    <p class="text-2xl font-bold text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($periodPayments, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 bg-purple-100 rounded-lg">
                    <i class="fas fa-user-plus text-purple-600 text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">New Clients</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $newClients }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Profit & Expenses -->
    @php $sym = auth()->user()->company->currency_symbol; @endphp
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 bg-red-100 rounded-lg"><i class="fas fa-wallet text-red-600 text-xl"></i></div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Period Expenses</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $sym }}{{ number_format($periodExpenses, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 {{ $periodProfit >= 0 ? 'bg-emerald-100' : 'bg-red-100' }} rounded-lg">
                    <i class="fas fa-chart-line {{ $periodProfit >= 0 ? 'text-emerald-600' : 'text-red-600' }} text-xl"></i>
                </div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Period Profit</p>
                    <p class="text-2xl font-bold {{ $periodProfit >= 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $sym }}{{ number_format($periodProfit, 2) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 bg-primary-light rounded-lg"><i class="fas fa-percent text-primary text-xl"></i></div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Profit Margin</p>
                    <p class="text-2xl font-bold text-gray-900">{{ number_format($profitMargin, 1) }}%</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-6">
            <div class="flex items-center">
                <div class="p-3 bg-gray-100 rounded-lg"><i class="fas fa-piggy-bank text-gray-600 text-xl"></i></div>
                <div class="ml-4">
                    <p class="text-sm text-gray-500">Total Expenses (All-Time)</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $sym }}{{ number_format($totalExpenses, 2) }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Expenses by Category -->
    @if($expensesByCategory->count())
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-wallet mr-2 text-red-500"></i> Expenses by Category</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Count</th>
                            <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($expensesByCategory as $row)
                            <tr>
                                <td class="px-4 py-2">{{ $row->label }}</td>
                                <td class="px-4 py-2">{{ $row->count }}</td>
                                <td class="px-4 py-2 text-right font-medium">{{ $sym }}{{ number_format($row->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Invoice Overview -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-file-invoice mr-2 text-primary"></i> Invoice Summary</h2>
            <div class="grid grid-cols-2 gap-4">
                <div class="p-4 bg-green-50 rounded-lg text-center">
                    <p class="text-3xl font-bold text-green-600">{{ $invoiceStats['paid'] }}</p>
                    <p class="text-sm text-gray-600">Paid</p>
                </div>
                <div class="p-4 bg-yellow-50 rounded-lg text-center">
                    <p class="text-3xl font-bold text-yellow-600">{{ $invoiceStats['pending'] }}</p>
                    <p class="text-sm text-gray-600">Pending</p>
                </div>
                <div class="p-4 bg-red-50 rounded-lg text-center">
                    <p class="text-3xl font-bold text-red-600">{{ $invoiceStats['overdue'] }}</p>
                    <p class="text-sm text-gray-600">Overdue</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg text-center">
                    <p class="text-3xl font-bold text-gray-600">{{ $invoiceStats['draft'] }}</p>
                    <p class="text-sm text-gray-600">Draft</p>
                </div>
            </div>
            <div class="mt-4 pt-4 border-t">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">Total Invoiced</span>
                    <span class="font-semibold">{{ auth()->user()->company->currency_symbol }}{{ number_format($invoiceAmounts['total'], 2) }}</span>
                </div>
                <div class="flex justify-between text-sm mt-2">
                    <span class="text-gray-500">Total Collected</span>
                    <span class="font-semibold text-green-600">{{ auth()->user()->company->currency_symbol }}{{ number_format($invoiceAmounts['paid'], 2) }}</span>
                </div>
                <div class="flex justify-between text-sm mt-2">
                    <span class="text-gray-500">Outstanding</span>
                    <span class="font-semibold text-orange-600">{{ auth()->user()->company->currency_symbol }}{{ number_format($invoiceAmounts['outstanding'], 2) }}</span>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-credit-card mr-2 text-green-500"></i> Payment Methods</h2>
            @if($paymentMethods->count() > 0)
            <div class="space-y-4">
                @foreach($paymentMethods as $method)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        @php
                            $methodIcons = [
                                'stripe' => 'fab fa-stripe text-purple-600',
                                'bank_transfer' => 'fas fa-university text-blue-600',
                                'cash' => 'fas fa-money-bill text-green-600',
                                'check' => 'fas fa-money-check text-gray-600',
                                'manual' => 'fas fa-hand-holding-usd text-orange-600',
                            ];
                        @endphp
                        <i class="{{ $methodIcons[$method->payment_method] ?? 'fas fa-credit-card text-gray-600' }} text-xl mr-3"></i>
                        <div>
                            <p class="font-medium text-gray-900">{{ ucfirst(str_replace('_', ' ', $method->payment_method)) }}</p>
                            <p class="text-sm text-gray-500">{{ $method->count }} transactions</p>
                        </div>
                    </div>
                    <span class="font-semibold text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($method->total, 2) }}</span>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-credit-card text-4xl mb-3"></i>
                <p>No payment data available</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-chart-line mr-2 text-indigo-500"></i> Revenue (Last 12 Months)</h2>
        @if($revenueByMonth->count() > 0)
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
                <div class="w-full bg-primary rounded-t transition-all bg-primary-hover cursor-pointer" 
                     style="height: {{ max($height, 5) }}%"
                     title="{{ auth()->user()->company->currency_symbol }}{{ number_format($month->total, 2) }}"></div>
                <span class="text-xs text-gray-500 mt-2">{{ $monthName }}</span>
            </div>
            @endforeach
        </div>
        @else
        <div class="h-64 flex items-center justify-center text-gray-400">
            <div class="text-center">
                <i class="fas fa-chart-bar text-4xl mb-3"></i>
                <p>No revenue data available</p>
            </div>
        </div>
        @endif
    </div>

    <!-- Top Clients & Products -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-users mr-2 text-blue-500"></i> Top Clients</h2>
            @if($topClients->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2 text-xs font-medium text-gray-500 uppercase">Client</th>
                            <th class="text-right py-2 text-xs font-medium text-gray-500 uppercase">Invoices</th>
                            <th class="text-right py-2 text-xs font-medium text-gray-500 uppercase">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($topClients as $item)
                        <tr>
                            <td class="py-3">
                                <p class="font-medium text-gray-900">{{ $item->client->name ?? 'Unknown' }}</p>
                                <p class="text-xs text-gray-500">{{ $item->client->email ?? '' }}</p>
                            </td>
                            <td class="py-3 text-right text-gray-600">{{ $item->invoice_count }}</td>
                            <td class="py-3 text-right font-semibold text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($item->total_revenue, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-users text-4xl mb-3"></i>
                <p>No client data available</p>
            </div>
            @endif
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-box mr-2 text-orange-500"></i> Top Products</h2>
            @if($topProducts->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2 text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="text-right py-2 text-xs font-medium text-gray-500 uppercase">Qty Sold</th>
                            <th class="text-right py-2 text-xs font-medium text-gray-500 uppercase">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($topProducts as $product)
                        <tr>
                            <td class="py-3 font-medium text-gray-900">{{ Str::limit($product->name, 30) }}</td>
                            <td class="py-3 text-right text-gray-600">{{ number_format($product->quantity_sold) }}</td>
                            <td class="py-3 text-right font-semibold text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($product->revenue, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @else
            <div class="text-center py-8 text-gray-500">
                <i class="fas fa-box text-4xl mb-3"></i>
                <p>No product data available</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="bg-white rounded-xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4"><i class="fas fa-chart-pie mr-2 text-purple-500"></i> All-Time Statistics</h2>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-3xl font-bold text-gray-900">{{ $invoiceStats['total'] }}</p>
                <p class="text-sm text-gray-500">Total Invoices</p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-3xl font-bold text-gray-900">{{ $totalClients }}</p>
                <p class="text-sm text-gray-500">Total Clients</p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-2xl font-bold text-green-600">{{ auth()->user()->company->currency_symbol }}{{ number_format($totalPayments, 2) }}</p>
                <p class="text-sm text-gray-500">Total Collected</p>
            </div>
            <div class="text-center p-4 bg-gray-50 rounded-lg">
                <p class="text-2xl font-bold text-orange-600">{{ auth()->user()->company->currency_symbol }}{{ number_format($invoiceAmounts['outstanding'], 2) }}</p>
                <p class="text-sm text-gray-500">Outstanding</p>
            </div>
        </div>
    </div>
</div>
@endsection
