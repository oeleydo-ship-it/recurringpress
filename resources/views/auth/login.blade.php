@extends('layouts.guest')

@section('title', 'Login - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))

@section('auth-header')
    <h2 class="h2">Sign in to your account</h2>
    <p class="body-sm mt-2">or <a href="{{ route('register') }}"
            class="font-semibold" style="color:var(--brand-600)">create an account for free</a></p>
@endsection

@section('content')
    <form action="{{ route('login') }}" method="POST" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="label">Email address</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required autofocus
                class="field">
        </div>

        <div>
            <label for="password" class="label">Password</label>
            <input type="password" name="password" id="password" required
                class="field">
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center">
                <input type="checkbox" name="remember"
                    class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                <span class="ml-2 text-sm" style="color:var(--ink-600)">Remember me</span>
            </label>
            <a href="{{ route('password.request') }}"
                class="text-sm font-semibold" style="color:var(--brand-600)">Forgot your password?</a>
        </div>

        <button type="submit"
            class="btn btn-primary btn-lg w-full">
            Sign in
        </button>
    </form>
@endsection