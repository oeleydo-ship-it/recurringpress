@extends('layouts.guest')

@section('title', 'Create Company - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))

@section('content')
    <h2 class="text-2xl font-bold text-gray-900 text-center mb-6">Create Your Company</h2>
    <p class="text-gray-600 text-center mb-6">Set up your company to start creating invoices</p>

    <form action="{{ route('company.store') }}" method="POST" class="space-y-5">
        @csrf

        <div>
            <label for="name" class="label">Company Name *</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition @error('name') border-red-500 @enderror">
            @error('name')
                <p class="mt-1 text-sm text-red-500">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="label">Company Email</label>
            <input type="email" name="email" id="email" value="{{ old('email', auth()->user()->email) }}"
                class="field">
        </div>

        <div>
            <label for="phone" class="label">Phone</label>
            <input type="text" name="phone" id="phone" value="{{ old('phone') }}"
                class="field">
        </div>

        <button type="submit"
            class="btn btn-primary btn-lg w-full">
            Create Company
        </button>
    </form>
@endsection