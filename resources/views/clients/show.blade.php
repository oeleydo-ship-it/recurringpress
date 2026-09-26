@extends('layouts.app')

@section('title', $client->name . ' - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', $client->name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('clients.index') }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-2"></i> Back to Clients
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-start justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $client->name }}</h1>
                        @if($client->isCompany() && $client->company_name)
                            <p class="text-gray-500 mt-1"><i class="fas fa-building mr-1.5"></i>{{ $client->company_name }}</p>
                        @endif
                        <div class="flex items-center gap-2 mt-2">
                            @if($client->is_active)
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                            @else
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                            @endif
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-light text-primary-dark">
                                <i class="fas {{ $client->isCompany() ? 'fa-building' : 'fa-user' }} mr-1"></i>
                                {{ $client->isCompany() ? 'Company' : 'Individual' }}
                            </span>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <a href="{{ route('invoices.create', ['client_id' => $client->id]) }}"
                            class="px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg bg-primary-hover transition">
                            <i class="fas fa-plus mr-2"></i> New Invoice
                        </a>
                        <a href="{{ route('clients.edit', $client) }}"
                            class="px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition">
                            <i class="fas fa-edit mr-2"></i> Edit
                        </a>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Contact Information</h3>
                        <div class="space-y-2">
                            @if($client->email)
                                <p class="flex items-center text-gray-700">
                                    <i class="fas fa-envelope w-5 text-gray-400"></i>
                                    <a href="mailto:{{ $client->email }}"
                                        class="ml-2 text-primary-hover">{{ $client->email }}</a>
                                </p>
                            @endif
                            @if($client->phone)
                                <p class="flex items-center text-gray-700">
                                    <i class="fas fa-phone w-5 text-gray-400"></i>
                                    <span class="ml-2">{{ $client->phone }}</span>
                                </p>
                            @endif
                            @if($client->tax_number)
                                <p class="flex items-center text-gray-700">
                                    <i class="fas fa-id-card w-5 text-gray-400"></i>
                                    <span class="ml-2">{{ $client->tax_number }}</span>
                                </p>
                            @endif
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Address</h3>
                        @if($client->full_address)
                            <p class="text-gray-700">{{ $client->full_address }}</p>
                        @else
                            <p class="text-gray-400">No address provided</p>
                        @endif
                    </div>

                    @if($client->notes)
                        <div class="md:col-span-2">
                            <h3 class="text-sm font-medium text-gray-500 mb-2">Notes</h3>
                            <p class="text-gray-700">{{ $client->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 mt-6">
                <div class="p-6 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Invoices</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($client->invoices as $invoice)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('invoices.show', $invoice) }}"
                                            class="font-medium text-primary text-primary-hover">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-gray-600">{{ $invoice->issue_date->format('M d, Y') }}</td>
                                    <td class="px-6 py-4 font-medium text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($invoice->total, 2) }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $invoice->status_badge }}">
                                            {{ ucfirst($invoice->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-8 text-center text-gray-500">
                                        No invoices yet
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Statistics</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Invoices</span>
                        <span class="font-semibold text-gray-900">{{ $stats['total_invoices'] }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Billed</span>
                        <span class="font-semibold text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($stats['total_billed'], 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Paid</span>
                        <span class="font-semibold text-green-600">{{ auth()->user()->company->currency_symbol }}{{ number_format($stats['total_paid'], 2) }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Outstanding</span>
                        <span class="font-semibold text-yellow-600">{{ auth()->user()->company->currency_symbol }}{{ number_format($stats['outstanding'], 2) }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
                <div class="space-y-2">
                    <a href="{{ route('invoices.create', ['client_id' => $client->id]) }}"
                        class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition">
                        <i class="fas fa-file-invoice w-5 text-primary"></i>
                        <span class="ml-3 text-gray-700">Create Invoice</span>
                    </a>
                    @if($client->email)
                        <a href="mailto:{{ $client->email }}"
                            class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition">
                            <i class="fas fa-envelope w-5 text-primary"></i>
                            <span class="ml-3 text-gray-700">Send Email</span>
                        </a>
                    @endif
                    <a href="{{ route('clients.edit', $client) }}"
                        class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition">
                        <i class="fas fa-edit w-5 text-primary"></i>
                        <span class="ml-3 text-gray-700">Edit Client</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection