@extends('layouts.app')

@section('title', 'Expenses')
@section('page-title', 'Expenses')
@section('page-subtitle', 'Track your business spending')

@section('content')
    @php $sym = auth()->user()->company->currency_symbol; @endphp

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-end mb-6">
        <div class="mt-4 sm:mt-0">
            <a href="{{ route('expenses.create') }}"
                class="inline-flex items-center px-4 py-2 bg-primary text-white text-sm font-medium rounded-lg bg-primary-hover transition">
                <i class="fas fa-plus mr-2"></i> Record Expense
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-xs text-gray-500 uppercase">Filtered Total</div>
            <div class="text-xl font-bold text-gray-900 mt-1">{{ $sym }}{{ number_format($totalAmount, 2) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-xs text-gray-500 uppercase">Filtered Tax</div>
            <div class="text-xl font-bold text-gray-900 mt-1">{{ $sym }}{{ number_format($totalTax, 2) }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="text-xs text-gray-500 uppercase">Count</div>
            <div class="text-xl font-bold text-gray-900 mt-1">{{ $expenses->total() }}</div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-4 border-b border-gray-100">
            <form action="{{ route('expenses.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-3">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..."
                    class="md:col-span-2 px-4 py-2 border border-gray-300 rounded-lg">
                <select name="category" class="px-4 py-2 border border-gray-300 rounded-lg">
                    <option value="">All Categories</option>
                    @foreach($categories as $key => $label)
                        <option value="{{ $key }}" {{ request('category') === $key ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
                <input type="date" name="from" value="{{ request('from') }}" class="px-4 py-2 border border-gray-300 rounded-lg">
                <input type="date" name="to" value="{{ request('to') }}" class="px-4 py-2 border border-gray-300 rounded-lg">
                <div class="md:col-span-5 text-right">
                    <button type="submit" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">
                        <i class="fas fa-search mr-2"></i> Filter
                    </button>
                </div>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vendor</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Amount</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($expenses as $e)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $e->expense_date->format('M d, Y') }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $e->expense_number }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-900">{{ $e->title }}</div>
                                @if($e->is_billable)
                                    <span class="inline-block mt-1 px-2 py-0.5 text-[10px] rounded-full bg-amber-100 text-amber-800">Billable</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $e->category_label }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ $e->vendor ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm">
                                @if($e->invoice)
                                    <a href="{{ route('invoices.show', $e->invoice) }}" class="text-primary hover:underline">{{ $e->invoice->invoice_number }}</a>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">{{ $sym }}{{ number_format($e->total, 2) }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end space-x-2">
                                    <a href="{{ route('expenses.edit', $e) }}" class="p-2 text-gray-400 text-primary-hover" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('expenses.destroy', $e) }}" method="POST" class="inline" onsubmit="return confirm('Delete this expense?')">
                                        @csrf @method('DELETE')
                                        <button class="p-2 text-gray-400 hover:text-red-600" title="Delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-wallet text-4xl text-gray-300 mb-3"></i>
                                <p>No expenses recorded yet</p>
                                <a href="{{ route('expenses.create') }}" class="text-primary text-primary-hover text-sm font-medium">Record your first expense</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($expenses->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">{{ $expenses->links() }}</div>
        @endif
    </div>
@endsection
