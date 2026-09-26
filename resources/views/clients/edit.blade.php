@extends('layouts.app')

@section('title', 'Edit Client - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Edit Client')

@section('content')
    <div class="mb-6">
        <a href="{{ route('clients.index') }}" class="text-gray-500 hover:text-gray-700">
            <i class="fas fa-arrow-left mr-2"></i> Back to Clients
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 max-w-3xl">
        <form action="{{ route('clients.update', $client) }}" method="POST" class="p-6 space-y-6"
              x-data="{ clientType: '{{ old('client_type', $client->client_type ?? 'individual') }}' }">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Client Type *</label>
                    <div class="flex gap-3">
                        <label class="flex-1 flex items-center justify-center gap-2 px-4 py-2 border rounded-lg cursor-pointer transition"
                               :class="clientType === 'individual' ? 'border-primary bg-primary-light text-primary-dark' : 'border-gray-300 text-gray-600 hover:bg-gray-50'">
                            <input type="radio" name="client_type" value="individual" x-model="clientType" class="sr-only">
                            <i class="fas fa-user"></i> Individual
                        </label>
                        <label class="flex-1 flex items-center justify-center gap-2 px-4 py-2 border rounded-lg cursor-pointer transition"
                               :class="clientType === 'company' ? 'border-primary bg-primary-light text-primary-dark' : 'border-gray-300 text-gray-600 hover:bg-gray-50'">
                            <input type="radio" name="client_type" value="company" x-model="clientType" class="sr-only">
                            <i class="fas fa-building"></i> Company
                        </label>
                    </div>
                    @error('client_type')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
                        <span x-show="clientType === 'company'" x-cloak>Contact Person *</span>
                        <span x-show="clientType !== 'company'">Client Name *</span>
                    </label>
                    <input type="text" name="name" id="name" value="{{ old('name', $client->name) }}" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('name') border-red-500 @enderror">
                    @error('name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2" x-show="clientType === 'company'" x-cloak>
                    <label for="company_name" class="block text-sm font-medium text-gray-700 mb-1">Company Name *</label>
                    <input type="text" name="company_name" id="company_name" value="{{ old('company_name', $client->company_name) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary @error('company_name') border-red-500 @enderror">
                    @error('company_name')
                        <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" id="email" value="{{ old('email', $client->email) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                    <input type="text" name="phone" id="phone" value="{{ old('phone', $client->phone) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="tax_number" class="block text-sm font-medium text-gray-700 mb-1">Tax Number / VAT ID</label>
                    <input type="text" name="tax_number" id="tax_number"
                        value="{{ old('tax_number', $client->tax_number) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $client->is_active) ? 'checked' : '' }}
                            class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                        <span class="ml-2 text-sm text-gray-700">Active Client</span>
                    </label>
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea name="address" id="address" rows="2"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('address', $client->address) }}</textarea>
                </div>

                <div>
                    <label for="city" class="block text-sm font-medium text-gray-700 mb-1">City</label>
                    <input type="text" name="city" id="city" value="{{ old('city', $client->city) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="state" class="block text-sm font-medium text-gray-700 mb-1">State / Province</label>
                    <input type="text" name="state" id="state" value="{{ old('state', $client->state) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="postal_code" class="block text-sm font-medium text-gray-700 mb-1">Postal Code</label>
                    <input type="text" name="postal_code" id="postal_code"
                        value="{{ old('postal_code', $client->postal_code) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div>
                    <label for="country" class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                    <input type="text" name="country" id="country" value="{{ old('country', $client->country) }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                </div>

                <div class="md:col-span-2">
                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea name="notes" id="notes" rows="3"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">{{ old('notes', $client->notes) }}</textarea>
                </div>
            </div>

            <div class="flex items-center justify-end space-x-4 pt-4 border-t">
                <a href="{{ route('clients.index') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">Cancel</a>
                <button type="submit"
                    class="px-6 py-2 bg-primary text-white font-medium rounded-lg bg-primary-hover transition">
                    Update Client
                </button>
            </div>
        </form>
    </div>
@endsection