@extends('layouts.app')

@section('title', 'Edit Team Member - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('page-title', 'Edit Team Member')

@section('content')
<div class="mb-6">
    <a href="{{ route('users.index') }}" class="text-gray-500 hover:text-gray-700">
        <i class="fas fa-arrow-left mr-2"></i> Back to Team
    </a>
</div>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 max-w-2xl">
    <form action="{{ route('users.update', $user) }}" method="POST" class="p-6 space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" required
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
            </div>

            <div>
                <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                <select name="role" id="role" required
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-primary">
                    @foreach($roles as $value => $label)
                    <option value="{{ $value }}" {{ old('role', $user->role) === $value ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                           class="w-4 h-4 text-primary border-gray-300 rounded focus:ring-primary">
                    <span class="ml-2 text-sm text-gray-700">Active Account</span>
                </label>
                <p class="mt-1 text-xs text-gray-500">Inactive users cannot log in</p>
            </div>
        </div>

        <div class="flex items-center justify-end space-x-4 pt-4 border-t">
            <a href="{{ route('users.index') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">Cancel</a>
            <button type="submit" class="px-6 py-2 bg-primary text-white font-medium rounded-lg bg-primary-hover transition">
                Update Member
            </button>
        </div>
    </form>
</div>
@endsection
