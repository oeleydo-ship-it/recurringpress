@extends('layouts.landing')

@php
    $siteName = \App\Models\SystemSetting::get('site_name', 'RecurringPress');
    $plans = $plans ?? \App\Models\SubscriptionPlan::active()->ordered()->get();

    // Shared accent palette. Keys match what the admin stores against each feature.
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

@section('title', $siteName . ' - Professional Invoicing for Freelancers & Businesses')

@section('content')
    {{-- ============================== Hero ============================== --}}
    <section class="canvas section">
        <div class="wrap">
            <div class="grid lg:grid-cols-[1.05fr_1fr] gap-14 lg:gap-16 items-center">
                <div>
                    <span class="eyebrow mb-6">
                        <i class="fas fa-bolt"></i> New · Recurring billing &amp; expense tracking
                    </span>
                    <h1 class="display">
                        {{ \App\Models\SystemSetting::get('landing_hero_title', 'Professional Invoicing Made Simple') }}
                    </h1>
                    <p class="lead mt-6 max-w-xl">
                        {{ \App\Models\SystemSetting::get('landing_hero_subtitle', 'Create beautiful invoices, manage clients, track payments, and grow your business. Built for freelancers and small businesses who want to get paid faster.') }}
                    </p>
                    <div class="mt-9 flex flex-col sm:flex-row gap-3">
                        <a href="{{ \App\Models\SystemSetting::get('landing_cta_url', route('register')) }}"
                            class="btn btn-primary btn-lg">
                            {{ \App\Models\SystemSetting::get('landing_cta_text', 'Get Started Free') }}
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="{{ route('landing.features') }}" class="btn btn-ghost btn-lg">
                            <i class="fas fa-play-circle" style="color:var(--brand-500)"></i> See how it works
                        </a>
                    </div>

                    <dl class="mt-12 grid grid-cols-3 gap-8 max-w-md">
                        @foreach([['12+', 'Templates'], ['30s', 'Setup'], ['0%', 'Hidden fees']] as [$num, $label])
                            <div>
                                <dt class="sr-only">{{ $label }}</dt>
                                <dd>
                                    <span class="block text-2xl font-extrabold tracking-tight"
                                        style="color:var(--ink-900);letter-spacing:-0.03em">{{ $num }}</span>
                                    <span class="block mt-1 text-[0.6875rem] font-semibold uppercase"
                                        style="color:var(--ink-500);letter-spacing:0.1em">{{ $label }}</span>
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>

                {{-- Product mockup --}}
                <div class="relative lg:pl-4">
                    <div class="card" style="box-shadow:var(--sh-lift)">
                        <div class="flex items-center gap-2 px-4 py-3"
                            style="background:var(--surface-2);border-bottom:1px solid var(--line)">
                            <span class="w-2.5 h-2.5 rounded-full" style="background:#FCA5A5"></span>
                            <span class="w-2.5 h-2.5 rounded-full" style="background:#FCD34D"></span>
                            <span class="w-2.5 h-2.5 rounded-full" style="background:#6EE7B7"></span>
                            <span class="ml-3 text-xs font-medium truncate" style="color:var(--ink-400)">
                                app.recurringpress.com / invoices / INV-00042
                            </span>
                        </div>
                        <div class="p-6 sm:p-7">
                            <div class="flex items-start justify-between gap-4 mb-7">
                                <div>
                                    <p class="text-[0.6875rem] font-semibold uppercase"
                                        style="color:var(--ink-500);letter-spacing:0.1em">Invoice #INV-00042</p>
                                    <p class="text-2xl font-extrabold mt-1.5"
                                        style="color:var(--ink-900);letter-spacing:-0.03em">$4,250.00</p>
                                </div>
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold shrink-0"
                                    style="background:var(--ok-50);color:var(--ok-600);border:1px solid #A7F3D0">
                                    <i class="fas fa-check-circle"></i> Paid
                                </span>
                            </div>
                            <div>
                                <div class="flex justify-between gap-4 py-3.5" style="border-bottom:1px solid var(--line)">
                                    <div>
                                        <div class="font-semibold text-[0.9375rem]" style="color:var(--ink-800)">Web Development</div>
                                        <div class="text-xs mt-0.5" style="color:var(--ink-500)">40 hrs · $87.50/hr</div>
                                    </div>
                                    <span class="font-semibold" style="color:var(--ink-900)">$3,500.00</span>
                                </div>
                                <div class="flex justify-between gap-4 py-3.5" style="border-bottom:1px solid var(--line)">
                                    <div>
                                        <div class="font-semibold text-[0.9375rem]" style="color:var(--ink-800)">UI/UX Design</div>
                                        <div class="text-xs mt-0.5" style="color:var(--ink-500)">Deliverables included</div>
                                    </div>
                                    <span class="font-semibold" style="color:var(--ink-900)">$750.00</span>
                                </div>
                                <div class="flex justify-between items-baseline pt-4">
                                    <span class="text-sm" style="color:var(--ink-500)">Total</span>
                                    <span class="text-base font-extrabold" style="color:var(--ink-900);letter-spacing:-0.02em">$4,250.00</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Floating badges: hidden on small screens where they would overlap. --}}
                    <div class="hidden sm:flex absolute -bottom-6 -left-5 card items-center gap-3 px-4 py-3"
                        style="box-shadow:var(--sh-lift)">
                        <span class="icon-tile" style="width:2.25rem;height:2.25rem;background:var(--brand-50);color:var(--brand-600)">
                            <i class="fas fa-bell text-sm"></i>
                        </span>
                        <div>
                            <div class="text-sm font-semibold" style="color:var(--ink-900)">Payment received</div>
                            <div class="text-xs" style="color:var(--ink-500)">Just now · Stripe</div>
                        </div>
                    </div>
                    <div class="hidden sm:flex absolute -top-6 -right-2 lg:-right-5 card items-center gap-3 px-4 py-3"
                        style="box-shadow:var(--sh-lift)">
                        <span class="icon-tile" style="width:2.25rem;height:2.25rem;background:var(--ok-50);color:var(--ok-600)">
                            <i class="fas fa-chart-line text-sm"></i>
                        </span>
                        <div>
                            <div class="text-sm font-semibold" style="color:var(--ink-900)">+18% this month</div>
                            <div class="text-xs" style="color:var(--ink-500)">vs. last month</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================== Trust ============================== --}}
    <section class="band section-sm">
        <div class="wrap">
            <p class="text-center text-xs font-semibold uppercase mb-8"
                style="color:var(--ink-500);letter-spacing:0.14em">
                {{ \App\Models\SystemSetting::get('landing_trust_text', 'Trusted by thousands of freelancers and businesses') }}
            </p>
            @php
                $trustBadgesJson = \App\Models\SystemSetting::get('landing_trust_badges_json');
                $trustBadges = $trustBadgesJson ? json_decode($trustBadgesJson, true) : [];
                $trustBadges = $trustBadges ?: array_map(fn($n) => ['name' => $n], ['TechCorp', 'StartupX', 'DesignCo', 'DevStudio', 'CreativeHub']);
            @endphp
            <ul class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
                @foreach($trustBadges as $badge)
                    <li class="card-flat flex items-center justify-center py-4 px-5 text-center font-semibold text-[0.9375rem]"
                        style="color:var(--ink-500)">
                        {{ $badge['name'] ?? '' }}
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ============================== Features ============================== --}}
    <section id="features" class="section">
        <div class="wrap">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <span class="eyebrow mb-4">Features</span>
                <h2 class="h1">
                    {{ \App\Models\SystemSetting::get('landing_features_title', 'Everything you need to get paid') }}
                </h2>
                <p class="lead mt-5">
                    {{ \App\Models\SystemSetting::get('landing_features_subtitle', 'Powerful features to streamline your invoicing workflow') }}
                </p>
            </div>

            @php
                $defaultFeatures = [
                    ['icon' => 'fas fa-file-invoice', 'color' => 'sky', 'title' => 'Professional Invoices', 'description' => 'Create stunning invoices in seconds with customizable templates that match your brand.', 'bullets' => ['Multiple templates', 'Custom branding', 'PDF downloads']],
                    ['icon' => 'fas fa-file-alt', 'color' => 'green', 'title' => 'Quotations & Estimates', 'description' => 'Send professional quotes and convert them to invoices with a single click.', 'bullets' => ['One-click conversion', 'Validity tracking', 'Client approval']],
                    ['icon' => 'fab fa-stripe', 'color' => 'purple', 'title' => 'Online Payments', 'description' => 'Accept payments via Stripe. Let clients pay directly from the invoice.', 'bullets' => ['Credit cards', 'Automatic receipts', 'Payment tracking']],
                    ['icon' => 'fas fa-users', 'color' => 'blue', 'title' => 'Client Management', 'description' => 'Keep all your client information organized and easily accessible.', 'bullets' => ['Contact details', 'Invoice history', 'Notes & tags']],
                    ['icon' => 'fas fa-chart-line', 'color' => 'orange', 'title' => 'Reports & Analytics', 'description' => 'Track your revenue, outstanding payments, and business growth.', 'bullets' => ['Revenue reports', 'Payment tracking', 'Dashboard insights']],
                    ['icon' => 'fas fa-users-cog', 'color' => 'pink', 'title' => 'Team Collaboration', 'description' => 'Invite team members and work together on invoices and clients.', 'bullets' => ['Multiple users', 'Role permissions', 'Activity logs']],
                ];

                $featuresJson = \App\Models\SystemSetting::get('landing_features_json');
                $features = $featuresJson ? json_decode($featuresJson, true) : $defaultFeatures;
            @endphp

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
                @forelse($features as $feature)
                    @php $a = $accents[$feature['color'] ?? 'sky'] ?? $accents['sky']; @endphp
                    <article class="card card-hover flex flex-col">
                        <span class="accent-bar" style="background:{{ $a['bar'] }}"></span>
                        <div class="p-7 flex flex-col flex-1">
                            <span class="icon-tile mb-5" style="background:{{ $a['tint'] }};color:{{ $a['fg'] }}">
                                <i class="{{ $feature['icon'] ?? 'fas fa-star' }} text-lg"></i>
                            </span>
                            <h3 class="h3 mb-2">{{ $feature['title'] ?? 'Feature' }}</h3>
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

    {{-- ============================== Quotes → invoices ============================== --}}
    <section class="band section">
        <div class="wrap">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <div>
                    <span class="kicker">Quotations</span>
                    <h2 class="h1 mt-2">Convert quotes to invoices instantly</h2>
                    <p class="lead mt-5">
                        Win more projects with professional quotations. When your client accepts,
                        convert to an invoice with one click. No retyping, no errors.
                    </p>
                    <ul class="mt-8 space-y-3.5">
                        @foreach([
                            'Create detailed quotes with line items and terms',
                            'Share via unique link - no login required for clients',
                            'Track quote status: sent, accepted, rejected',
                            'One-click conversion to invoice',
                        ] as $point)
                            <li class="flex items-start gap-3">
                                <i class="fas fa-check-circle mt-1" style="color:var(--ok-500)"></i>
                                <span style="color:var(--ink-700)">{{ $point }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="card p-6 sm:p-8" style="box-shadow:var(--sh-card)">
                    <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold"
                            style="background:var(--brand-50);color:var(--brand-700);border:1px solid var(--brand-200)">
                            <i class="fas fa-check"></i> Quote Accepted
                        </span>
                        <span class="btn btn-primary" style="font-size:0.875rem;padding:0.5rem 0.875rem">
                            Convert to Invoice <i class="fas fa-arrow-right"></i>
                        </span>
                    </div>
                    <p class="text-sm" style="color:var(--ink-500)">Quote #QT-00015 • Valid until Jan 30, 2024</p>
                    <p class="text-3xl font-extrabold mt-2" style="color:var(--ink-900);letter-spacing:-0.032em">$12,500.00</p>
                    <div class="mt-6 pt-5 grid grid-cols-3 gap-4" style="border-top:1px solid var(--line)">
                        @foreach([['Sent', 'fa-paper-plane'], ['Viewed', 'fa-eye'], ['Accepted', 'fa-thumbs-up']] as $i => [$step, $ico])
                            <div class="text-center">
                                <span class="icon-tile mx-auto mb-2"
                                    style="width:2.25rem;height:2.25rem;background:{{ $i === 2 ? 'var(--ok-50)' : 'var(--surface-2)' }};color:{{ $i === 2 ? 'var(--ok-600)' : 'var(--ink-400)' }}">
                                    <i class="fas {{ $ico }} text-xs"></i>
                                </span>
                                <span class="block text-xs font-semibold" style="color:var(--ink-600)">{{ $step }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ============================== Pricing ============================== --}}
    <section id="pricing" class="section">
        <div class="wrap">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <span class="eyebrow mb-4"><i class="fas fa-tag"></i> Pricing</span>
                <h2 class="h1">
                    {{ \App\Models\SystemSetting::get('landing_pricing_title', 'Simple, transparent pricing') }}
                </h2>
                <p class="lead mt-5">
                    {{ \App\Models\SystemSetting::get('landing_pricing_subtitle', 'Start free, upgrade when you\'re ready') }}
                </p>
            </div>

            @php
                // Explicit map: an interpolated `grid-cols-{{ $n }}` class cannot be
                // statically extracted by a compiled Tailwind build.
                $cols = [1 => 'md:grid-cols-1', 2 => 'md:grid-cols-2'][$plans->count()] ?? 'md:grid-cols-3';
            @endphp
            <div class="grid {{ $cols }} gap-6 max-w-5xl mx-auto items-stretch">
                @forelse($plans as $plan)
                    @php $popular = $plan->is_popular; @endphp
                    <div class="relative flex flex-col rounded-[var(--r-xl)] overflow-hidden bg-white"
                        style="border:1px solid {{ $popular ? 'var(--brand-500)' : 'var(--line)' }};
                               box-shadow:{{ $popular ? '0 0 0 4px var(--brand-100), var(--sh-lift)' : 'var(--sh-card)' }}">
                        @if($popular)
                            <div class="text-center py-2 text-[0.6875rem] font-bold uppercase text-white"
                                style="background:var(--brand-500);letter-spacing:0.12em">
                                <i class="fas fa-star mr-1"></i> Most Popular
                            </div>
                        @endif
                        <div class="p-8">
                            <h3 class="text-lg font-bold" style="color:var(--ink-900);letter-spacing:-0.02em">{{ $plan->name }}</h3>
                            <p class="text-sm mt-1.5" style="color:var(--ink-500)">
                                {{ $plan->description ?? ($popular ? 'For growing businesses' : 'Perfect for getting started') }}
                            </p>
                            <p class="mt-6 flex items-baseline gap-1.5">
                                <span class="text-4xl font-extrabold" style="color:var(--ink-900);letter-spacing:-0.035em">
                                    ${{ $plan->isFree() ? '0' : number_format($plan->price, 0) }}
                                </span>
                                <span class="text-sm" style="color:var(--ink-500)">
                                    /{{ $plan->billing_cycle === 'yearly' ? 'year' : 'month' }}
                                </span>
                            </p>
                            <a href="{{ route('register') }}"
                                class="btn {{ $popular ? 'btn-primary' : 'btn-ghost' }} w-full mt-7">Get Started</a>
                        </div>
                        <div class="px-8 pb-8 pt-6 mt-auto" style="border-top:1px solid var(--line)">
                            <p class="text-[0.6875rem] font-bold uppercase mb-4"
                                style="color:var(--ink-500);letter-spacing:0.12em">What's included</p>
                            <ul class="space-y-3 text-[0.9375rem]" style="color:var(--ink-700)">
                                @php
                                    $lines = [
                                        (!$plan->max_invoices || $plan->max_invoices == -1) ? 'Unlimited invoices' : $plan->max_invoices . ' invoices/month',
                                        (!$plan->max_clients || $plan->max_clients == -1) ? 'Unlimited clients' : $plan->max_clients . ' clients',
                                        (!$plan->max_users || $plan->max_users == -1) ? 'Unlimited team members' : $plan->max_users . ' ' . ($plan->max_users == 1 ? 'user' : 'team members'),
                                        (!$plan->max_products || $plan->max_products == -1) ? 'Unlimited products' : $plan->max_products . ' products',
                                    ];
                                    if ($plan->stripe_enabled) $lines[] = 'Stripe payments';
                                    if ($plan->custom_branding) $lines[] = 'Custom branding';
                                    if ($plan->api_access) $lines[] = 'API access';
                                @endphp
                                @foreach($lines as $line)
                                    <li class="flex items-start gap-2.5">
                                        <i class="fas fa-check-circle mt-0.5" style="color:var(--ok-500)"></i>
                                        <span>{{ $line }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @empty
                    <p class="md:col-span-3 text-center py-8" style="color:var(--ink-500)">No pricing plans available yet.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- ============================== Testimonials ============================== --}}
    @php
        $testimonialsJson = \App\Models\SystemSetting::get('landing_testimonials_json');
        $testimonials = $testimonialsJson ? json_decode($testimonialsJson, true) : [];
    @endphp
    <section id="testimonials" class="band section">
        <div class="wrap">
            <div class="text-center max-w-3xl mx-auto mb-14">
                <span class="eyebrow mb-4"><i class="fas fa-star"></i> Testimonials</span>
                <h2 class="h1">
                    {{ \App\Models\SystemSetting::get('landing_testimonials_title', 'Loved by freelancers worldwide') }}
                </h2>
                <p class="lead mt-5">
                    {{ \App\Models\SystemSetting::get('landing_testimonials_subtitle', 'See what our customers have to say') }}
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-5">
                @forelse($testimonials as $testimonial)
                    @php $a = $accents[$testimonial['color'] ?? 'sky'] ?? $accents['sky']; @endphp
                    <figure class="card p-7 flex flex-col">
                        <div class="flex gap-0.5 mb-4" aria-label="5 out of 5 stars">
                            @for($i = 0; $i < 5; $i++)
                                <i class="fas fa-star text-xs" style="color:#FBBF24"></i>
                            @endfor
                        </div>
                        <blockquote class="flex-1" style="color:var(--ink-700);line-height:1.7">
                            “{{ $testimonial['quote'] ?? '' }}”
                        </blockquote>
                        <figcaption class="flex items-center gap-3.5 mt-6 pt-6" style="border-top:1px solid var(--line)">
                            <span class="icon-tile rounded-full font-bold text-sm"
                                style="border-radius:999px;background:{{ $a['tint'] }};color:{{ $a['fg'] }}">
                                {{ $testimonial['initials'] ?? 'XX' }}
                            </span>
                            <span>
                                <span class="block font-semibold text-[0.9375rem]" style="color:var(--ink-900)">
                                    {{ $testimonial['name'] ?? 'Anonymous' }}
                                </span>
                                <span class="block text-sm" style="color:var(--ink-500)">{{ $testimonial['role'] ?? '' }}</span>
                            </span>
                        </figcaption>
                    </figure>
                @empty
                    <p class="md:col-span-3 text-center py-8" style="color:var(--ink-500)">No testimonials configured yet.</p>
                @endforelse
            </div>
        </div>
    </section>

    {{-- ============================== Closing CTA ============================== --}}
    <section class="section">
        <div class="wrap-md">
            <div class="cta-panel">
                <h2 class="h1 text-white">Ready to get paid faster?</h2>
                <p class="lead mt-4 mx-auto max-w-xl" style="color:rgba(255,255,255,.9)">
                    Join thousands of freelancers and businesses who trust {{ $siteName }}
                </p>
                <a href="{{ route('register') }}" class="btn btn-white btn-lg mt-8">
                    Get Started <i class="fas fa-arrow-right"></i>
                </a>
                <p class="mt-5 text-sm" style="color:rgba(255,255,255,.75)">
                    No credit card required • Start with our free plan
                </p>
            </div>
        </div>
    </section>
@endsection
