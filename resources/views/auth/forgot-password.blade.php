@extends('layouts.guest')

@section('title', 'Forgot Password - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))

@section('auth-header')
    <h2 class="h2">Forgot your password?</h2>
    <p class="body-sm mt-2">Enter your email and we'll send you a reset link</p>
@endsection

@section('content')
    @if(session('status'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg text-sm">
            {{ session('status') }}
        </div>
    @endif

    <form action="{{ route('password.email') }}" method="POST" class="space-y-5">
        @csrf
        <x-honeypot />

        <div>
            <label for="email" class="label">Email address</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                class="field">
        </div>

        <button type="submit"
            class="btn btn-primary btn-lg w-full">
            <i class="fas fa-paper-plane"></i>
            Send Reset Link
        </button>
    </form>

    <p class="mt-6 text-center body-sm">
        Remember your password?
        <a href="{{ route('login') }}" class="font-semibold" style="color:var(--brand-600)">Back to login</a>
    </p>
@endsection