<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-sky-500 px-8 py-6 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold">INVOICE</h1>
                        <p class="text-sky-100 mt-1">{{ $invoice->invoice_number }}</p>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium
                            {{ $invoice->status === 'paid' ? 'bg-green-500' : ($invoice->status === 'overdue' ? 'bg-red-500' : 'bg-sky-600') }}">
                            {{ strtoupper($invoice->status) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">From</h3>
                        <div class="text-gray-900">
                            @if($invoice->company->logo)
                            <img src="{{ $invoice->company->logo_url }}" alt="{{ $invoice->company->name }}" class="h-12 mb-2">
                            @endif
                            <p class="font-semibold text-lg">{{ $invoice->company->name }}</p>
                            @if($invoice->company->address)
                            <p class="text-gray-600">{{ $invoice->company->address }}</p>
                            @endif
                            @if($invoice->company->city)
                            <p class="text-gray-600">{{ $invoice->company->city }}, {{ $invoice->company->state }} {{ $invoice->company->postal_code }}</p>
                            @endif
                            @if($invoice->company->email)
                            <p class="text-gray-600">{{ $invoice->company->email }}</p>
                            @endif
                            @if($invoice->company->phone)
                            <p class="text-gray-600">{{ $invoice->company->phone }}</p>
                            @endif
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Bill To</h3>
                        <div class="text-gray-900">
                            <p class="font-semibold text-lg">{{ $invoice->client->bill_to_name }}</p>
                            @if($invoice->client->bill_to_contact)
                            <p class="text-gray-600">{{ $invoice->client->bill_to_contact }}</p>
                            @endif
                            @if($invoice->client->address)
                            <p class="text-gray-600">{{ $invoice->client->address }}</p>
                            @endif
                            @if($invoice->client->city)
                            <p class="text-gray-600">{{ $invoice->client->city }}, {{ $invoice->client->state }} {{ $invoice->client->postal_code }}</p>
                            @endif
                            @if($invoice->client->email)
                            <p class="text-gray-600">{{ $invoice->client->email }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm text-gray-500">Invoice Number</p>
                        <p class="font-semibold">{{ $invoice->invoice_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Issue Date</p>
                        <p class="font-semibold">{{ $invoice->issue_date->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Due Date</p>
                        <p class="font-semibold {{ $invoice->isOverdue() ? 'text-red-600' : '' }}">{{ $invoice->due_date->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Amount Due</p>
                        <p class="font-semibold text-lg">{{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}</p>
                    </div>
                </div>

                <div class="overflow-x-auto mb-8">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b-2 border-gray-200">
                                <th class="text-left py-3 text-gray-600 font-medium">Description</th>
                                <th class="text-center py-3 text-gray-600 font-medium">Qty</th>
                                <th class="text-right py-3 text-gray-600 font-medium">Price</th>
                                <th class="text-right py-3 text-gray-600 font-medium">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($invoice->items as $item)
                            <tr class="border-b border-gray-100">
                                <td class="py-4">
                                    <p class="font-medium text-gray-900">{{ $item->name }}</p>
                                    @if($item->description)
                                    <p class="text-sm text-gray-500">{{ $item->description }}</p>
                                    @endif
                                </td>
                                <td class="py-4 text-center text-gray-600">{{ $item->quantity }} {{ $item->unit }}</td>
                                <td class="py-4 text-right text-gray-600">{{ $invoice->company->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                                <td class="py-4 text-right font-medium text-gray-900">{{ $invoice->company->currency_symbol }}{{ number_format($item->total, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end mb-8">
                    <div class="w-72">
                        <div class="flex justify-between py-2 text-gray-600">
                            <span>Subtotal</span>
                            <span>{{ $invoice->company->currency_symbol }}{{ number_format($invoice->subtotal, 2) }}</span>
                        </div>
                        <div class="flex justify-between py-2 text-gray-600">
                            <span>Tax</span>
                            <span>{{ $invoice->company->currency_symbol }}{{ number_format($invoice->tax_amount, 2) }}</span>
                        </div>
                        @if($invoice->discount_amount > 0)
                        <div class="flex justify-between py-2 text-gray-600">
                            <span>Discount</span>
                            <span>-{{ $invoice->company->currency_symbol }}{{ number_format($invoice->discount_type === 'percentage' ? ($invoice->subtotal * $invoice->discount_amount / 100) : $invoice->discount_amount, 2) }}</span>
                        </div>
                        @endif
                        <div class="flex justify-between py-3 text-xl font-bold border-t-2 border-gray-200 mt-2">
                            <span>Total</span>
                            <span>{{ $invoice->company->currency_symbol }}{{ number_format($invoice->total, 2) }}</span>
                        </div>
                    </div>
                </div>

                @if($invoice->notes)
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Notes</h3>
                    <p class="text-gray-700">{{ $invoice->notes }}</p>
                </div>
                @endif

                @if($invoice->terms)
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Terms & Conditions</h3>
                    <p class="text-gray-700">{{ $invoice->terms }}</p>
                </div>
                @endif

                @if($invoice->company->invoice_footer)
                <div class="text-center text-gray-500 text-sm pt-6 border-t">
                    {{ $invoice->company->invoice_footer }}
                </div>
                @endif

                @if(session('success'))
                <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-3"></i>
                        <p class="font-medium text-green-800">{{ session('success') }}</p>
                    </div>
                </div>
                @endif

                @if(session('error'))
                <div class="mt-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-600 mr-3"></i>
                        <p class="font-medium text-red-800">{{ session('error') }}</p>
                    </div>
                </div>
                @endif

                @if($invoice->status === 'paid')
                <div class="mt-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle text-green-600 mr-3 text-xl"></i>
                        <div>
                            <p class="font-medium text-green-800">Invoice Paid</p>
                            @if($invoice->paid_at)
                            <p class="text-sm text-green-600">Paid on {{ $invoice->paid_at->format('F d, Y') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
                @elseif(isset($paymentMethods) && count($paymentMethods) > 0)
                <div class="mt-8 pt-6 border-t">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Pay This Invoice</h3>
                    
                    @if($invoice->isOverdue())
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-red-600 mr-2"></i>
                            <p class="text-sm text-red-800">This invoice is overdue. Please pay as soon as possible.</p>
                        </div>
                    </div>
                    @endif

                    <div class="mb-4 p-4 bg-indigo-50 border border-indigo-200 rounded-lg">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-700 font-medium">Amount Due:</span>
                            <span class="text-2xl font-bold text-indigo-600">{{ $invoice->company->currency_symbol }}{{ number_format($amountDue ?? $invoice->total, 2) }}</span>
                        </div>
                    </div>

                    <form action="{{ route('invoices.public.payment', $invoice->share_token) }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                            @if(in_array('bank_transfer', $paymentMethods))
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_method" value="bank_transfer" class="hidden peer" required>
                                <div class="p-4 border-2 border-gray-200 rounded-lg hover:border-indigo-500 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 transition text-center">
                                    <i class="fas fa-university text-2xl text-gray-600 mb-2"></i>
                                    <p class="font-medium text-gray-900">Bank Transfer</p>
                                    <p class="text-xs text-gray-500">Pay via bank transfer</p>
                                </div>
                            </label>
                            @endif

                            @if(in_array('stripe', $paymentMethods))
                            <label class="cursor-pointer">
                                <input type="radio" name="payment_method" value="stripe" class="hidden peer">
                                <div class="p-4 border-2 border-gray-200 rounded-lg hover:border-indigo-500 peer-checked:border-indigo-500 peer-checked:bg-indigo-50 transition text-center">
                                    <i class="fab fa-stripe text-2xl text-gray-600 mb-2"></i>
                                    <p class="font-medium text-gray-900">Card Payment</p>
                                    <p class="text-xs text-gray-500">Pay securely with Stripe</p>
                                </div>
                            </label>
                            @endif
                        </div>
                        <button type="submit" class="w-full px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 transition">
                            <i class="fas fa-arrow-right mr-2"></i> Continue to Payment
                        </button>
                    </form>
                </div>
                @endif
            </div>
        </div>

        <p class="text-center text-gray-400 text-sm mt-6">
            Powered by {{ \App\Models\SystemSetting::get('site_name', 'RecurringPress') }}
        </p>
    </div>
</body>
</html>
