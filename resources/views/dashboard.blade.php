@extends('layouts.app')

@section('title', 'Dashboard - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Dashboard')
@section('page-subtitle', 'Welcome back, ' . auth()->user()->name . '!')

@section('content')
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Subscription Usage Widget -->
        @if(isset($subscriptionUsage))
            <div class="md:col-span-2 lg:col-span-4 bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-2">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-gray-900">Subscription Usage ({{ $plan->name ?? 'Free' }})</h2>
                    <a href="{{ route('settings.subscription') }}"
                        class="text-sm text-primary text-primary-hover font-medium">Manage Plan</a>
                </div>
                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Invoices Created</span>
                            <span class="font-medium text-gray-900">{{ $subscriptionUsage['invoices']['used'] }} /
                                {{ $subscriptionUsage['invoices']['limit'] }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div class="bg-primary h-2.5 rounded-full transition-all duration-500"
                                style="width: {{ $subscriptionUsage['invoices']['percent'] }}%"></div>
                        </div>
                        @if($subscriptionUsage['invoices']['percent'] >= 90)
                            <p class="text-xs text-red-500 mt-1">You are approaching your invoice limit.</p>
                        @endif
                    </div>
                    <div>
                        <div class="flex justify-between text-sm mb-2">
                            <span class="text-gray-600">Clients Added</span>
                            <span class="font-medium text-gray-900">{{ $subscriptionUsage['clients']['used'] }} /
                                {{ $subscriptionUsage['clients']['limit'] }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-2.5">
                            <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-500"
                                style="width: {{ $subscriptionUsage['clients']['percent'] }}%"></div>
                        </div>
                        @if($subscriptionUsage['clients']['percent'] >= 90)
                            <p class="text-xs text-red-500 mt-1">You are approaching your client limit.</p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ auth()->user()->company->currency_symbol }}{{ number_format($stats['total_revenue'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-dollar-sign text-green-600 text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-green-600 mt-2">
                <i class="fas fa-check-circle mr-1"></i> From paid invoices
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Pending Amount</p>
                    <p class="text-2xl font-bold text-gray-900">
                        {{ auth()->user()->company->currency_symbol }}{{ number_format($stats['pending_amount'], 2) }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-clock text-yellow-600 text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-yellow-600 mt-2">
                <i class="fas fa-exclamation-circle mr-1"></i> {{ $stats['overdue_invoices'] }} overdue
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Invoices</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_invoices'] }}</p>
                </div>
                <div class="w-12 h-12 bg-primary-light rounded-lg flex items-center justify-center">
                    <i class="fas fa-file-invoice text-primary text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                <i class="fas fa-edit mr-1"></i> {{ $stats['draft_invoices'] }} drafts
            </p>
        </div>

        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500">Total Clients</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $stats['total_clients'] }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="fas fa-users text-blue-600 text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-2">
                <i class="fas fa-box mr-1"></i> {{ $stats['total_products'] }} products
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">Revenue Overview</h2>
            </div>
            <div class="p-6">
                <canvas id="revenueChart" height="120"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-900">Invoice Status</h2>
            </div>
            <div class="p-6">
                <canvas id="statusChart" height="200"></canvas>
            </div>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-lg font-semibold text-gray-900">Recent Invoices</h2>
            <a href="{{ route('invoices.index') }}" class="text-primary text-primary-hover text-sm font-medium">
                View all <i class="fas fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recentInvoices as $invoice)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('invoices.show', $invoice) }}"
                                    class="font-medium text-primary text-primary-hover">
                                    {{ $invoice->invoice_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $invoice->client->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 font-medium text-gray-900">
                                {{ auth()->user()->company->currency_symbol }}{{ number_format($invoice->total, 2) }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $invoice->status_badge }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-500 text-sm">{{ $invoice->issue_date->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-file-invoice text-4xl text-gray-300 mb-3"></i>
                                <p>No invoices yet</p>
                                <a href="{{ route('invoices.create') }}"
                                    class="text-primary text-primary-hover text-sm font-medium">
                                    Create your first invoice
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const chartData = @json($chartData);
        const currencySymbol = @json(auth()->user()->company->currency_symbol);

        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Revenue',
                    data: chartData,
                    backgroundColor: 'rgba(99, 102, 241, 0.8)',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, ticks: { callback: value => currencySymbol + value } }
                }
            }
        });

        const statusData = @json($statusDistribution);
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: ['Draft', 'Sent', 'Paid', 'Overdue', 'Cancelled'],
                datasets: [{
                    data: [
                        statusData.draft || 0,
                        statusData.sent || 0,
                        statusData.paid || 0,
                        statusData.overdue || 0,
                        statusData.cancelled || 0
                    ],
                    backgroundColor: ['#9CA3AF', '#3B82F6', '#10B981', '#EF4444', '#F59E0B'],
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    </script>
@endpush