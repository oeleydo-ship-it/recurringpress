<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quotation {{ $quotation->quotation_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-emerald-600 px-8 py-6 text-white">
                <div class="flex justify-between items-start">
                    <div>
                        <h1 class="text-3xl font-bold">QUOTATION</h1>
                        <p class="text-emerald-200 mt-1">{{ $quotation->quotation_number }}</p>
                    </div>
                    <div class="text-right">
                        <span
                            class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium 
                            {{ $quotation->status === 'accepted' ? 'bg-green-500' : ($quotation->status === 'rejected' ? 'bg-red-500' : ($quotation->isExpired() ? 'bg-yellow-500' : 'bg-emerald-500')) }}">
                            {{ strtoupper($quotation->status) }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="p-8">
                @if(!$quotation->isExpired() && $quotation->status === 'sent')
                    <div class="mb-6 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-clock text-amber-600 mr-3"></i>
                            <div>
                                <p class="font-medium text-amber-800">This quotation is valid until
                                    {{ $quotation->valid_until->format('F d, Y') }}</p>
                                <p class="text-sm text-amber-600">{{ $quotation->valid_until->diffForHumans() }}</p>
                            </div>
                        </div>
                    </div>
                @elseif($quotation->isExpired())
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-triangle text-red-600 mr-3"></i>
                            <p class="font-medium text-red-800">This quotation has expired. Please contact us for an updated
                                quote.</p>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                    <div>
                        <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">From</h3>
                        <div class="text-gray-900">
                            @if($quotation->company->logo)
                                <img src="{{ $quotation->company->logo_url }}" alt="{{ $quotation->company->name }}"
                                    class="h-12 mb-2">
                            @endif
                            <p class="font-semibold text-lg">{{ $quotation->company->name }}</p>
                            @if($quotation->company->address)
                                <p class="text-gray-600">{{ $quotation->company->address }}</p>
                            @endif
                            @if($quotation->company->city)
                                <p class="text-gray-600">{{ $quotation->company->city }}, {{ $quotation->company->state }}
                                    {{ $quotation->company->postal_code }}</p>
                            @endif
                            @if($quotation->company->email)
                                <p class="text-gray-600">{{ $quotation->company->email }}</p>
                            @endif
                        </div>
                    </div>

                    <div>
                        <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">To</h3>
                        <div class="text-gray-900">
                            <p class="font-semibold text-lg">{{ $quotation->client->bill_to_name }}</p>
                            @if($quotation->client->bill_to_contact)
                                <p class="text-gray-600">{{ $quotation->client->bill_to_contact }}</p>
                            @endif
                            @if($quotation->client->address)
                                <p class="text-gray-600">{{ $quotation->client->address }}</p>
                            @endif
                            @if($quotation->client->city)
                                <p class="text-gray-600">{{ $quotation->client->city }}, {{ $quotation->client->state }}
                                    {{ $quotation->client->postal_code }}</p>
                            @endif
                            @if($quotation->client->email)
                                <p class="text-gray-600">{{ $quotation->client->email }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8 p-4 bg-gray-50 rounded-lg">
                    <div>
                        <p class="text-sm text-gray-500">Quotation Number</p>
                        <p class="font-semibold">{{ $quotation->quotation_number }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Issue Date</p>
                        <p class="font-semibold">{{ $quotation->issue_date->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Valid Until</p>
                        <p class="font-semibold {{ $quotation->isExpired() ? 'text-red-600' : '' }}">
                            {{ $quotation->valid_until->format('M d, Y') }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Total</p>
                        <p class="font-semibold text-lg">
                            {{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}</p>
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
                            @foreach($quotation->items as $item)
                                <tr class="border-b border-gray-100">
                                    <td class="py-4">
                                        <p class="font-medium text-gray-900">{{ $item->name }}</p>
                                        @if($item->description)
                                            <p class="text-sm text-gray-500">{{ $item->description }}</p>
                                        @endif
                                    </td>
                                    <td class="py-4 text-center text-gray-600">{{ $item->quantity }} {{ $item->unit }}</td>
                                    <td class="py-4 text-right text-gray-600">
                                        {{ $quotation->company->currency_symbol }}{{ number_format($item->price, 2) }}</td>
                                    <td class="py-4 text-right font-medium text-gray-900">
                                        {{ $quotation->company->currency_symbol }}{{ number_format($item->total, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="flex justify-end mb-8">
                    <div class="w-72">
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
                        <div class="flex justify-between py-3 text-xl font-bold border-t-2 border-gray-200 mt-2">
                            <span>Total</span>
                            <span>{{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}</span>
                        </div>
                    </div>
                </div>

                @if($quotation->notes)
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Notes</h3>
                        <p class="text-gray-700">{{ $quotation->notes }}</p>
                    </div>
                @endif

                @if($quotation->terms)
                    <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                        <h3 class="text-sm font-medium text-gray-500 uppercase mb-2">Terms & Conditions</h3>
                        <p class="text-gray-700">{{ $quotation->terms }}</p>
                    </div>
                @endif

                @if(session('success'))
                    <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-600 mr-3"></i>
                            <p class="font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-600 mr-3"></i>
                            <p class="font-medium text-red-800">{{ session('error') }}</p>
                        </div>
                    </div>
                @endif

                @if($quotation->status === 'sent' && !$quotation->isExpired())
                    <div class="border-t pt-6 mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Respond to this Quotation</h3>
                        <div class="flex flex-col sm:flex-row gap-4">
                            <button type="button"
                                onclick="document.getElementById('acceptModal').classList.remove('hidden')"
                                class="flex-1 px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition flex items-center justify-center">
                                <i class="fas fa-check mr-2"></i> Accept Quotation
                            </button>
                            <button type="button"
                                onclick="document.getElementById('rejectModal').classList.remove('hidden')"
                                class="flex-1 px-6 py-3 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition flex items-center justify-center">
                                <i class="fas fa-times mr-2"></i> Reject Quotation
                            </button>
                        </div>
                    </div>
                @endif



                @if($quotation->status === 'accepted' && $quotation->responded_at)
                    <div class="border-t pt-6 mt-6">
                        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-check-circle text-green-600 mr-3 text-xl"></i>
                                <div>
                                    <p class="font-medium text-green-800">Quotation Accepted</p>
                                    <p class="text-sm text-green-600">Accepted on
                                        {{ $quotation->responded_at->format('F d, Y \a\t h:i A') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($quotation->status === 'rejected' && $quotation->responded_at)
                    <div class="border-t pt-6 mt-6">
                        <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-times-circle text-red-600 mr-3 text-xl"></i>
                                <div>
                                    <p class="font-medium text-red-800">Quotation Rejected</p>
                                    <p class="text-sm text-red-600">Rejected on
                                        {{ $quotation->responded_at->format('F d, Y \a\t h:i A') }}</p>
                                    @if($quotation->response_notes)
                                        <p class="text-sm text-red-600 mt-1">Reason: {{ $quotation->response_notes }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                @if($quotation->status === 'converted')
                    <div class="border-t pt-6 mt-6">
                        <div class="p-4 bg-purple-50 border border-purple-200 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-file-invoice text-purple-600 mr-3 text-xl"></i>
                                <div>
                                    <p class="font-medium text-purple-800">Converted to Invoice</p>
                                    <p class="text-sm text-purple-600">This quotation has been converted to an invoice.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <p class="text-center text-gray-400 text-sm mt-6">
            Powered by {{ \App\Models\SystemSetting::get('site_name', 'RecurringPress') }}
        </p>
    </div>

    <!-- Accept Modal -->
    <div id="acceptModal" class="hidden fixed inset-0 bg-slate-700/60 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Accept Quotation</h3>
                <p class="text-gray-600 mb-4">Are you sure you want to accept this quotation for
                    <strong>{{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}</strong>?
                </p>
                <form action="{{ route('quotations.public.accept', $quotation->share_token) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Additional Notes (optional)</label>
                        <textarea name="notes" rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"
                            placeholder="Any special requirements or notes..."></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="document.getElementById('acceptModal').classList.add('hidden')"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                            <i class="fas fa-check mr-2"></i> Accept
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="hidden fixed inset-0 bg-slate-700/60 flex items-center justify-center z-50 p-4">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full">
            <div class="p-6">
                <h3 class="text-xl font-bold text-gray-900 mb-4">Reject Quotation</h3>
                <p class="text-gray-600 mb-4">Are you sure you want to reject this quotation?</p>
                <form action="{{ route('quotations.public.reject', $quotation->share_token) }}" method="POST">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason for rejection
                            (optional)</label>
                        <textarea name="rejection_reason" rows="3"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500"
                            placeholder="Please let us know why you're rejecting this quotation..."></textarea>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')"
                            class="flex-1 px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button type="submit"
                            class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            <i class="fas fa-times mr-2"></i> Reject
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>