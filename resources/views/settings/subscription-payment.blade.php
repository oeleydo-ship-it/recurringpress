@extends('layouts.app')

@section('title', 'Upgrade Subscription - ' . $company->name)
@section('page-title', 'Upgrade Subscription')

@section('content')
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="bg-gradient-to-r from-sky-500 to-sky-600 px-8 py-6 text-white">
                <h1 class="text-2xl font-bold">Upgrade Subscription</h1>
                <p class="text-primary mt-1">Complete your payment to upgrade to the {{ $plan->name }} plan.</p>
            </div>

            <div class="p-8">
                <div class="mb-8 p-4 bg-primary-light border border-primary rounded-lg flex justify-between items-center">
                    <div>
                        <h3 class="font-bold text-gray-900">{{ $plan->name }} Plan</h3>
                        <p class="text-sm text-gray-500">{{ $plan->description }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-2xl font-bold text-primary">${{ number_format($plan->price, 2) }}</p>
                        <p class="text-xs text-gray-500">per month</p>
                    </div>
                </div>

                <div id="payment-message" class="hidden mb-6 p-4 rounded-lg text-sm"></div>

                <form id="subscription-payment-form">
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Card Information</label>
                        <div class="p-4 border border-gray-300 rounded-lg bg-gray-50">
                            <div id="card-element">
                                <!-- Stripe Element will be inserted here. -->
                            </div>
                        </div>
                        <div id="card-errors" class="mt-2 text-sm text-red-600" role="alert"></div>
                    </div>

                    <div class="flex items-center justify-between mt-8">
                        <a href="{{ route('settings.subscription') }}" class="text-gray-500 hover:text-gray-700 font-medium">
                            Cancel
                        </a>
                        <button type="submit" id="submit-button"
                            class="px-6 py-3 bg-primary text-white font-medium rounded-lg bg-primary-hover transition shadow-lg shadow-sky-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                            <span id="button-text">Pay ${{ number_format($plan->price, 2) }} & Upgrade</span>
                            <span id="spinner" class="hidden ml-2">
                                <i class="fas fa-circle-notch fa-spin"></i>
                            </span>
                        </button>
                    </div>
                </form>

                <div class="mt-6 flex items-center justify-center text-gray-400 text-xs">
                    <i class="fas fa-lock mr-1"></i> Secured by Stripe
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://js.stripe.com/v3/"></script>
        <script>

            async function initPayment() {
                const messageDiv = document.getElementById('payment-message');
                const submitButton = document.getElementById('submit-button');
                const spinner = document.getElementById('spinner');
                const buttonText = document.getElementById('button-text');

                try {
                    const response = await fetch('{{ route("settings.subscription.intent", $plan->id) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    });

                    if (!response.ok) {
                        const text = await response.text();
                        try {
                            const errorData = JSON.parse(text);
                            throw new Error(errorData.error || 'Server error: ' + response.status);
                        } catch (e) {
                            throw new Error('Server returned ' + response.status + '. Check logs.');
                        }
                    }

                    const data = await response.json();

                    if (data.error) {
                        showMessage(data.error, 'error');
                        submitButton.disabled = true;
                        return;
                    }

                    if (!data.publishableKey) {
                        throw new Error('Stripe Publishable Key is missing. Please check system settings.');
                    }

                    const stripe = Stripe(data.publishableKey);
                    const elements = stripe.elements();
                    const cardElement = elements.create('card', {
                        style: {
                            base: {
                                fontSize: '16px',
                                color: '#32325d',
                                fontFamily: '"Inter", sans-serif',
                                '::placeholder': {
                                    color: '#aab7c4'
                                }
                            },
                            invalid: {
                                color: '#fa755a',
                                iconColor: '#fa755a'
                            }
                        }
                    });

                    cardElement.mount('#card-element');

                    const form = document.getElementById('subscription-payment-form');

                    form.addEventListener('submit', async (event) => {
                        event.preventDefault();
                        setLoading(true);

                        const { paymentIntent, error } = await stripe.confirmCardPayment(data.clientSecret, {
                            payment_method: {
                                card: cardElement,
                                billing_details: {
                                    name: '{{ $company->name }}',
                                    email: '{{ $company->email }}'
                                }
                            }
                        });

                        if (error) {
                            showMessage(error.message, 'error');
                            setLoading(false);
                        } else if (paymentIntent.status === 'succeeded') {
                            showMessage('Payment successful! Upgrading your plan...', 'success');
                            window.location.href = '{{ route("settings.subscription.success", $plan->id) }}?payment_intent=' + paymentIntent.id;
                        }
                    });

                } catch (e) {
                    console.error(e);
                    showMessage(e.message || 'Failed to initialize payment system.', 'error');
                }

                function showMessage(text, type) {
                    messageDiv.textContent = text;
                    messageDiv.classList.remove('hidden', 'bg-red-50', 'text-red-700', 'bg-green-50', 'text-green-700');
                    if (type === 'error') {
                        messageDiv.classList.add('bg-red-50', 'text-red-700');
                    } else {
                        messageDiv.classList.add('bg-green-50', 'text-green-700');
                    }
                    messageDiv.classList.remove('hidden');
                }

                function setLoading(isLoading) {
                    if (isLoading) {
                        submitButton.disabled = true;
                        spinner.classList.remove('hidden');
                        buttonText.classList.add('opacity-0'); // Hide text but keep width
                    } else {
                        submitButton.disabled = false;
                        spinner.classList.add('hidden');
                        buttonText.classList.remove('opacity-0');
                    }
                }
            }

            document.addEventListener('DOMContentLoaded', initPayment);
        </script>
    @endpush
@endsection