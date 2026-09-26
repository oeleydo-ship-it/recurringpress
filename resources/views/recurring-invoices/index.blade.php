@extends('layouts.app')

@section('title', 'Recurring Invoices - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Recurring Invoices')
@section('page-subtitle', 'Manage your subscription and recurring billing')

@section('content')
    <div class="flex items-center justify-end mb-6">
        <a href="{{ route('recurring-invoices.create') }}"
            class="inline-flex items-center px-4 py-2 bg-primary text-white font-medium rounded-lg bg-primary-hover transition">
            <i class="fas fa-plus mr-2"></i> New Recurring Invoice
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
        <div class="p-4 border-b border-gray-100">
            <form action="{{ route('recurring-invoices.index') }}" method="GET" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-[200px]">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search recurring invoices..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <select name="status"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">All Statuses</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ request('status') == $value ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="client_id"
                        class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">All Clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>{{ $client->select_label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'client_id']))
                    <a href="{{ route('recurring-invoices.index') }}"
                        class="px-4 py-2 text-gray-500 hover:text-gray-700">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cycle</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Invoice</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($recurringInvoices as $recurringInvoice)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('recurring-invoices.show', $recurringInvoice) }}"
                                    class="font-medium text-gray-900 text-primary-hover">
                                    {{ $recurringInvoice->name }}
                                </a>
                                <div class="text-sm text-gray-500">{{ $recurringInvoice->invoices_generated }} invoices generated</div>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('clients.show', $recurringInvoice->client) }}" class="text-gray-900 text-primary-hover">
                                    {{ $recurringInvoice->client->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ \App\Models\RecurringInvoice::billingCycles()[$recurringInvoice->billing_cycle] ?? $recurringInvoice->billing_cycle }}
                            </td>
                            <td class="px-6 py-4 text-gray-500">
                                {{ $recurringInvoice->next_invoice_date->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900">
                                {{ auth()->user()->company->currency_symbol }}{{ number_format($recurringInvoice->total, 2) }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $recurringInvoice->status_badge }}">
                                    {{ ucfirst($recurringInvoice->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('recurring-invoices.show', $recurringInvoice) }}"
                                        class="text-gray-400 hover:text-gray-600" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($recurringInvoice->status !== 'cancelled')
                                        <a href="{{ route('recurring-invoices.edit', $recurringInvoice) }}"
                                            class="text-gray-400 hover:text-gray-600" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-sync-alt text-4xl mb-4"></i>
                                <p>No recurring invoices found.</p>
                                <a href="{{ route('recurring-invoices.create') }}" class="text-primary text-primary-hover">
                                    Create your first recurring invoice
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($recurringInvoices->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $recurringInvoices->links() }}
            </div>
        @endif
    </div>
@endsection
