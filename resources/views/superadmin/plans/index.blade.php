@extends('superadmin.layout')

@section('title', 'Subscription Plans')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-gray-600">Manage subscription plans for your customers</p>
        </div>
        <a href="{{ route('superadmin.plans.create') }}"
            class="px-4 py-2 bg-sky-500 text-white font-medium rounded-lg hover:bg-sky-600 transition">
            <i class="fas fa-plus mr-2"></i>New Plan
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Limits</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Features</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($plans as $plan)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-900">{{ $plan->name }}</div>
                            @if($plan->is_popular)
                                <span class="text-xs text-sky-500">Popular</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-semibold text-gray-900">{{ $plan->formatted_price }}</div>
                            @if(!$plan->isFree())
                                <div class="text-xs text-gray-500">{{ ucfirst($plan->billing_cycle) }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">
                            <div>Invoices: {{ $plan->max_invoices ?? '∞' }}</div>
                            <div>Clients: {{ $plan->max_clients ?? '∞' }}</div>
                            <div>Users: {{ $plan->max_users ?? '∞' }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-wrap gap-1">
                                @if($plan->stripe_enabled)
                                    <span class="px-2 py-0.5 bg-purple-100 text-purple-700 text-xs rounded">Stripe</span>
                                @endif
                                @if($plan->custom_branding)
                                    <span class="px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded">Branding</span>
                                @endif
                                @if($plan->api_access)
                                    <span class="px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded">API</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $plan->is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-700' }}">
                                {{ $plan->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="{{ route('superadmin.plans.edit', $plan) }}"
                                    class="p-2 text-gray-400 hover:text-sky-500">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form action="{{ route('superadmin.plans.destroy', $plan) }}" method="POST" class="inline"
                                    onsubmit="return confirm('Delete this plan?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-gray-400 hover:text-red-500">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-tags text-4xl text-gray-300 mb-4"></i>
                            <p>No plans yet</p>
                            <a href="{{ route('superadmin.plans.create') }}"
                                class="mt-2 inline-block text-sky-500 hover:text-sky-600">Create your first plan</a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($plans->hasPages())
            <div class="px-6 py-4 border-t border-gray-100">
                {{ $plans->links() }}
            </div>
        @endif
    </div>
@endsection