<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Transfer Payment - {{ $invoice->invoice_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-blue-600 px-8 py-6 text-white">
                <div class="flex items-center">
                    <i class="fas fa-university text-4xl mr-4"></i>
                    <div>
                        <h1 class="text-2xl font-bold">Bank Transfer Payment</h1>
                        <p class="text-blue-200">Invoice {{ $invoice->invoice_number }}</p>
                    </div>
                </div>
            </div>

            <div class="p-8">
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 font-medium">Amount to Pay:</span>
                        <span class="text-2xl font-bold text-blue-600">{{ $invoice->company->currency_symbol }}{{ number_format($amountDue, 2) }}</span>
                    </div>
                </div>

                <h3 class="text-lg font-semibold text-gray-900 mb-4">Bank Account Details</h3>
                
                <div class="space-y-4 mb-8">
                    @if($settings->bank_name)
                    <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600">Bank Name</span>
                        <span class="font-medium text-gray-900">{{ $settings->bank_name }}</span>
                    </div>
                    @endif

                    @if($settings->bank_account_name)
                    <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600">Account Name</span>
                        <span class="font-medium text-gray-900">{{ $settings->bank_account_name }}</span>
                    </div>
                    @endif

                    @if($settings->bank_account_number)
                    <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600">Account Number</span>
                        <span class="font-medium text-gray-900 font-mono">{{ $settings->bank_account_number }}</span>
                    </div>
                    @endif

                    @if($settings->bank_routing_number)
                    <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600">Routing Number</span>
                        <span class="font-medium text-gray-900 font-mono">{{ $settings->bank_routing_number }}</span>
                    </div>
                    @endif
                </div>

                <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg mb-6">
                    <div class="flex">
                        <i class="fas fa-info-circle text-amber-600 mr-3 mt-1"></i>
                        <div>
                            <p class="font-medium text-amber-800">Important</p>
                            <ul class="text-sm text-amber-700 mt-2 space-y-1">
                                <li>• Please include invoice number <strong>{{ $invoice->invoice_number }}</strong> as reference</li>
                                <li>• Transfer the exact amount shown above</li>
                                <li>• Payment confirmation may take 1-3 business days</li>
                            </ul>
                        </div>
                    </div>
                </div>

                @if($settings->payment_instructions)
                <div class="p-4 bg-gray-50 rounded-lg mb-6">
                    <h4 class="text-sm font-medium text-gray-500 uppercase mb-2">Additional Instructions</h4>
                    <p class="text-gray-700">{{ $settings->payment_instructions }}</p>
                </div>
                @endif

                <a href="{{ route('invoices.public', $invoice->share_token) }}" 
                    class="block w-full text-center px-6 py-3 bg-gray-100 text-gray-700 font-medium rounded-lg hover:bg-gray-200 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Invoice
                </a>
            </div>
        </div>

        <p class="text-center text-gray-400 text-sm mt-6">
            Powered by {{ \App\Models\SystemSetting::get('site_name', 'RecurringPress') }}
        </p>
    </div>
</body>
</html>
