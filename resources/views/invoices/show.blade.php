@extends('layouts.app')

@section('title', $invoice->invoice_number . ' - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', $invoice->invoice_number)

@section('content')
<div class="mb-6">
    <a href="{{ route('invoices.index') }}" class="text-gray-500 hover:text-gray-700">
        <i class="fas fa-arrow-left mr-2"></i> Back to Invoices
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="p-6 border-b border-gray-100">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ $invoice->invoice_number }}</h1>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $invoice->status_badge }} mt-2">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    </div>
                    <div class="mt-4 sm:mt-0 flex flex-wrap gap-2">
                        @if($invoice->status === 'draft')
                        <form action="{{ route('invoices.email', $invoice) }}" method="POST" class="inline">
                            @csrf
                            <div class="relative group">
                                <button type="submit" title="Send to Client" class="w-10 h-10 rounded-full bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 transition">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                                <span class="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap z-10">Send to Client</span>
                            </div>
                        </form>
                        <form action="{{ route('invoices.send', $invoice) }}" method="POST" class="inline">
                            @csrf
                            <div class="relative group">
                                <button type="submit" title="Mark as Sent" class="w-10 h-10 rounded-full bg-gray-600 text-white flex items-center justify-center hover:bg-gray-700 transition">
                                    <i class="fas fa-check"></i>
                                </button>
                                <span class="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap z-10">Mark as Sent</span>
                            </div>
                        </form>
                        @endif
                        @if(in_array($invoice->status, ['sent', 'overdue', 'partial']))
                        <div class="relative group">
                            <button type="button" onclick="openPaymentModal()" title="Add Payment" class="w-10 h-10 rounded-full bg-green-600 text-white flex items-center justify-center hover:bg-green-700 transition">
                                <i class="fas fa-money-bill-wave"></i>
                            </button>
                            <span class="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap z-10">Add Payment</span>
                        </div>
                        @endif
                        @if($invoice->status === 'refunded')
                        <div class="relative group">
                            <div title="Refunded" class="w-10 h-10 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center cursor-default">
                                <i class="fas fa-undo"></i>
                            </div>
                            <span class="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap z-10">Refunded</span>
                        </div>
                        @endif
                        <div class="relative group">
                            <a href="{{ route('invoices.download', $invoice) }}" title="Download PDF" class="w-10 h-10 rounded-full bg-gray-100 text-gray-700 flex items-center justify-center hover:bg-gray-200 transition">
                                <i class="fas fa-download"></i>
                            </a>
                            <span class="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap z-10">Download PDF</span>
                        </div>
                        @if(!in_array($invoice->status, ['paid', 'refunded']))
                        <div class="relative group">
                            <a href="{{ route('invoices.edit', $invoice) }}" title="Edit" class="w-10 h-10 rounded-full bg-gray-100 text-gray-700 flex items-center justify-center hover:bg-gray-200 transition">
                                <i class="fas fa-edit"></i>
                            </a>
                            <span class="absolute -top-8 left-1/2 -translate-x-1/2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition pointer-events-none whitespace-nowrap z-10">Edit</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2">From</h3>
                        <div class="text-gray-900">
                            <p class="font-semibold">{{ $invoice->company->name }}</p>
                            @if($invoice->company->address)
                            <p>{{ $invoice->company->address }}</p>
                            @endif
                            @if($invoice->company->city)
                            <p>{{ $invoice->company->city }}, {{ $invoice->company->state }} {{ $invoice->company->postal_code }}</p>
                            @endif
                            @if($invoice->company->email)
                            <p>{{ $invoice->company->email }}</p>
                            @endif
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500 mb-2">Bill To</h3>
                        <div class="text-gray-900">
                            <p class="font-semibold">{{ $invoice->client->bill_to_name }}</p>
                            @if($invoice->client->bill_to_contact)
                            <p class="text-gray-600">{{ $invoice->client->bill_to_contact }}</p>
                            @endif
                            @if($invoice->client->address)
                            <p>{{ $invoice->client->address }}</p>
                            @endif
                            @if($invoice->client->city)
                            <p>{{ $invoice->client->city }}, {{ $invoice->client->state }} {{ $invoice->client->postal_code }}</p>
                            @endif
                            @if($invoice->client->email)
                            <p>{{ $invoice->client->email }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm text-gray-500">Invoice Number</p>
                        <p class="font-semibold text-gray-900">{{ $invoice->invoice_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Issue Date</p>
                        <p class="font-semibold text-gray-900">{{ $invoice->issue_date->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Due Date</p>
                        <p class="font-semibold {{ $invoice->isOverdue() ? 'text-red-600' : 'text-gray-900' }}">
                            {{ $invoice->due_date->format('M d, Y') }}
                        </p>
                    </div>
                    @if($invoice->reference)
                    <div>
                        <p class="text-sm text-gray-500">Reference</p>
                        <p class="font-semibold text-gray-900">{{ $invoice->reference }}</p>
                    </div>
                    @endif
                </div>

                <div class="overflow-x-auto mb-6">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Item</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Qty</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Price</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Tax</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($invoice->items as $item)
                            <tr>
                                <td class="px-4 py-4">
                                    <p class="font-medium text-gray-900">{{ $item->name }}</p>
                                    @if($item->description)
                                    <p class="text-sm text-gray-500">{{ $item->description }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-4 text-center text-gray-600">{{ $item->quantity }} {{ $item->unit }}</td>
                                <td class="px-4 py-4 text-right text-gray-600">{{ $invoice->company->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                                <td class="px-4 py-4 text-right text-gray-600">{{ $item->tax_rate }}%</td>
                                <td class="px-4 py-4 text-right font-medium text-gray-900">{{ $invoice->company->currency_symbol }}{{ number_format($item->total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end">
                    <div class="w-full md:w-64 space-y-2">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal</span>
                            <span>{{ $invoice->company->currency_symbol }}{{ number_format($invoice->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Tax</span>
                            <span>{{ $invoice->company->currency_symbol }}{{ number_format($invoice->tax_amount, 2) }}</span>
                        </div>
                        @if($invoice->discount_amount > 0)
                        <div class="flex justify-between text-gray-600">
                            <span>Discount</span>
                            <span>-{{ $invoice->company->currency_symbol }}{{ number_format($invoice->discount_type === 'percentage' ? ($invoice->subtotal * $invoice->discount_amount / 100) : $invoice->discount_amount, 2) }}</span>
                        </div>
                        @endif
                        <hr>
                        <div class="flex justify-between text-lg font-bold text-gray-900">
                            <span>Total</span>
                            <span>{{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}</span>
                        </div>
                        @if($invoice->paid_amount > 0)
                        <div class="flex justify-between text-green-600 font-medium">
                            <span>Paid Amount</span>
                            <span>{{ $invoice->company->currency_symbol }}{{ number_format($invoice->paid_amount, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-lg font-bold {{ $invoice->remaining_amount > 0 ? 'text-red-600' : 'text-green-600' }}">
                            <span>{{ $invoice->remaining_amount > 0 ? 'Remaining' : 'Balance' }}</span>
                            <span>{{ $invoice->company->currency_symbol }}{{ number_format($invoice->remaining_amount, 2) }}</span>
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Refund Information -->
                @if($invoice->status === 'refunded')
                <div class="mt-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                    <div class="flex items-center mb-3">
                        <i class="fas fa-undo text-purple-600 mr-2"></i>
                        <h3 class="text-sm font-semibold text-purple-900">Refund Information</h3>
                    </div>
                    <div class="text-sm text-purple-700">
                        <p>This invoice has been fully refunded.</p>
                        @if($invoice->payments()->where('status', 'refunded')->exists())
                            @php
                                $refundedPayments = $invoice->payments()->where('status', 'refunded')->get();
                                $totalRefunded = $refundedPayments->sum('refunded_amount');
                            @endphp
                            <p class="mt-2">Total Refunded: <span class="font-semibold">{{ $invoice->company->currency_symbol }}{{ number_format($totalRefunded, 2) }}</span></p>
                            @foreach($refundedPayments as $payment)
                                <div class="mt-2 p-2 bg-white rounded border border-purple-100">
                                    <div class="flex justify-between items-center">
                                        <span class="font-medium">{{ $payment->payment_number ?? 'Payment #' . $payment->id }}</span>
                                        <span>{{ $invoice->company->currency_symbol }}{{ number_format($payment->refunded_amount, 2) }}</span>
                                    </div>
                                    @if($payment->refund_reason)
                                        <p class="text-xs text-gray-600 mt-1">Reason: {{ $payment->refund_reason }}</p>
                                    @endif
                                    @if($payment->refunded_at)
                                        <p class="text-xs text-gray-500">Refunded on: {{ $payment->refunded_at->format('M d, Y') }}</p>
                                    @endif
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
                @endif

                @if($invoice->notes)
                <div class="mt-8 pt-6 border-t">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Notes</h3>
                    <p class="text-gray-700">{{ $invoice->notes }}</p>
                </div>
                @endif

                @if($invoice->terms)
                <div class="mt-4">
                    <h3 class="text-sm font-medium text-gray-500 mb-2">Terms & Conditions</h3>
                    <p class="text-gray-700">{{ $invoice->terms }}</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h3>
            <div class="space-y-2">
                <button onclick="navigator.clipboard.writeText('{{ $invoice->share_url }}')" class="w-full flex items-center p-3 rounded-lg hover:bg-gray-50 transition text-left">
                    <i class="fas fa-link w-5 text-primary"></i>
                    <span class="ml-3 text-gray-700">Copy Share Link</span>
                </button>
                <a href="{{ route('invoices.download', $invoice) }}" class="flex items-center p-3 rounded-lg hover:bg-gray-50 transition">
                    <i class="fas fa-file-pdf w-5 text-primary"></i>
                    <span class="ml-3 text-gray-700">Download PDF</span>
                </a>
                <form action="{{ route('invoices.duplicate', $invoice) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center p-3 rounded-lg hover:bg-gray-50 transition text-left">
                        <i class="fas fa-copy w-5 text-primary"></i>
                        <span class="ml-3 text-gray-700">Duplicate Invoice</span>
                    </button>
                </form>
                @if($invoice->client->email)
                <form action="{{ route('invoices.email', $invoice) }}" method="POST">
                    @csrf
                    <button type="submit" class="w-full flex items-center p-3 rounded-lg hover:bg-gray-50 transition text-left">
                        <i class="fas fa-envelope w-5 text-primary"></i>
                        <span class="ml-3 text-gray-700">Email Client</span>
                    </button>
                </form>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Share Link</h3>
            <div class="p-3 bg-gray-50 rounded-lg break-all text-sm text-gray-600">
                {{ $invoice->share_url }}
            </div>
            <p class="text-xs text-gray-400 mt-2">Share this link with your client to view the invoice</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Activity</h3>
            <div class="space-y-3">
                <div class="flex items-center text-sm">
                    <i class="fas fa-plus-circle text-gray-400 w-5"></i>
                    <span class="ml-2 text-gray-600">Created {{ $invoice->created_at->diffForHumans() }}</span>
                </div>
                @if($invoice->sent_at)
                <div class="flex items-center text-sm">
                    <i class="fas fa-paper-plane text-blue-400 w-5"></i>
                    <span class="ml-2 text-gray-600">Sent {{ $invoice->sent_at->diffForHumans() }}</span>
                </div>
                @endif
                @if($invoice->paid_at)
                <div class="flex items-center text-sm">
                    <i class="fas fa-check-circle text-green-400 w-5"></i>
                    <span class="ml-2 text-gray-600">Paid {{ $invoice->paid_at->diffForHumans() }}</span>
                </div>
                @endif
                @if($invoice->status === 'refunded' && $invoice->payments()->where('status', 'refunded')->exists())
                    @php
                        $lastRefund = $invoice->payments()->where('status', 'refunded')->latest('refunded_at')->first();
                    @endphp
                    @if($lastRefund && $lastRefund->refunded_at)
                    <div class="flex items-center text-sm">
                        <i class="fas fa-undo text-purple-400 w-5"></i>
                        <span class="ml-2 text-gray-600">Refunded {{ $lastRefund->refunded_at->diffForHumans() }}</span>
                    </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-lg bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Add Payment</h3>
            <button onclick="closePaymentModal()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form action="{{ route('invoices.paid', $invoice) }}" method="POST" id="paymentForm">
            @csrf
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Invoice Total</label>
                <div class="text-2xl font-bold text-gray-900">
                    {{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}
                </div>
            </div>

            @if($invoice->paid_amount > 0)
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Already Paid</label>
                <div class="text-lg font-semibold text-green-600">
                    {{ $invoice->company->currency_symbol }}{{ number_format($invoice->paid_amount, 2) }}
                </div>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Remaining Amount</label>
                <div class="text-lg font-semibold text-red-600">
                    {{ $invoice->company->currency_symbol }}{{ number_format($invoice->remaining_amount, 2) }}
                </div>
            </div>
            @endif

            <div class="mb-4">
                <label for="payment_amount" class="block text-sm font-medium text-gray-700 mb-2">
                    Payment Amount <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">
                        {{ $invoice->company->currency_symbol }}
                    </span>
                    <input 
                        type="number" 
                        name="amount" 
                        id="payment_amount" 
                        step="0.01" 
                        min="0.01" 
                        max="{{ $invoice->remaining_amount }}"
                        value="{{ $invoice->remaining_amount }}"
                        class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                        required
                        oninput="updateRemainingAmount()"
                    >
                </div>
                <p class="mt-1 text-xs text-gray-500">Maximum: {{ $invoice->company->currency_symbol }}{{ number_format($invoice->remaining_amount, 2) }}</p>
            </div>

            <div class="mb-4">
                <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">
                    Payment Method
                </label>
                <select name="payment_method" id="payment_method" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    <option value="manual">Manual/Cash</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="credit_card">Credit Card</option>
                    <option value="paypal">PayPal</option>
                    <option value="stripe">Stripe</option>
                    <option value="other">Other</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="payment_notes" class="block text-sm font-medium text-gray-700 mb-2">
                    Notes (Optional)
                </label>
                <textarea 
                    name="notes" 
                    id="payment_notes" 
                    rows="3" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                    placeholder="Add any notes about this payment..."
                ></textarea>
            </div>

            <div id="remainingAfterPayment" class="mb-4 p-3 bg-blue-50 rounded-lg hidden">
                <div class="flex justify-between items-center">
                    <span class="text-sm font-medium text-blue-900">Remaining After Payment:</span>
                    <span class="text-lg font-bold text-blue-900" id="remainingAmountDisplay">
                        {{ $invoice->company->currency_symbol }}0.00
                    </span>
                </div>
            </div>

            <div class="flex gap-3">
                <button 
                    type="button" 
                    onclick="closePaymentModal()" 
                    class="flex-1 px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition"
                >
                    Cancel
                </button>
                <button 
                    type="submit" 
                    class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition"
                >
                    <i class="fas fa-check mr-2"></i> Add Payment
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const invoiceTotal = {{ $invoice->total }};
    const paidAmount = {{ $invoice->paid_amount }};
    const remainingAmount = {{ $invoice->remaining_amount }};
    const currencySymbol = '{{ $invoice->company->currency_symbol }}';

    function openPaymentModal() {
        document.getElementById('paymentModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        updateRemainingAmount();
    }

    function closePaymentModal() {
        document.getElementById('paymentModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    function updateRemainingAmount() {
        const paymentAmount = parseFloat(document.getElementById('payment_amount').value) || 0;
        const newRemaining = remainingAmount - paymentAmount;
        
        const remainingDisplay = document.getElementById('remainingAfterPayment');
        const remainingAmountDisplay = document.getElementById('remainingAmountDisplay');
        
        if (paymentAmount > 0 && paymentAmount < remainingAmount) {
            remainingDisplay.classList.remove('hidden');
            remainingAmountDisplay.textContent = currencySymbol + newRemaining.toFixed(2);
        } else {
            remainingDisplay.classList.add('hidden');
        }
    }

    // Close modal when clicking outside
    document.getElementById('paymentModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closePaymentModal();
        }
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closePaymentModal();
        }
    });
</script>
@endsection
