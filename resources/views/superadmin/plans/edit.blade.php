@extends('superadmin.layout')

@section('title', 'Edit Plan')

@section('content')
<div class="max-w-4xl">
    <div class="mb-6">
        <a href="{{ route('superadmin.plans.index') }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-2"></i>Back to Plans
        </a>
    </div>

    <form action="{{ route('superadmin.plans.update', $plan) }}" method="POST" class="bg-white rounded-xl shadow-sm p-6">
        @csrf
        @method('PUT')

        <div class="space-y-6">
            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Plan Name *</label>
                    <input type="text" name="name" value="{{ old('name', $plan->name) }}" required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Slug</label>
                    <input type="text" name="slug" value="{{ old('slug', $plan->slug) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea name="description" rows="2"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">{{ old('description', $plan->description) }}</textarea>
            </div>

            <div class="grid md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Price *</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2 text-gray-400">$</span>
                        <input type="number" name="price" value="{{ old('price', $plan->price) }}" min="0" step="0.01" required
                               class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Billing Cycle</label>
                    <select name="billing_cycle" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                        <option value="monthly" {{ old('billing_cycle', $plan->billing_cycle) === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="yearly" {{ old('billing_cycle', $plan->billing_cycle) === 'yearly' ? 'selected' : '' }}>Yearly</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Display Order</label>
                    <input type="number" name="order" value="{{ old('order', $plan->order) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                </div>
            </div>

            <div class="border-t pt-6">
                <h3 class="font-medium text-gray-900 mb-4">Usage Limits</h3>
                <p class="text-sm text-gray-500 mb-4">Leave empty for unlimited</p>
                <div class="grid md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Invoices/month</label>
                        <input type="number" name="max_invoices" value="{{ old('max_invoices', $plan->max_invoices) }}" min="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Clients</label>
                        <input type="number" name="max_clients" value="{{ old('max_clients', $plan->max_clients) }}" min="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Users</label>
                        <input type="number" name="max_users" value="{{ old('max_users', $plan->max_users) }}" min="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Max Products</label>
                        <input type="number" name="max_products" value="{{ old('max_products', $plan->max_products) }}" min="1"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-sky-500">
                    </div>
                </div>
            </div>

            <div class="border-t pt-6">
                <h3 class="font-medium text-gray-900 mb-4">Features</h3>
                <div class="grid md:grid-cols-3 gap-4">
                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="stripe_enabled" value="1" {{ old('stripe_enabled', $plan->stripe_enabled) ? 'checked' : '' }}
                               class="w-4 h-4 text-sky-500 border-gray-300 rounded focus:ring-sky-500">
                        <span class="ml-3 text-gray-700">Stripe Payments</span>
                    </label>
                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="custom_branding" value="1" {{ old('custom_branding', $plan->custom_branding) ? 'checked' : '' }}
                               class="w-4 h-4 text-sky-500 border-gray-300 rounded focus:ring-sky-500">
                        <span class="ml-3 text-gray-700">Custom Branding</span>
                    </label>
                    <label class="flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="api_access" value="1" {{ old('api_access', $plan->api_access) ? 'checked' : '' }}
                               class="w-4 h-4 text-sky-500 border-gray-300 rounded focus:ring-sky-500">
                        <span class="ml-3 text-gray-700">API Access</span>
                    </label>
                </div>
            </div>

            <div class="border-t pt-6">
                <div class="flex items-center space-x-6">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $plan->is_active) ? 'checked' : '' }}
                               class="w-4 h-4 text-sky-500 border-gray-300 rounded focus:ring-sky-500">
                        <span class="ml-2 text-gray-700">Active</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_popular" value="1" {{ old('is_popular', $plan->is_popular) ? 'checked' : '' }}
                               class="w-4 h-4 text-sky-500 border-gray-300 rounded focus:ring-sky-500">
                        <span class="ml-2 text-gray-700">Mark as Popular</span>
                    </label>
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-4 mt-8 pt-6 border-t">
            <a href="{{ route('superadmin.plans.index') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-sky-500 text-white font-medium rounded-lg hover:bg-sky-600 transition">
                Update Plan
            </button>
        </div>
    </form>
</div>
@endsection
