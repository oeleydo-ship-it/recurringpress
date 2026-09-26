@extends('layouts.guest')

@section('title', 'Verify Email - ' . config('app.name'))

@section('auth-header')
    <h2 class="h2">{{ __('Verify your email') }}</h2>
    <p class="body-sm mt-2">
        {{ __('Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn\'t receive the email, we will gladly send you another.') }}
    </p>
@endsection

@section('content')
    @if (session('status') == 'verification-link-sent')
        <div class="flex items-start gap-3 p-4 mb-5 rounded-[var(--r)] text-sm"
            style="background:var(--ok-50);border:1px solid #A7F3D0;color:var(--ok-600)">
            <i class="fas fa-check-circle mt-0.5"></i>
            <span>{{ __('A new verification link has been sent to the email address you provided during registration.') }}</span>
        </div>
    @endif

    <span class="icon-tile icon-tile-lg mx-auto mb-6 flex"
        style="background:var(--brand-50);color:var(--brand-600)">
        <i class="fas fa-envelope-open-text text-xl"></i>
    </span>

    <form method="POST" action="{{ route('verification.send') }}">
        @csrf
        <button type="submit" class="btn btn-primary btn-lg w-full">
            <i class="fas fa-paper-plane"></i> {{ __('Resend Verification Email') }}
        </button>
    </form>

    <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm font-semibold" style="color:var(--ink-500)">
            {{ __('Log Out') }}
        </button>
    </form>
@endsection
