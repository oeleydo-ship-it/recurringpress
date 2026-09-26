@extends('superadmin.layout')

@section('title', 'User Details')

@section('content')
    <div class="mb-6">
        <a href="{{ route('superadmin.users.index') }}" class="text-sky-500 hover:text-sky-600">
            <i class="fas fa-arrow-left mr-2"></i>Back to Users
        </a>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        {{-- User Profile Card --}}
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm p-6">
                <div class="text-center mb-6">
                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-24 h-24 rounded-full mx-auto mb-4">
                    <h2 class="text-xl font-semibold text-gray-900">{{ $user->name }}</h2>
                    <p class="text-gray-500">{{ $user->email }}</p>
                    @php
                        $roleColors = [
                            'owner' => 'bg-purple-100 text-purple-700',
                            'admin' => 'bg-blue-100 text-blue-700',
                            'member' => 'bg-gray-100 text-gray-700',
                        ];
                    @endphp
                    <span class="mt-2 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $roleColors[$user->role] ?? 'bg-gray-100 text-gray-700' }}">
                        {{ ucfirst($user->role) }}
                    </span>
                </div>

                <div class="border-t border-gray-100 pt-4 space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Phone</span>
                        <span class="text-gray-900">{{ $user->phone ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Status</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $user->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $user->is_active ? 'Active' : 'Inactive' }}
                        </span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Joined</span>
                        <span class="text-gray-900">{{ $user->created_at->format('M d, Y') }}</span>
                    </div>
                </div>

                <div class="mt-6 flex gap-2">
                    <a href="{{ route('superadmin.users.edit', $user) }}" class="flex-1 px-4 py-2 bg-sky-500 text-white text-sm font-medium rounded-lg text-center hover:bg-sky-600 transition">
                        <i class="fas fa-edit mr-1"></i> Edit
                    </a>
                </div>
            </div>
        </div>

        {{-- Company & Subscription Info --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Company Details --}}
            <div class="bg-white rounded-xl shadow-sm">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">
                        <i class="fas fa-building text-gray-400 mr-2"></i>Company Information
                    </h3>
                </div>
                <div class="p-6">
                    @if($user->company)
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Company Name</label>
                                <p class="text-gray-900 font-medium">{{ $user->company->name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Email</label>
                                <p class="text-gray-900">{{ $user->company->email }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Phone</label>
                                <p class="text-gray-900">{{ $user->company->phone ?? '—' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Website</label>
                                <p class="text-gray-900">{{ $user->company->website ?? '—' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Country</label>
                                <p class="text-gray-900">{{ $user->company->country ?? '—' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Currency</label>
                                <p class="text-gray-900">{{ $user->company->currency ?? 'USD' }}</p>
                            </div>
                        </div>
                    @else
                        <p class="text-gray-500 text-center py-4">No company associated</p>
                    @endif
                </div>
            </div>

            {{-- Subscription Details --}}
            <div class="bg-white rounded-xl shadow-sm">
                <div class="p-6 border-b border-gray-100">
                    <h3 class="font-semibold text-gray-900">
                        <i class="fas fa-tags text-gray-400 mr-2"></i>Subscription Details
                    </h3>
                </div>
                <div class="p-6">
                    @if($user->company && $user->company->subscriptionPlan)
                        @php
                            $plan = $user->company->subscriptionPlan;
                            $company = $user->company;
                            $statusColors = [
                                'active' => 'bg-green-100 text-green-700',
                                'trial' => 'bg-yellow-100 text-yellow-700',
                                'expired' => 'bg-red-100 text-red-700',
                                'cancelled' => 'bg-gray-100 text-gray-700',
                            ];
                        @endphp
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Plan Name</label>
                                <p class="text-gray-900 font-semibold text-lg">{{ $plan->name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Price</label>
                                <p class="text-gray-900 font-semibold">{{ $plan->formatted_price }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Status</label>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $statusColors[$company->subscription_status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ ucfirst($company->subscription_status ?? 'Unknown') }}
                                </span>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Billing Cycle</label>
                                <p class="text-gray-900">{{ ucfirst($plan->billing_cycle) }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Started At</label>
                                <p class="text-gray-900">{{ $company->subscription_started_at ? $company->subscription_started_at->format('M d, Y') : '—' }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-500 uppercase mb-1">Ends At</label>
                                <p class="text-gray-900">{{ $company->subscription_ends_at ? $company->subscription_ends_at->format('M d, Y') : 'Never' }}</p>
                            </div>
                        </div>

                        {{-- Plan Limits --}}
                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-3">Plan Limits</label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="bg-gray-50 rounded-lg p-3 text-center">
                                    <p class="text-2xl font-bold text-gray-900">{{ $plan->max_invoices ?? '∞' }}</p>
                                    <p class="text-xs text-gray-500">Invoices</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3 text-center">
                                    <p class="text-2xl font-bold text-gray-900">{{ $plan->max_clients ?? '∞' }}</p>
                                    <p class="text-xs text-gray-500">Clients</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3 text-center">
                                    <p class="text-2xl font-bold text-gray-900">{{ $plan->max_users ?? '∞' }}</p>
                                    <p class="text-xs text-gray-500">Users</p>
                                </div>
                                <div class="bg-gray-50 rounded-lg p-3 text-center">
                                    <p class="text-2xl font-bold text-gray-900">{{ $plan->max_products ?? '∞' }}</p>
                                    <p class="text-xs text-gray-500">Products</p>
                                </div>
                            </div>
                        </div>

                        {{-- Features --}}
                        <div class="mt-6 pt-6 border-t border-gray-100">
                            <label class="block text-xs font-medium text-gray-500 uppercase mb-3">Features</label>
                            <div class="flex flex-wrap gap-2">
                                @if($plan->stripe_enabled)
                                    <span class="px-3 py-1 bg-purple-100 text-purple-700 text-sm rounded-full">
                                        <i class="fab fa-stripe mr-1"></i> Stripe Payments
                                    </span>
                                @endif
                                @if($plan->custom_branding)
                                    <span class="px-3 py-1 bg-blue-100 text-blue-700 text-sm rounded-full">
                                        <i class="fas fa-palette mr-1"></i> Custom Branding
                                    </span>
                                @endif
                                @if($plan->api_access)
                                    <span class="px-3 py-1 bg-green-100 text-green-700 text-sm rounded-full">
                                        <i class="fas fa-code mr-1"></i> API Access
                                    </span>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-tag text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500">No subscription plan</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
