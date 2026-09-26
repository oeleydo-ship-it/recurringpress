@extends('layouts.app')

@section('title', 'Request Payout - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Request Payout')

@section('content')
<div class="mb-6">
    <a href="{{ route('payouts.index') }}" class="text-primary hover:opacity-80">
        <i class="fas fa-arrow-left mr-2"></i> Back to Payouts
    </a>
</div>

<div class="max-w-2xl mx-auto" x-data="{ payoutMethod: '{{ old('payout_method', 'bank_transfer') }}' }">
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h1 class="text-xl font-bold text-gray-900">Request Payout</h1>
            <p class="text-gray-600 text-sm">Withdraw your earnings to your bank account or PayPal</p>
        </div>

        <div class="p-6">
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center justify-between">
                    <span class="text-gray-700 font-medium">Available Balance:</span>
                    <span class="text-2xl font-bold text-green-600">{{ auth()->user()->company->currency_symbol }}{{ number_format($availableBalance, 2) }}</span>
                </div>
            </div>

            <form action="{{ route('payouts.store') }}" method="POST" class="space-y-6">
                @csrf

                <div x-data="{ 
                    amount: {{ old('amount', $availableBalance) }},
                    feePercent: {{ $feePercentage }},
                    minAmount: {{ $minimumAmount }},
                    get fee() { return (this.amount * this.feePercent / 100).toFixed(2); },
                    get netAmount() { return (this.amount - this.fee).toFixed(2); }
                }">
                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Payout Amount *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">{{ auth()->user()->company->currency_symbol }}</span>
                        <input type="number" name="amount" id="amount" step="0.01" :min="minAmount" max="{{ $availableBalance }}"
                               x-model="amount" required
                               class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Minimum amount: {{ auth()->user()->company->currency_symbol }}{{ number_format($minimumAmount, 2) }}</p>
                    @error('amount')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    @if($feePercentage > 0)
                    <div class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                        <h4 class="text-sm font-medium text-gray-700 mb-3">
                            <i class="fas fa-calculator mr-1"></i> Payout Calculation
                        </h4>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Payout Amount</span>
                                <span class="font-medium">{{ auth()->user()->company->currency_symbol }}<span x-text="parseFloat(amount).toFixed(2)"></span></span>
                            </div>
                            <div class="flex justify-between text-red-600">
                                <span>Platform Fee ({{ $feePercentage }}%)</span>
                                <span class="font-medium">-{{ auth()->user()->company->currency_symbol }}<span x-text="fee"></span></span>
                            </div>
                            <hr class="border-gray-300">
                            <div class="flex justify-between text-green-600 font-semibold">
                                <span>You Will Receive</span>
                                <span>{{ auth()->user()->company->currency_symbol }}<span x-text="netAmount"></span></span>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Payout Method *</label>
                    <div class="grid grid-cols-2 gap-4">
                        <label class="cursor-pointer">
                            <input type="radio" name="payout_method" value="bank_transfer" x-model="payoutMethod" class="hidden peer">
                            <div class="p-4 border-2 border-gray-200 rounded-lg border-primary-hover peer-checked:border-primary peer-checked:bg-primary-light transition text-center">
                                <i class="fas fa-university text-2xl text-gray-600 mb-2"></i>
                                <p class="font-medium text-gray-900">Bank Transfer</p>
                                <p class="text-xs text-gray-500">IBAN or Account Number</p>
                            </div>
                        </label>
                        <label class="cursor-pointer">
                            <input type="radio" name="payout_method" value="paypal" x-model="payoutMethod" class="hidden peer">
                            <div class="p-4 border-2 border-gray-200 rounded-lg hover:border-blue-400 peer-checked:border-blue-500 peer-checked:bg-blue-50 transition text-center">
                                <i class="fab fa-paypal text-2xl text-blue-600 mb-2"></i>
                                <p class="font-medium text-gray-900">PayPal</p>
                                <p class="text-xs text-gray-500">Fast & secure</p>
                            </div>
                        </label>
                    </div>
                    @error('payout_method')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div x-show="payoutMethod === 'bank_transfer'" x-cloak class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4"><i class="fas fa-university mr-2 text-gray-500"></i> Bank Account Details</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label for="bank_name" class="block text-sm font-medium text-gray-700 mb-1">Bank Name *</label>
                            <input type="text" name="bank_name" id="bank_name" 
                                   value="{{ old('bank_name', $settings->bank_name ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            @error('bank_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="bank_account_name" class="block text-sm font-medium text-gray-700 mb-1">Account Holder Name *</label>
                            <input type="text" name="bank_account_name" id="bank_account_name" 
                                   value="{{ old('bank_account_name', $settings->bank_account_name ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            @error('bank_account_name')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="bank_iban" class="block text-sm font-medium text-gray-700 mb-1">IBAN</label>
                            <input type="text" name="bank_iban" id="bank_iban" 
                                   value="{{ old('bank_iban') }}" placeholder="e.g., DE89370400440532013000"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary font-mono">
                            @error('bank_iban')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="bank_swift" class="block text-sm font-medium text-gray-700 mb-1">SWIFT/BIC Code</label>
                            <input type="text" name="bank_swift" id="bank_swift" 
                                   value="{{ old('bank_swift') }}" placeholder="e.g., COBADEFFXXX"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary font-mono">
                            @error('bank_swift')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="bank_account_number" class="block text-sm font-medium text-gray-700 mb-1">Account Number (if no IBAN)</label>
                            <input type="text" name="bank_account_number" id="bank_account_number" 
                                   value="{{ old('bank_account_number', $settings->bank_account_number ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            @error('bank_account_number')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="md:col-span-2">
                            <label for="bank_routing_number" class="block text-sm font-medium text-gray-700 mb-1">Routing Number (US banks)</label>
                            <input type="text" name="bank_routing_number" id="bank_routing_number" 
                                   value="{{ old('bank_routing_number', $settings->bank_routing_number ?? '') }}"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                            @error('bank_routing_number')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <div x-show="payoutMethod === 'paypal'" x-cloak class="border-t pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4"><i class="fab fa-paypal mr-2 text-blue-600"></i> PayPal Details</h3>
                    
                    <div>
                        <label for="paypal_email" class="block text-sm font-medium text-gray-700 mb-1">PayPal Email Address *</label>
                        <input type="email" name="paypal_email" id="paypal_email" 
                               value="{{ old('paypal_email') }}" placeholder="your-email@example.com"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                        <p class="text-xs text-gray-500 mt-1">Enter the email address associated with your PayPal account</p>
                        @error('paypal_email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes (Optional)</label>
                    <textarea name="notes" id="notes" rows="3"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('notes') }}</textarea>
                </div>

                <div class="flex justify-end space-x-3 pt-4 border-t">
                    <a href="{{ route('payouts.index') }}" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <button type="submit" class="px-6 py-2 bg-primary text-white rounded-lg hover:opacity-90 transition">
                        <i class="fas fa-paper-plane mr-2"></i> Request Payout
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
