@extends('layouts.app')

@section('title', 'Quotations - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Quotations')
@section('page-subtitle', 'Manage your quotes and estimates')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end gap-3 mb-6">
        <a href="{{ route('quotations.export', request()->query()) }}"
            class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition">
            <i class="fas fa-download mr-2"></i> Export CSV
        </a>
        <a href="{{ route('quotations.create') }}"
            class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg bg-primary-hover transition">
            <i class="fas fa-plus mr-2"></i> New Quotation
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 mb-6">
        <div class="p-4 border-b border-gray-100">
            <form action="{{ route('quotations.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search quotations..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <select name="status"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="sent" {{ request('status') === 'sent' ? 'selected' : '' }}>Sent</option>
                    <option value="accepted" {{ request('status') === 'accepted' ? 'selected' : '' }}>Accepted</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                    <option value="converted" {{ request('status') === 'converted' ? 'selected' : '' }}>Converted</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    <i class="fas fa-search mr-2"></i> Filter
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Valid Until</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($quotations as $quotation)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('quotations.show', $quotation) }}"
                                    class="font-medium text-primary hover:underline">
                                    {{ $quotation->quotation_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-medium text-gray-900">{{ $quotation->client->name }}</p>
                                <p class="text-sm text-gray-500">{{ $quotation->client->email }}</p>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $quotation->issue_date->format('M d, Y') }}</td>
                            <td class="px-6 py-4 text-gray-600 {{ $quotation->isExpired() ? 'text-red-600' : '' }}">
                                {{ $quotation->valid_until->format('M d, Y') }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($quotation->total, 2) }}</td>
                            <td class="px-6 py-4">{!! $quotation->status_badge !!}</td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('quotations.show', $quotation) }}"
                                        class="p-2 text-gray-400 hover:text-gray-600" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    @if($quotation->status !== 'converted')
                                        <a href="{{ route('quotations.edit', $quotation) }}"
                                            class="p-2 text-gray-400 hover:text-primary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    @endif
                                    @if($quotation->canConvert())
                                        <form action="{{ route('quotations.convert', $quotation) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="p-2 text-gray-400 hover:text-green-600"
                                                title="Convert to Invoice">
                                                <i class="fas fa-file-invoice"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-file-alt text-4xl mb-4 text-gray-300"></i>
                                <p>No quotations found</p>
                                <a href="{{ route('quotations.create') }}"
                                    class="mt-2 inline-block text-primary hover:underline">Create your first quotation</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($quotations->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $quotations->links() }}
            </div>
        @endif
    </div>
@endsection