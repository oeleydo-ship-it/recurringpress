@extends('layouts.guest')

@section('title', 'Register - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))

@section('auth-header')
    <h2 class="h2">
        @if(isset($plan) && $plan->price > 0)
            Start your {{ $plan->name }} trial
        @else
            Get started for free
        @endif
    </h2>
    @if(isset($plan) && $plan->price > 0)
        <p class="body-sm mt-2">Start with our free plan, no credit card required</p>
    @endif
@endsection

@section('content')
    <form action="{{ route('register') }}" method="POST" class="space-y-5">
        @csrf
        <x-honeypot />
        <input type="hidden" name="plan" value="{{ request('plan', 'free') }}">

        <div>
            <label for="name" class="label">Your name</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
                class="field">
        </div>

        <div>
            <label for="email" class="label">Email address</label>
            <input type="email" name="email" id="email" value="{{ old('email') }}" required
                class="field">
        </div>

        <div>
            <label for="company_name" class="label">Company name</label>
            <input type="text" name="company_name" id="company_name" value="{{ old('company_name') }}" required
                class="field">
        </div>

        <div>
            <label for="password" class="label">Password</label>
            <input type="password" name="password" id="password" required minlength="8" maxlength="128"
                autocomplete="new-password" class="field">
            <p class="body-sm mt-1">At least 8 characters, with upper &amp; lowercase letters and a number.</p>
        </div>

        <div>
            <label for="password_confirmation" class="label">Confirm password</label>
            <input type="password" name="password_confirmation" id="password_confirmation" required
                autocomplete="new-password"
                class="field">
        </div>

        <button type="submit"
            class="btn btn-primary btn-lg w-full">
            Create account
        </button>
    </form>

    <p class="mt-6 text-center body-sm">
        Already have an account?
        <a href="{{ route('login') }}" class="font-semibold" style="color:var(--brand-600)">Log in here</a>.
    </p>
@endsection