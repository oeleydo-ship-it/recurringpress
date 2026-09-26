@extends('layouts.app')

@section('title', 'Expense ' . $expense->expense_number)
@section('page-title', $expense->title)
@section('page-subtitle', $expense->expense_number)

@section('content')
    @php $sym = auth()->user()->company->currency_symbol; @endphp

    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $expense->title }}</h1>
            <p class="text-gray-600">{{ $expense->expense_number }} · {{ $expense->expense_date->format('M d, Y') }}</p>
        </div>
        <div class="flex items-center space-x-2">
            <a href="{{ route('expenses.edit', $expense) }}" class="px-3 py-2 bg-primary text-white rounded-lg bg-primary-hover text-sm"><i class="fas fa-edit mr-1"></i> Edit</a>
            <a href="{{ route('expenses.index') }}" class="px-3 py-2 border rounded-lg text-sm">Back</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        <div><div class="text-xs text-gray-500 uppercase">Category</div><div class="text-gray-900">{{ $expense->category_label }}</div></div>
        <div><div class="text-xs text-gray-500 uppercase">Vendor</div><div class="text-gray-900">{{ $expense->vendor ?? '—' }}</div></div>
        <div><div class="text-xs text-gray-500 uppercase">Amount</div><div class="text-gray-900">{{ $sym }}{{ number_format($expense->amount, 2) }}</div></div>
        <div><div class="text-xs text-gray-500 uppercase">Tax</div><div class="text-gray-900">{{ $sym }}{{ number_format($expense->tax_amount, 2) }}</div></div>
        <div><div class="text-xs text-gray-500 uppercase">Total</div><div class="text-gray-900 font-bold">{{ $sym }}{{ number_format($expense->total, 2) }}</div></div>
        <div><div class="text-xs text-gray-500 uppercase">Payment Method</div><div class="text-gray-900">{{ ucfirst(str_replace('_', ' ', $expense->payment_method)) }}</div></div>
        <div><div class="text-xs text-gray-500 uppercase">Reference</div><div class="text-gray-900">{{ $expense->reference ?? '—' }}</div></div>
        <div><div class="text-xs text-gray-500 uppercase">Related Invoice</div>
            <div>@if($expense->invoice)<a href="{{ route('invoices.show', $expense->invoice) }}" class="text-primary">{{ $expense->invoice->invoice_number }}</a>@else — @endif</div>
        </div>
        @if($expense->description)
            <div class="md:col-span-2"><div class="text-xs text-gray-500 uppercase">Description</div><div class="text-gray-900 whitespace-pre-line">{{ $expense->description }}</div></div>
        @endif
        @if($expense->receipt_path)
            <div class="md:col-span-2">
                <div class="text-xs text-gray-500 uppercase mb-1">Receipt</div>
                <a href="{{ asset('storage/' . $expense->receipt_path) }}" target="_blank" class="text-primary hover:underline"><i class="fas fa-file-alt mr-1"></i> View Receipt</a>
            </div>
        @endif
    </div>
@endsection
