@extends('superadmin.layout')

@section('title', 'Stripe Settings')

@section('content')
<div class="max-w-4xl">
    <div class="mb-6">
        <p class="text-gray-600">Configure Stripe payment gateway for subscription payments.</p>
    </div>

    <form action="{{ route('superadmin.settings.stripe.update') }}" method="POST" class="bg-white rounded-xl shadow-sm p-6">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                <div>
                    <h3 class="font-medium text-gray-900">Enable Stripe Payments</h3>
                    <p class="text-sm text-gray-500">Allow customers to pay for subscriptions via Stripe</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="stripe_enabled" value="1" {{ $settings['stripe_enabled'] ? 'checked' : '' }} class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-sky-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-sky-500"></div>
                </label>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Mode</label>
                <div class="flex space-x-4">
                    <label class="flex items-center">
                        <input type="radio" name="stripe_mode" value="test" {{ $settings['stripe_mode'] === 'test' ? 'checked' : '' }}
                               class="w-4 h-4 text-sky-500 border-gray-300 focus:ring-sky-500">
                        <span class="ml-2 text-gray-700">Test Mode</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="stripe_mode" value="live" {{ $settings['stripe_mode'] === 'live' ? 'checked' : '' }}
                               class="w-4 h-4 text-sky-500 border-gray-300 focus:ring-sky-500">
                        <span class="ml-2 text-gray-700">Live Mode</span>
                    </label>
                </div>
            </div>

            <div class="border-t pt-6">
                <h3 class="font-medium text-gray-900 mb-4">Test API Keys</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Publishable Key</label>
                        <input type="text" name="stripe_test_publishable_key" value="{{ $settings['stripe_test_publishable_key'] }}"
                               placeholder="pk_test_..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 font-mono text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Secret Key</label>
                        <input type="password" name="stripe_test_secret_key" value="{{ $settings['stripe_test_secret_key'] }}"
                               placeholder="sk_test_..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 font-mono text-sm">
                    </div>
                </div>
            </div>

            <div class="border-t pt-6">
                <h3 class="font-medium text-gray-900 mb-4">Live API Keys</h3>
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Publishable Key</label>
                        <input type="text" name="stripe_live_publishable_key" value="{{ $settings['stripe_live_publishable_key'] }}"
                               placeholder="pk_live_..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 font-mono text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Secret Key</label>
                        <input type="password" name="stripe_live_secret_key" value="{{ $settings['stripe_live_secret_key'] }}"
                               placeholder="sk_live_..."
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 font-mono text-sm">
                    </div>
                </div>
            </div>

            <div class="border-t pt-6">
                <h3 class="font-medium text-gray-900 mb-4">Webhook</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Webhook Secret</label>
                    <input type="password" name="stripe_webhook_secret" value="{{ $settings['stripe_webhook_secret'] }}"
                           placeholder="whsec_..."
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500 font-mono text-sm">
                    <p class="text-xs text-gray-500 mt-1">Webhook URL: {{ url('/stripe/webhook') }}</p>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end mt-8 pt-6 border-t">
            <button type="submit" class="px-6 py-2 bg-sky-500 text-white font-medium rounded-lg hover:bg-sky-600 transition">
                Save Settings
            </button>
        </div>
    </form>
</div>
@endsection
