<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Card Payment - {{ $quotation->quotation_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://js.stripe.com/v3/"></script>
</head>
<body class="bg-gray-100 min-h-screen py-8">
    <div class="max-w-lg mx-auto px-4">
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <div class="bg-sky-500 px-8 py-6 text-white">
                <div class="flex items-center">
                    <i class="fab fa-stripe text-4xl mr-4"></i>
                    <div>
                        <h1 class="text-2xl font-bold">Card Payment</h1>
                        <p class="text-sky-100">Quotation {{ $quotation->quotation_number }}</p>
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="mb-6 p-4 bg-sky-50 border border-sky-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-700 font-medium">Amount to Pay:</span>
                        <span class="text-2xl font-bold text-sky-600">{{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}</span>
                    </div>
                </div>

                <div id="payment-message" class="hidden mb-4 p-4 rounded-lg"></div>

                <form id="payment-form">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Card Information</label>
                        <div class="space-y-3">
                            <div id="card-number-element" class="p-3 border border-gray-300 rounded-lg bg-white"></div>
                            <div class="grid grid-cols-2 gap-3">
                                <div id="card-expiry-element" class="p-3 border border-gray-300 rounded-lg bg-white"></div>
                                <div id="card-cvc-element" class="p-3 border border-gray-300 rounded-lg bg-white"></div>
                            </div>
                        </div>
                        <div id="card-errors" class="mt-2 text-sm text-red-600"></div>
                    </div>

                    <button type="submit" id="submit-button" 
                        class="w-full px-6 py-3 bg-sky-500 text-white font-medium rounded-lg hover:bg-sky-600 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span id="button-text">
                            <i class="fas fa-lock mr-2"></i> Pay {{ $quotation->company->currency_symbol }}{{ number_format($quotation->total, 2) }}
                        </span>
                        <span id="spinner" class="hidden">
                            <i class="fas fa-spinner fa-spin mr-2"></i> Processing...
                        </span>
                    </button>
                </form>

                <div class="mt-6 flex items-center justify-center text-gray-500 text-sm">
                    <i class="fas fa-shield-alt mr-2"></i>
                    Secured by Stripe
                </div>

                <a href="{{ route('quotations.public', $quotation->share_token) }}" 
                    class="block w-full text-center px-6 py-3 mt-4 text-gray-600 hover:text-gray-900 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Quotation
                </a>
            </div>
        </div>

        <p class="text-center text-gray-400 text-sm mt-6">
            Powered by {{ \App\Models\SystemSetting::get('site_name', 'RecurringPress') }}
        </p>
    </div>

    <script>
        const stripe = Stripe('{{ $publishableKey }}');
        let elements;
        let cardNumberElement, cardExpiryElement, cardCvcElement;
        let clientSecret;

        initialize();

        async function initialize() {
            const response = await fetch('{{ route("quotations.public.stripe.intent", $quotation->share_token) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            });

            const data = await response.json();

            if (data.error) {
                showMessage(data.error, 'error');
                return;
            }

            clientSecret = data.clientSecret;
            elements = stripe.elements();
            
            const style = {
                base: {
                    fontSize: '16px',
                    color: '#374151',
                    fontFamily: 'system-ui, -apple-system, sans-serif',
                    '::placeholder': {
                        color: '#9CA3AF',
                    },
                },
                invalid: {
                    color: '#EF4444',
                },
            };

            cardNumberElement = elements.create('cardNumber', { style, placeholder: '1234 1234 1234 1234' });
            cardExpiryElement = elements.create('cardExpiry', { style, placeholder: 'MM / YY' });
            cardCvcElement = elements.create('cardCvc', { style, placeholder: 'CVC' });
            
            cardNumberElement.mount('#card-number-element');
            cardExpiryElement.mount('#card-expiry-element');
            cardCvcElement.mount('#card-cvc-element');

            [cardNumberElement, cardExpiryElement, cardCvcElement].forEach(element => {
                element.on('change', function(event) {
                    const displayError = document.getElementById('card-errors');
                    if (event.error) {
                        displayError.textContent = event.error.message;
                    } else {
                        displayError.textContent = '';
                    }
                });
            });
        }

        document.getElementById('payment-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            setLoading(true);

            const { error, paymentIntent } = await stripe.confirmCardPayment(
                clientSecret,
                {
                    payment_method: {
                        card: cardNumberElement,
                    }
                }
            );

            if (error) {
                showMessage(error.message, 'error');
                setLoading(false);
            } else if (paymentIntent.status === 'succeeded') {
                window.location.href = '{{ route("quotations.public.stripe.success", $quotation->share_token) }}?payment_intent=' + paymentIntent.id;
            }
        });

        function setLoading(isLoading) {
            const submitButton = document.getElementById('submit-button');
            const buttonText = document.getElementById('button-text');
            const spinner = document.getElementById('spinner');

            submitButton.disabled = isLoading;
            buttonText.classList.toggle('hidden', isLoading);
            spinner.classList.toggle('hidden', !isLoading);
        }

        function showMessage(message, type) {
            const messageDiv = document.getElementById('payment-message');
            messageDiv.textContent = message;
            messageDiv.classList.remove('hidden', 'bg-red-50', 'text-red-800', 'bg-green-50', 'text-green-800');
            
            if (type === 'error') {
                messageDiv.classList.add('bg-red-50', 'text-red-800', 'border', 'border-red-200');
            } else {
                messageDiv.classList.add('bg-green-50', 'text-green-800', 'border', 'border-green-200');
            }
        }
    </script>
</body>
</html>
