@extends('layouts.app')

@section('title', 'Subscription & Billing')
@section('page-title', 'Subscription & Billing')

@section('header')
    <h2 class="font-semibold text-xl text-gray-800 leading-tight">
        {{ __('Subscription & Billing') }}
    </h2>
@endsection

@section('content')
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">

        <!-- Current Plan Status -->
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-8">
            <div class="p-6 bg-white border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 mb-6">Current Subscription</h3>

                <div class="flex flex-col md:flex-row gap-8">
                    <div class="flex-1">
                        <div class="flex items-center mb-4">
                            <div class="bg-primary-light p-3 rounded-lg mr-4">
                                <i class="fas fa-crown text-primary text-2xl"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Current Plan</p>
                                <h4 class="text-2xl font-bold text-gray-900">{{ $currentPlan->name ?? 'Free Plan' }}</h4>
                            </div>
                        </div>

                        <div class="flex items-center space-x-4 text-sm">
                            <span class="px-3 py-1 rounded-full 
                                    {{ $company->subscription_status === 'active' ? 'bg-green-100 text-green-800' :
        ($company->subscription_status === 'trial' ? 'bg-indigo-100 text-indigo-800' : 'bg-red-100 text-red-800') }}">
                                {{ ucfirst($company->subscription_status) }}
                            </span>

                            @if($company->subscription_ends_at)
                                <span class="text-gray-500">
                                    {{ $company->subscription_status === 'trial' ? 'Trial ends' : 'Renews' }} on
                                    {{ $company->subscription_ends_at->format('M d, Y') }}
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex-1 border-l pl-8 border-gray-100">
                        <p class="text-sm text-gray-500 mb-4">Plan Usage</p>

                        <!-- Invoices Usage -->
                        <div class="mb-4">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-700">Invoices</span>
                                <span class="text-gray-900 font-medium">
                                    {{ $company->invoices()->count() }} / {{ $currentPlan->max_invoices ?? '∞' }}
                                </span>
                            </div>
                            @if($currentPlan && $currentPlan->max_invoices)
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-primary h-2 rounded-full"
                                        style="width: {{ min(($company->invoices()->count() / $currentPlan->max_invoices) * 100, 100) }}%">
                                    </div>
                                </div>
                            @else
                                <div class="w-full bg-green-100 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: 100%"></div>
                                </div>
                            @endif
                        </div>

                        <!-- Clients Usage -->
                        <div class="mb-4">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-gray-700">Clients</span>
                                <span class="text-gray-900 font-medium">
                                    {{ $company->clients()->count() }} / {{ $currentPlan->max_clients ?? '∞' }}
                                </span>
                            </div>
                            @if($currentPlan && $currentPlan->max_clients)
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-primary h-2 rounded-full"
                                        style="width: min(($company->clients()->count() / $currentPlan->max_clients) * 100, 100)%">
                                    </div>
                                </div>
                            @else
                                <div class="w-full bg-green-100 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: 100%"></div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Available Plans -->
        <div>
            <h3 class="text-lg font-medium text-gray-900 mb-6">Available Plans</h3>
            <div class="grid md:grid-cols-3 gap-6">
                @foreach($plans as $plan)
                    @php
                        $isCurrentPlan = $currentPlan && $currentPlan->id === $plan->id;
                    @endphp
                    <div
                        class="bg-white rounded-xl shadow-sm border {{ $isCurrentPlan ? 'border-primary ring-2 ring-primary' : 'border-gray-200' }} p-6 relative">
                        @if($isCurrentPlan)
                            <div
                                class="absolute top-0 right-0 bg-primary text-white text-xs font-bold px-3 py-1 rounded-bl-xl rounded-tr-xl">
                                CURRENT PLAN
                            </div>
                        @endif

                        @if($plan->is_popular && !$isCurrentPlan)
                            <div
                                class="absolute top-0 left-0 bg-orange-500 text-white text-xs font-bold px-3 py-1 rounded-br-xl rounded-tl-xl">
                                POPULAR
                            </div>
                        @endif

                        <h4 class="text-xl font-bold text-gray-900 mb-2">{{ $plan->name }}</h4>
                        <p class="text-gray-500 text-sm mb-4">{{ $plan->description }}</p>

                        <div class="mb-6">
                            <span class="text-3xl font-bold text-gray-900">${{ number_format($plan->price, 0) }}</span>
                            <span class="text-gray-500">/{{ $plan->billing_cycle === 'yearly' ? 'year' : 'month' }}</span>
                        </div>

                        <ul class="space-y-3 mb-8 text-sm">
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                {{ $plan->max_invoices ? $plan->max_invoices . ' invoices' : 'Unlimited invoices' }}
                            </li>
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                {{ $plan->max_clients ? $plan->max_clients . ' clients' : 'Unlimited clients' }}
                            </li>
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                {{ $plan->max_users ? $plan->max_users . ' users' : 'Unlimited users' }}
                            </li>
                            <li class="flex items-center text-gray-600">
                                <i class="fas fa-check text-green-500 mr-2"></i>
                                {{ $plan->max_products ? $plan->max_products . ' products' : 'Unlimited products' }}
                            </li>
                        </ul>

                        @if($isCurrentPlan)
                            <button disabled class="w-full py-2 bg-primary-light text-primary font-medium rounded-lg cursor-default">
                                <i class="fas fa-check mr-2"></i> Current Plan
                            </button>
                        @elseif($currentPlan && $plan->price > $currentPlan->price)
                            <form action="{{ route('settings.subscription.change', $plan) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-2 bg-primary text-white font-medium rounded-lg bg-primary-hover transition">
                                    <i class="fas fa-arrow-up mr-2"></i> Upgrade
                                </button>
                            </form>
                        @elseif($currentPlan && $plan->price < $currentPlan->price)
                            <form action="{{ route('settings.subscription.change', $plan) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-2 bg-gray-200 text-gray-700 font-medium rounded-lg hover:bg-gray-300 transition">
                                    <i class="fas fa-arrow-down mr-2"></i> Downgrade
                                </button>
                            </form>
                        @else
                            <form action="{{ route('settings.subscription.change', $plan) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full py-2 bg-primary text-white font-medium rounded-lg bg-primary-hover transition">
                                    Select Plan
                                </button>
                            </form>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection