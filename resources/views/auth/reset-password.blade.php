@extends('layouts.guest')

@section('title', 'Reset Password - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))

@section('auth-header')
    <h2 class="h2">Reset your password</h2>
    <p class="body-sm mt-2">Enter your new password below</p>
@endsection

@section('content')
    <form action="{{ route('password.update') }}" method="POST" class="space-y-5">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">
        <input type="hidden" name="email" value="{{ $email }}">

        <div>
            <label for="password" class="label">New Password</label>
            <input type="password" name="password" id="password" required autofocus
                class="field">
        </div>

        <div>
            <label for="password_confirmation" class="label">Confirm Password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required
                class="field">
        </div>

        <button type="submit"
            class="btn btn-primary btn-lg w-full">
            <i class="fas fa-lock"></i>
            Reset Password
        </button>
    </form>

    <p class="mt-6 text-center body-sm">
        Remember your password?
        <a href="{{ route('login') }}" class="font-semibold" style="color:var(--brand-600)">Back to login</a>
    </p>
@endsection