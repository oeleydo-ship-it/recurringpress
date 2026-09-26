@extends('superadmin.layout')

@section('title', 'Payout Settings')

@section('content')
    <div class="max-w-4xl">
        <div class="mb-6">
            <p class="text-gray-600">Configure payout fees and limits for tenants using the platform payment gateway.</p>
        </div>

        <form action="{{ route('superadmin.settings.payouts.update') }}" method="POST"
            class="bg-white rounded-xl shadow-sm p-6">
            @csrf
            @method('PUT')

            <div class="space-y-6">
                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-6">
                    <div class="flex">
                        <i class="fas fa-info-circle text-amber-500 mt-0.5 mr-3"></i>
                        <div>
                            <h4 class="text-sm font-medium text-amber-800">How Payout Fees Work</h4>
                            <p class="text-sm text-amber-700 mt-1">
                                When tenants request a payout, the fee percentage is deducted from their payout amount.
                                For example, if a tenant requests $100 with a 3% fee, they will receive $97 and the platform
                                keeps $3.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payout Fee Percentage *</label>
                        <div class="relative">
                            <input type="number" name="payout_fee_percentage" step="0.01" min="0" max="100"
                                value="{{ old('payout_fee_percentage', $settings['payout_fee_percentage']) }}" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500">%</span>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Set to 0 for no fee. e.g., 2.5 = 2.5%</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Payout Amount *</label>
                        <div class="relative">
                            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-500">$</span>
                            <input type="number" name="payout_minimum_amount" step="0.01" min="0"
                                value="{{ old('payout_minimum_amount', $settings['payout_minimum_amount']) }}" required
                                class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">Minimum balance required to request a payout</p>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-sm font-medium text-gray-700 mb-3">Fee Calculation Example</h4>
                    <div class="grid grid-cols-3 gap-4 text-sm" x-data="{ 
                            feePercent: {{ $settings['payout_fee_percentage'] ?? 0 }},
                            sampleAmount: 100,
                            get feeAmount() { return (this.sampleAmount * this.feePercent / 100).toFixed(2); },
                            get netAmount() { return (this.sampleAmount - this.feeAmount).toFixed(2); }
                        }">
                        <div class="bg-white p-3 rounded border border-gray-200">
                            <div class="text-gray-500 text-xs">Gross Amount</div>
                            <div class="font-semibold text-gray-900">$<span x-text="sampleAmount.toFixed(2)"></span></div>
                        </div>
                        <div class="bg-white p-3 rounded border border-gray-200">
                            <div class="text-gray-500 text-xs">Fee (<span x-text="feePercent"></span>%)</div>
                            <div class="font-semibold text-red-600">-$<span x-text="feeAmount"></span></div>
                        </div>
                        <div class="bg-white p-3 rounded border border-gray-200">
                            <div class="text-gray-500 text-xs">Net Amount (Tenant Receives)</div>
                            <div class="font-semibold text-green-600">$<span x-text="netAmount"></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-end mt-8 pt-6 border-t">
                <button type="submit"
                    class="px-6 py-2 bg-sky-500 text-white font-medium rounded-lg hover:bg-sky-600 transition">
                    Save Settings
                </button>
            </div>
        </form>
    </div>
@endsection