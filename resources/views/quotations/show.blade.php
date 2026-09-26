@extends('layouts.app')

@section('title', 'Quotation ' . $quotation->quotation_number . ' - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', $quotation->quotation_number)

@section('content')
<div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between">
    <div>
        <a href="{{ route('quotations.index') }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-2"></i> Back to Quotations
        </a>
        <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $quotation->quotation_number }}</h1>
    </div>
    <div class="mt-4 sm:mt-0 flex flex-wrap gap-2">
        @if($quotation->status === 'draft')
        <form action="{{ route('quotations.email', $quotation) }}" method="POST" class="inline">
            @csrf
            <div class="relative group">
                <button type="submit" title="Send to Client" class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition">
                    <i class="fas fa-paper-plane"></i>
                </button>
                <span class="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap z-10">Send to Client</span>
            </div>
        </form>
        @endif

        @if($quotation->canTransitionTo(\App\Models\Quotation::STATUS_ACCEPTED))
        <form action="{{ route('quotations.accept', $quotation) }}" method="POST" class="inline">
            @csrf
            <div class="relative group">
                <button type="submit" title="Mark Accepted" class="w-10 h-10 rounded-full bg-green-600 text-white flex items-center justify-center hover:bg-green-700 transition">
                    <i class="fas fa-check"></i>
                </button>
                <span class="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap z-10">Mark Accepted</span>
            </div>
        </form>
        @endif

        @if($quotation->canTransitionTo(\App\Models\Quotation::STATUS_REJECTED))
        <form action="{{ route('quotations.reject', $quotation) }}" method="POST" class="inline">
            @csrf
            <div class="relative group">
                <button type="submit" title="Mark Rejected" class="w-10 h-10 rounded-full bg-red-600 text-white flex items-center justify-center hover:bg-red-700 transition">
                    <i class="fas fa-times"></i>
                </button>
                <span class="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap z-10">Mark Rejected</span>
            </div>
        </form>
        @endif

        @if($quotation->canConvert())
        <form action="{{ route('quotations.convert', $quotation) }}" method="POST" class="inline">
            @csrf
            <div class="relative group">
                <button type="submit" title="Convert to Invoice" class="w-10 h-10 rounded-full bg-primary text-white flex items-center justify-center hover:opacity-90 transition">
                    <i class="fas fa-file-invoice"></i>
                </button>
                <span class="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap z-10">Convert to Invoice</span>
            </div>
        </form>
        @endif

        <div class="relative group">
            <a href="{{ route('quotations.download', $quotation) }}" title="Download PDF" class="w-10 h-10 rounded-full bg-gray-100 text-gray-700 flex items-center justify-center hover:bg-gray-200 transition">
                <i class="fas fa-download"></i>
            </a>
            <span class="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap z-10">Download PDF</span>
        </div>

        @if($quotation->client->email)
        <form action="{{ route('quotations.email', $quotation) }}" method="POST" class="inline">
            @csrf
            <div class="relative group">
                <button type="submit" title="Email Client" class="w-10 h-10 rounded-full bg-gray-100 text-gray-700 flex items-center justify-center hover:bg-gray-200 transition">
                    <i class="fas fa-envelope"></i>
                </button>
                <span class="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap z-10">Email Client</span>
            </div>
        </form>
        @endif

        @if($quotation->isEditable())
        <div class="relative group">
            <a href="{{ route('quotations.edit', $quotation) }}" title="Edit" class="w-10 h-10 rounded-full bg-gray-100 text-gray-700 flex items-center justify-center hover:bg-gray-200 transition">
                <i class="fas fa-edit"></i>
            </a>
            <span class="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap z-10">Edit</span>
        </div>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $quotation->quotation_number }}</h2>
                    <p class="text-gray-500">{{ $quotation->reference ? 'Ref: ' . $quotation->reference : '' }}</p>
                </div>
                <div class="text-right">
                    {!! $quotation->status_badge !!}
                    @if($quotation->converted_invoice_id)
                    <p class="text-sm text-gray-500 mt-1">
                        Converted to <a href="{{ route('invoices.show', $quotation->converted_invoice_id) }}" class="text-primary hover:underline">Invoice</a>
                    </p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6 mb-8">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">From</h3>
                    <p class="font-semibold text-gray-900">{{ $quotation->company->name }}</p>
                    @if($quotation->company->address)
                    <p class="text-gray-600">{{ $quotation->company->address }}</p>
                    @endif
                    @if($quotation->company->city)
                    <p class="text-gray-600">{{ $quotation->company->city }}, {{ $quotation->company->state }} {{ $quotation->company->postal_code }}</p>
                    @endif
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">To</h3>
                    <p class="font-semibold text-gray-900">{{ $quotation->client->bill_to_name }}</p>
                    @if($quotation->client->bill_to_contact)
                    <p class="text-gray-600">{{ $quotation->client->bill_to_contact }}</p>
                    @endif
                    <p class="text-gray-600">{{ $quotation->client->email }}</p>
                    @if($quotation->client->address)
                    <p class="text-gray-600">{{ $quotation->client->address }}</p>
                    @endif
                </div>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-8 p-4 bg-gray-50 rounded-lg">
                <div>
                    <p class="text-sm text-gray-500">Issue Date</p>
                    <p class="font-medium">{{ $quotation->issue_date->format('M d, Y') }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Valid Until</p>
                    <p class="font-medium {{ $quotation->isExpired() ? 'text-red-600' : '' }}">{{ $quotation->valid_until->format('M d, Y') }}</p>
                    @if(!in_array($quotation->status, [\App\Models\Quotation::STATUS_ACCEPTED, \App\Models\Quotation::STATUS_CONVERTED]))
                        @php($daysLeft = $quotation->daysUntilExpiry())
                        <p class="text-xs {{ $daysLeft < 0 ? 'text-red-500' : ($daysLeft <= 3 ? 'text-yellow-600' : 'text-gray-400') }} mt-0.5">
                            {{ $daysLeft < 0 ? 'Expired ' . abs($daysLeft) . ' day' . (abs($daysLeft) === 1 ? '' : 's') . ' ago' : ($daysLeft === 0 ? 'Expires today' : 'Expires in ' . $daysLeft . ' day' . ($daysLeft === 1 ? '' : 's')) }}
                        </p>
                    @endif
                </div>
                <div>
                    <p class="text-sm text-gray-500">Created By</p>
                    <p class="font-medium">{{ $quotation->user?->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Total</p>
                    <p class="font-semibold text-lg">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}</p>
                </div>
            </div>

            @if($quotation->sent_at || $quotation->viewed_at || $quotation->responded_at)
                <div class="flex flex-wrap gap-x-6 gap-y-2 mb-8 text-sm text-gray-600">
                    @if($quotation->sent_at)
                        <span><i class="fas fa-paper-plane text-gray-400 mr-1.5"></i>Sent
                            {{ $quotation->sent_at->format('M d, Y g:i A') }}</span>
                    @endif
                    @if($quotation->viewed_at)
                        <span><i class="fas fa-eye text-gray-400 mr-1.5"></i>Viewed by client
                            {{ $quotation->viewed_at->format('M d, Y g:i A') }}</span>
                    @endif
                    @if($quotation->responded_at)
                        <span><i class="fas fa-reply text-gray-400 mr-1.5"></i>Responded
                            {{ $quotation->responded_at->format('M d, Y g:i A') }}</span>
                    @endif
                </div>
            @endif

            <table class="w-full mb-6">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($quotation->items as $item)
                    <tr>
                        <td class="px-4 py-4">
                            <p class="font-medium text-gray-900">{{ $item->name }}</p>
                            @if($item->description)
                            <p class="text-sm text-gray-500">{{ $item->description }}</p>
                            @endif
                        </td>
                        <td class="px-4 py-4 text-center text-gray-600">{{ $item->quantity }} {{ $item->unit }}</td>
                        <td class="px-4 py-4 text-right text-gray-600">{{ $quotation->company->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                        <td class="px-4 py-4 text-right font-medium text-gray-900">{{ $quotation->company->currency_symbol }}{{ number_format($item->total, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="flex justify-end">
                <div class="w-64">
                    <div class="flex justify-between py-2 text-gray-600">
                        <span>Subtotal</span>
                        <span>{{ $quotation->company->currency_symbol }}{{ number_format($quotation->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between py-2 text-gray-600">
                        <span>Tax</span>
                        <span>{{ $quotation->company->currency_symbol }}{{ number_format($quotation->tax_amount, 2) }}</span>
                    </div>
                    @if($quotation->discount_amount > 0)
                    <div class="flex justify-between py-2 text-gray-600">
                        <span>Discount</span>
                        <span>-{{ $quotation->company->currency_symbol }}{{ number_format($quotation->discount_type === 'percentage' ? ($quotation->subtotal * $quotation->discount_amount / 100) : $quotation->discount_amount, 2) }}</span>
                    </div>
                    @endif
                    <div class="flex justify-between py-3 text-xl font-bold border-t-2 border-gray-200">
                        <span>Total</span>
                        <span>{{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}</span>
                    </div>
                </div>
            </div>

            @if($quotation->notes)
            <div class="mt-6 p-4 bg-gray-50 rounded-lg">
                <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Notes</h3>
                <p class="text-gray-700">{{ $quotation->notes }}</p>
            </div>
            @endif

            @if($quotation->terms)
            <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Terms & Conditions</h3>
                <p class="text-gray-700">{{ $quotation->terms }}</p>
            </div>
            @endif
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Share Quotation</h3>
            <p class="text-sm text-gray-600 mb-4">Send this link to your client:</p>
            <div class="flex">
                <input type="text" value="{{ $quotation->share_url }}" readonly id="shareUrl"
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-l-lg bg-gray-50 text-sm">
                <button onclick="copyShareUrl()" class="px-4 py-2 bg-primary text-white rounded-r-lg hover:opacity-90 transition">
                    <i class="fas fa-copy"></i>
                </button>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-2">
                <form action="{{ route('quotations.duplicate', $quotation) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center px-4 py-2 text-gray-700 hover:bg-gray-50 rounded-lg transition">
                        <i class="fas fa-copy w-5 mr-3 text-gray-400"></i>
                        Duplicate Quotation
                    </button>
                </form>
                @if($quotation->status !== 'converted')
                <form action="{{ route('quotations.destroy', $quotation) }}" method="POST" onsubmit="return confirm('Are you sure?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full flex items-center px-4 py-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                        <i class="fas fa-trash w-5 mr-3"></i>
                        Delete Quotation
                    </button>
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function copyShareUrl() {
    const input = document.getElementById('shareUrl');
    input.select();
    document.execCommand('copy');
    alert('Link copied to clipboard!');
}
</script>
@endpush
