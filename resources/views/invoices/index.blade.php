@extends('layouts.app')

@section('title', 'Invoices - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Invoices')
@section('page-subtitle', 'Manage and track all your invoices')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3 mb-6">
        <a href="{{ route('invoices.export', request()->query()) }}"
            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
            <i class="fas fa-download mr-2"></i> Export CSV
        </a>
        <a href="{{ route('invoices.create') }}"
            class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg bg-primary-hover transition">
            <i class="fas fa-plus mr-2"></i> New Invoice
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-4 border-b border-gray-100">
            <form action="{{ route('invoices.index') }}" method="GET"
                class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-4">
                <div>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search invoices..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <div>
                    <select name="status"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">All Status</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}" {{ request('status') === $value ? 'selected' : '' }}>{{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="payment_status"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">Paid & Unpaid</option>
                        <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid Only</option>
                        <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid Only</option>
                    </select>
                </div>
                <div>
                    <select name="client_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        <option value="">All Clients</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                                {{ $client->select_label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" placeholder="From date"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <div class="flex space-x-2">
                    <input type="date" name="date_to" value="{{ request('date_to') }}" placeholder="To date"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <button type="submit"
                        class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Issue Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('invoices.show', $invoice) }}"
                                    class="font-medium text-primary text-primary-hover">
                                    {{ $invoice->invoice_number }}
                                </a>
                                @if($invoice->reference)
                                    <div class="text-sm text-gray-400">{{ $invoice->reference }}</div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $invoice->client->name ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $invoice->issue_date->format('M d, Y') }}</td>
                            <td class="px-6 py-4">
                                <span class="{{ $invoice->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                                    {{ $invoice->due_date->format('M d, Y') }}
                                </span>
                            </td>
                            <td class="px-6 py-4 font-medium text-gray-900">
                                {{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}</td>
                            <td class="px-6 py-4">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $invoice->status_badge }}">
                                    {{ ucfirst($invoice->status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('invoices.show', $invoice) }}"
                                        class="p-2 text-gray-400 text-primary-hover" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($invoice->status !== 'paid')
                                        <a href="{{ route('invoices.edit', $invoice) }}"
                                            class="p-2 text-gray-400 text-primary-hover" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('invoices.download', $invoice) }}"
                                        class="p-2 text-gray-400 text-primary-hover" title="Download PDF">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    @if($invoice->status === 'draft')
                                        <form action="{{ route('invoices.destroy', $invoice) }}" method="POST" class="inline"
                                            onsubmit="return confirm('Are you sure?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-gray-400 hover:text-red-600" title="Delete">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-file-invoice text-4xl text-gray-300 mb-3"></i>
                                <p>No invoices found</p>
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

        @if($invoices->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>
@endsection