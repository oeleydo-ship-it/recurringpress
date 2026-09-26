@extends('layouts.onboarding')

@section('title', 'Welcome - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))

@section('content')
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="max-w-lg w-full">
            <!-- Welcome Card -->
            <div class="bg-white rounded-2xl card-shadow p-8 text-center">
                <!-- Success Icon -->
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i class="fas fa-check-circle text-green-500 text-4xl"></i>
                </div>

                <!-- Welcome Message -->
                <h1 class="text-3xl font-bold text-gray-900 mb-3">
                    Welcome, {{ $user->name }}! 🎉
                </h1>

                <p class="text-gray-600 mb-8 text-lg">
                    Your account is now verified and ready to go. Let's set up your company
                    <strong>{{ $company->name }}</strong> to get the most out of your experience.
                </p>

                <!-- Setup Steps Preview -->
                <div class="bg-gray-50 rounded-xl p-6 mb-8 text-left">
                    <h3 class="font-semibold text-gray-900 mb-4">
                        <i class="fas fa-clipboard-list mr-2 text-primary-500"></i>
                        Quick Setup Guide
                    </h3>
                    <ul class="space-y-3 text-sm text-gray-600">
                        <li class="flex items-start">
                            <i class="fas fa-image text-primary-500 mt-1 w-5 mr-3"></i>
                            <span>Upload your company logo for professional invoices</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt text-primary-500 mt-1 w-5 mr-3"></i>
                            <span>Add your business address and contact details</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-credit-card text-primary-500 mt-1 w-5 mr-3"></i>
                            <span>Configure payment methods (Stripe, Bank, PayPal)</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-percent text-primary-500 mt-1 w-5 mr-3"></i>
                            <span>Set up tax rates for your invoices</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-palette text-primary-500 mt-1 w-5 mr-3"></i>
                            <span>Customize your theme and invoice design</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-users text-primary-500 mt-1 w-5 mr-3"></i>
                            <span>Invite team members to collaborate</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-rocket text-primary-500 mt-1 w-5 mr-3"></i>
                            <span>Explore premium features</span>
                        </li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="space-y-3">
                    <a href="{{ route('onboarding.setup') }}"
                        class="w-full inline-flex items-center justify-center px-6 py-3 bg-sky-500 hover:bg-sky-600 text-white font-semibold rounded-xl transition transform hover:scale-[1.02]">
                        <i class="fas fa-arrow-right mr-2"></i>
                        Let's Get Started
                    </a>

                    <form action="{{ route('onboarding.skip') }}" method="POST" class="inline w-full">
                        @csrf
                        <button type="submit"
                            class="w-full px-6 py-3 text-gray-500 hover:text-gray-700 font-medium transition">
                            Skip for now, take me to the dashboard →
                        </button>
                    </form>
                </div>

                <!-- Time Estimate -->
                <p class="text-gray-400 text-sm mt-6">
                    <i class="fas fa-clock mr-1"></i>
                    This takes about 2-3 minutes
                </p>
            </div>
        </div>
    </div>
@endsection