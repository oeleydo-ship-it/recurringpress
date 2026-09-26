@extends('layouts.app')

@section('title', $recurringInvoice->name . ' - Recurring Invoice')
@section('page-title', $recurringInvoice->name)

@section('content')
    <div class="mb-6">
        <a href="{{ route('recurring-invoices.index') }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-2"></i> Back to Recurring Invoices
        </a>
    </div>

    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ $recurringInvoice->name }}</h1>
            <p class="text-gray-500">
                {{ \App\Models\RecurringInvoice::billingCycles()[$recurringInvoice->billing_cycle] ?? $recurringInvoice->billing_cycle }} billing
                for {{ $recurringInvoice->client->bill_to_name }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @if($recurringInvoice->status === 'active')
                <form action="{{ route('recurring-invoices.generate', $recurringInvoice) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white font-medium rounded-lg hover:bg-green-600 transition">
                        <i class="fas fa-file-invoice mr-1"></i> Generate Invoice Now
                    </button>
                </form>
                <form action="{{ route('recurring-invoices.pause', $recurringInvoice) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-yellow-500 text-white font-medium rounded-lg hover:bg-yellow-600 transition">
                        <i class="fas fa-pause mr-1"></i> Pause
                    </button>
                </form>
            @endif
            @if($recurringInvoice->status === 'paused')
                <form action="{{ route('recurring-invoices.resume', $recurringInvoice) }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white font-medium rounded-lg hover:bg-green-600 transition">
                        <i class="fas fa-play mr-1"></i> Resume
                    </button>
                </form>
            @endif
            @if(!in_array($recurringInvoice->status, ['cancelled', 'completed']))
                <a href="{{ route('recurring-invoices.edit', $recurringInvoice) }}"
                    class="px-4 py-2 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition">
                    <i class="fas fa-edit mr-1"></i> Edit
                </a>
                <form action="{{ route('recurring-invoices.cancel', $recurringInvoice) }}" method="POST" class="inline"
                    onsubmit="return confirm('Are you sure you want to cancel this recurring invoice?')">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-red-500 text-white font-medium rounded-lg hover:bg-red-600 transition">
                        <i class="fas fa-times mr-1"></i> Cancel
                    </button>
                </form>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-1">Status</h3>
            <span class="inline-flex px-3 py-1 text-sm font-medium rounded-full {{ $recurringInvoice->status_badge }}">
                {{ ucfirst($recurringInvoice->status) }}
            </span>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-1">Next Invoice Date</h3>
            <p class="text-xl font-bold text-gray-900">{{ $recurringInvoice->next_invoice_date->format('M d, Y') }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-sm font-medium text-gray-500 mb-1">Total per Cycle</h3>
            <p class="text-xl font-bold text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($recurringInvoice->total, 2) }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Subscription Details</h2>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500">Client</p>
                        <a href="{{ route('clients.show', $recurringInvoice->client) }}" class="font-medium text-primary text-primary-hover">
                            {{ $recurringInvoice->client->bill_to_name }}
                        </a>
                        @if($recurringInvoice->client->bill_to_contact)
                        <p class="text-sm text-gray-500">{{ $recurringInvoice->client->bill_to_contact }}</p>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Billing Cycle</p>
                        <p class="font-medium text-gray-900">{{ \App\Models\RecurringInvoice::billingCycles()[$recurringInvoice->billing_cycle] ?? $recurringInvoice->billing_cycle }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Start Date</p>
                        <p class="font-medium text-gray-900">{{ $recurringInvoice->start_date->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">End Date</p>
                        <p class="font-medium text-gray-900">{{ $recurringInvoice->end_date ? $recurringInvoice->end_date->format('M d, Y') : 'No end date' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Invoices Generated</p>
                        <p class="font-medium text-gray-900">{{ $recurringInvoice->invoices_generated }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Cycle Limit</p>
                        <p class="font-medium text-gray-900">{{ $recurringInvoice->billing_cycle_count ?? 'Unlimited' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Auto-send Invoices</p>
                        <p class="font-medium text-gray-900">{{ $recurringInvoice->auto_send ? 'Yes' : 'No' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Last Invoice Date</p>
                        <p class="font-medium text-gray-900">{{ $recurringInvoice->last_invoice_date ? $recurringInvoice->last_invoice_date->format('M d, Y') : 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Line Items</h2>
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Qty</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tax</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($recurringInvoice->items as $item)
                            <tr>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900">{{ $item->name }}</p>
                                    @if($item->description)
                                        <p class="text-sm text-gray-500">{{ $item->description }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-500">{{ $item->quantity }} {{ $item->unit }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ auth()->user()->company->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $item->tax_rate }}%</td>
                                <td class="px-4 py-3 text-right font-medium text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="border-t border-gray-200">
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-right text-gray-500">Subtotal</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($recurringInvoice->subtotal, 2) }}</td>
                        </tr>
                        <tr>
                            <td colspan="4" class="px-4 py-3 text-right text-gray-500">Tax</td>
                            <td class="px-4 py-3 text-right font-medium text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($recurringInvoice->tax_amount, 2) }}</td>
                        </tr>
                        @if($recurringInvoice->discount_amount > 0)
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right text-gray-500">
                                    Discount ({{ $recurringInvoice->discount_type === 'percentage' ? $recurringInvoice->discount_amount . '%' : auth()->user()->company->currency_symbol . number_format($recurringInvoice->discount_amount, 2) }})
                                </td>
                                <td class="px-4 py-3 text-right font-medium text-red-600">
                                    -{{ auth()->user()->company->currency_symbol }}{{ number_format($recurringInvoice->discount_type === 'percentage' ? $recurringInvoice->subtotal * $recurringInvoice->discount_amount / 100 : $recurringInvoice->discount_amount, 2) }}
                                </td>
                            </tr>
                        @endif
                        <tr class="bg-gray-50">
                            <td colspan="4" class="px-4 py-3 text-right font-bold text-gray-900">Total per Cycle</td>
                            <td class="px-4 py-3 text-right font-bold text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($recurringInvoice->total, 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($recurringInvoice->invoices->count() > 0)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Generated Invoices</h2>
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Invoice #</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Amount</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($recurringInvoice->invoices()->latest()->take(10)->get() as $invoice)
                                <tr>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('invoices.show', $invoice) }}" class="font-medium text-primary text-primary-hover">
                                            {{ $invoice->invoice_number }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ $invoice->issue_date->format('M d, Y') }}</td>
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ auth()->user()->company->currency_symbol }}{{ number_format($invoice->total, 2) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-1 text-xs font-medium rounded-full {{ $invoice->status_badge }}">
                                            {{ ucfirst($invoice->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <a href="{{ route('invoices.show', $invoice) }}" class="text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        <div class="space-y-6">
            @if($recurringInvoice->notes)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Notes</h3>
                    <p class="text-gray-900 whitespace-pre-line">{{ $recurringInvoice->notes }}</p>
                </div>
            @endif

            @if($recurringInvoice->terms)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Terms & Conditions</h3>
                    <p class="text-gray-900 whitespace-pre-line">{{ $recurringInvoice->terms }}</p>
                </div>
            @endif

            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Created By</h3>
                <p class="text-gray-900">{{ $recurringInvoice->creator->name }}</p>
                <p class="text-sm text-gray-500">{{ $recurringInvoice->created_at->format('M d, Y \a\t h:i A') }}</p>
            </div>
        </div>
    </div>
@endsection
