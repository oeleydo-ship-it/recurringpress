<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Successful - {{ $quotation->quotation_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-2xl mx-auto px-4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-green-600 px-8 py-6 text-white text-center">
                <i class="fas fa-check-circle text-6xl mb-4"></i>
                <h1 class="text-3xl font-bold">Payment Successful!</h1>
                <p class="text-green-200 mt-2">Thank you for your payment</p>
            </div>

            <div class="p-8">
                <div class="mb-8 p-6 bg-green-50 border border-green-200 rounded-lg text-center">
                    <p class="text-gray-600 mb-2">Amount Paid</p>
                    <p class="text-3xl font-bold text-green-600">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}</p>
                </div>

                <div class="space-y-4 mb-8">
                    <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600">Quotation Number</span>
                        <span class="font-medium text-gray-900">{{ $quotation->quotation_number }}</span>
                    </div>
                    <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600">Invoice Number</span>
                        <span class="font-medium text-gray-900">{{ $invoice->invoice_number }}</span>
                    </div>
                    <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600">Transaction ID</span>
                        <span class="font-medium text-gray-900 font-mono text-sm">{{ $paymentIntent->id }}</span>
                    </div>
                    <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600">Payment Date</span>
                        <span class="font-medium text-gray-900">{{ now()->format('F d, Y \a\t h:i A') }}</span>
                    </div>
                    <div class="flex justify-between p-3 bg-gray-50 rounded-lg">
                        <span class="text-gray-600">Payment Method</span>
                        <span class="font-medium text-gray-900">Credit/Debit Card (Stripe)</span>
                    </div>
                </div>

                <div class="p-4 bg-blue-50 border border-blue-200 rounded-lg mb-6">
                    <div class="flex">
                        <i class="fas fa-info-circle text-blue-600 mr-3 mt-1"></i>
                        <div>
                            <p class="font-medium text-blue-800">What's Next?</p>
                            <ul class="text-sm text-blue-700 mt-2 space-y-1">
                                <li>• An invoice has been automatically generated</li>
                                <li>• You will receive a confirmation email shortly</li>
                                <li>• A receipt is available for download</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="text-center">
                    <p class="text-gray-600 mb-4">Thank you for your business!</p>
                    <p class="text-gray-500 text-sm">{{ $quotation->company->name }}</p>
                </div>
            </div>
        </div>

        <p class="text-center text-gray-400 text-sm mt-6">
            Powered by {{ \App\Models\SystemSetting::get('site_name', 'RecurringPress') }}
        </p>
    </div>
</body>
</html>
