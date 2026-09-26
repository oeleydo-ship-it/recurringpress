@extends('layouts.app')

@section('title', 'Clients - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Clients')
@section('page-subtitle', 'Manage your client database')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end mb-6">
        <a href="{{ route('clients.create') }}"
            class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg bg-primary-hover transition">
            <i class="fas fa-plus mr-2"></i> Add Client
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-4 border-b border-gray-100">
            <form action="{{ route('clients.index') }}" method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Search clients..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>
                <select name="status"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    <option value="">All Status</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
                <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition">
                    <i class="fas fa-search mr-2"></i> Search
                </button>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoices</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Billed</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($clients as $client)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <a href="{{ route('clients.show', $client) }}"
                                    class="font-medium text-gray-900 text-primary-hover">
                                    {{ $client->name }}
                                </a>
                                @if($client->isCompany() && $client->company_name)
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        <i class="fas fa-building mr-1"></i>{{ $client->company_name }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-600">{{ $client->email }}</div>
                                <div class="text-sm text-gray-400">{{ $client->phone }}</div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $client->invoices_count }}</td>
                            <td class="px-6 py-4 font-medium text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($client->total_billed ?? 0, 2) }}
                            </td>
                            <td class="px-6 py-4">
                                @if($client->is_active)
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Active</span>
                                @else
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactive</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('clients.show', $client) }}"
                                        class="p-2 text-gray-400 text-primary-hover" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('clients.edit', $client) }}"
                                        class="p-2 text-gray-400 text-primary-hover" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('clients.destroy', $client) }}" method="POST" class="inline"
                                        onsubmit="return confirm('Are you sure?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-gray-400 hover:text-red-600" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-users text-4xl text-gray-300 mb-3"></i>
                                <p>No clients found</p>
                                <a href="{{ route('clients.create') }}"
                                    class="text-primary text-primary-hover text-sm font-medium">
                                    Add your first client
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($clients->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $clients->links() }}
            </div>
        @endif
    </div>
@endsection