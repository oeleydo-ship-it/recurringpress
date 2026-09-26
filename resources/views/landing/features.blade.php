@extends('layouts.landing')

@php
    $siteName = \App\Models\SystemSetting::get('site_name', 'RecurringPress');
    $pageTitle = \App\Models\SystemSetting::get('features_page_title', 'Powerful Features for Your Business');
    $pageSubtitle = \App\Models\SystemSetting::get('features_page_subtitle', 'Everything you need to create professional invoices, manage clients, and get paid faster.');
    $ctaTitle = \App\Models\SystemSetting::get('features_page_cta_title', 'Ready to get started?');
    $ctaSubtitle = str_replace('{site_name}', $siteName, \App\Models\SystemSetting::get('features_page_cta_subtitle', 'Try {site_name} with our free plan. No credit card required.'));

    $defaultFeatures = [
        ['icon' => 'fas fa-file-invoice', 'color' => 'sky', 'title' => 'Professional Invoices', 'description' => 'Create stunning invoices in seconds with customizable templates that match your brand.', 'bullets' => ['Multiple templates', 'Custom branding', 'PDF downloads']],
        ['icon' => 'fas fa-file-alt', 'color' => 'green', 'title' => 'Quotations & Estimates', 'description' => 'Send professional quotes and convert them to invoices with a single click.', 'bullets' => ['One-click conversion', 'Validity tracking', 'Client approval']],
        ['icon' => 'fab fa-stripe', 'color' => 'purple', 'title' => 'Online Payments', 'description' => 'Accept payments via Stripe. Let clients pay directly from the invoice.', 'bullets' => ['Credit cards', 'Automatic receipts', 'Payment tracking']],
        ['icon' => 'fas fa-users', 'color' => 'blue', 'title' => 'Client Management', 'description' => 'Keep all your client information organized and easily accessible.', 'bullets' => ['Contact details', 'Invoice history', 'Notes & tags']],
        ['icon' => 'fas fa-chart-line', 'color' => 'orange', 'title' => 'Reports & Analytics', 'description' => 'Track your revenue, outstanding payments, and business growth.', 'bullets' => ['Revenue reports', 'Payment tracking', 'Dashboard insights']],
        ['icon' => 'fas fa-users-cog', 'color' => 'pink', 'title' => 'Team Collaboration', 'description' => 'Invite team members and work together on invoices and clients.', 'bullets' => ['Multiple users', 'Role permissions', 'Activity logs']],
    ];

    $featuresJson = \App\Models\SystemSetting::get('features_page_json');
    $features = $featuresJson ? json_decode($featuresJson, true) : $defaultFeatures;

    $accents = [
        'sky' => ['tint' => 'var(--brand-50)', 'fg' => 'var(--brand-600)', 'bar' => 'var(--brand-500)'],
        'green' => ['tint' => '#ECFDF5', 'fg' => '#059669', 'bar' => '#10B981'],
        'emerald' => ['tint' => '#ECFDF5', 'fg' => '#059669', 'bar' => '#10B981'],
        'purple' => ['tint' => '#F5F3FF', 'fg' => '#7C3AED', 'bar' => '#8B5CF6'],
        'blue' => ['tint' => '#EFF6FF', 'fg' => '#2563EB', 'bar' => '#3B82F6'],
        'orange' => ['tint' => '#FFF7ED', 'fg' => '#EA580C', 'bar' => '#F97316'],
        'pink' => ['tint' => '#FDF2F8', 'fg' => '#DB2777', 'bar' => '#EC4899'],
        'red' => ['tint' => '#FEF2F2', 'fg' => '#DC2626', 'bar' => '#EF4444'],
    ];
@endphp

@section('title', 'Features - ' . $siteName)
@section('meta_description', $pageSubtitle)

@section('content')
    <section class="canvas section">
        <div class="wrap-sm text-center">
            <span class="eyebrow mb-5"><i class="fas fa-bolt"></i> Features</span>
            <h1 class="display">{{ $pageTitle }}</h1>
            <p class="lead mt-6">{{ $pageSubtitle }}</p>
            <div class="mt-9 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('register') }}" class="btn btn-primary btn-lg">
                    Get Started Free <i class="fas fa-arrow-right"></i>
                </a>
                <a href="{{ route('landing.pricing') }}" class="btn btn-ghost btn-lg">View pricing</a>
            </div>
        </div>
    </section>

    <section class="band section">
        <div class="wrap">
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
                @forelse($features as $feature)
                    @php $a = $accents[$feature['color'] ?? 'sky'] ?? $accents['sky']; @endphp
                    <article class="card card-hover flex flex-col">
                        <span class="accent-bar" style="background:{{ $a['bar'] }}"></span>
                        <div class="p-7 flex flex-col flex-1">
                            <span class="icon-tile mb-5" style="background:{{ $a['tint'] }};color:{{ $a['fg'] }}">
                                <i class="{{ $feature['icon'] ?? 'fas fa-star' }} text-lg"></i>
                            </span>
                            <h2 class="h3 mb-2">{{ $feature['title'] ?? 'Feature' }}</h2>
                            <p class="body-sm">{{ $feature['description'] ?? '' }}</p>
                            @if(!empty($feature['bullets']))
                                <ul class="mt-5 pt-5 space-y-2.5 text-[0.9375rem]"
                                    style="border-top:1px solid var(--line);color:var(--ink-700)">
                                    @foreach($feature['bullets'] as $bullet)
                                        @if(!empty($bullet))
                                            <li class="flex items-start gap-2.5">
                                                <i class="fas fa-check text-[0.6875rem] mt-1.5" style="color:{{ $a['fg'] }}"></i>
                                                <span>{{ $bullet }}</span>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </article>
                @empty
                    <p class="md:col-span-2 lg:col-span-3 text-center py-8" style="color:var(--ink-500)">
                        No features configured yet.
                    </p>
                @endforelse
            </div>
        </div>
    </section>

    <section class="section">
        <div class="wrap-md">
            <div class="cta-panel">
                <h2 class="h1 text-white">{{ $ctaTitle }}</h2>
                <p class="lead mt-4 mx-auto max-w-xl" style="color:rgba(255,255,255,.9)">{{ $ctaSubtitle }}</p>
                <a href="{{ route('register') }}" class="btn btn-white btn-lg mt-8">
                    Get Started Free <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>
@endsection
