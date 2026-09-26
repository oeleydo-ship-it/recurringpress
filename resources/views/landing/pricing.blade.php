@extends('layouts.landing')

@section('title', 'Pricing - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('meta_description', "Start free, upgrade when you're ready. No hidden fees, no surprises.")

@section('content')
    <section class="canvas section">
        <div class="wrap-sm text-center">
            <span class="eyebrow mb-5"><i class="fas fa-tag"></i> Pricing</span>
            <h1 class="display">Simple, Transparent Pricing</h1>
            <p class="lead mt-6">Start free, upgrade when you're ready. No hidden fees, no surprises.</p>
        </div>
    </section>

    <section class="pb-[var(--section-y)]">
        <div class="wrap">
            @php
                $usePlans = $plans->count() > 0;
                $planList = $usePlans ? $plans : collect([
                    (object)['name'=>'Free','slug'=>'free','description'=>'Perfect for getting started','price'=>0,'billing_cycle'=>'monthly','is_popular'=>false,'max_invoices'=>5,'max_clients'=>3,'max_users'=>1,'max_products'=>10,'stripe_enabled'=>false,'custom_branding'=>false,'api_access'=>false],
                    (object)['name'=>'Pro','slug'=>'pro','description'=>'For growing businesses','price'=>19,'billing_cycle'=>'monthly','is_popular'=>true,'max_invoices'=>null,'max_clients'=>null,'max_users'=>5,'max_products'=>null,'stripe_enabled'=>true,'custom_branding'=>true,'api_access'=>false],
                    (object)['name'=>'Enterprise','slug'=>'enterprise','description'=>'For large teams','price'=>49,'billing_cycle'=>'monthly','is_popular'=>false,'max_invoices'=>null,'max_clients'=>null,'max_users'=>null,'max_products'=>null,'stripe_enabled'=>true,'custom_branding'=>true,'api_access'=>true],
                ]);
                // Explicit map: an interpolated `grid-cols-{{ $n }}` class cannot be
                // statically extracted by a compiled Tailwind build. Narrow the wrapper
                // too, so two plans stay centred instead of leaving an empty column.
                $cols = [1 => 'md:grid-cols-1', 2 => 'md:grid-cols-2'][$planList->count()] ?? 'md:grid-cols-3';
                $maxW = [1 => 'max-w-sm', 2 => 'max-w-3xl'][$planList->count()] ?? 'max-w-5xl';
            @endphp

            <div class="grid {{ $cols }} gap-6 {{ $maxW }} mx-auto items-stretch">
                @foreach($planList as $plan)
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
                            <h2 class="text-lg font-bold" style="color:var(--ink-900);letter-spacing:-0.02em">{{ $plan->name }}</h2>
                            <p class="text-sm mt-1.5" style="color:var(--ink-500)">{{ $plan->description ?? 'Choose this plan' }}</p>

                            <p class="mt-6 flex items-baseline gap-1.5">
                                <span class="text-4xl font-extrabold" style="color:var(--ink-900);letter-spacing:-0.035em">
                                    ${{ number_format($plan->price, 0) }}
                                </span>
                                <span class="text-sm" style="color:var(--ink-500)">
                                    /{{ $plan->billing_cycle === 'yearly' ? 'yr' : 'mo' }}
                                </span>
                            </p>

                            <a href="{{ $plan->slug === 'enterprise' ? route('landing.contact') : route('register', ['plan' => $plan->slug]) }}"
                                class="btn {{ $popular ? 'btn-primary' : 'btn-ghost' }} w-full mt-7">
                                {{ $plan->slug === 'enterprise' ? 'Contact Sales' : 'Get Started' }}
                            </a>
                        </div>
                        <div class="px-8 pb-8 pt-6 mt-auto" style="border-top:1px solid var(--line)">
                            <p class="text-[0.6875rem] font-bold uppercase mb-4"
                                style="color:var(--ink-500);letter-spacing:0.12em">What's included</p>
                            <ul class="space-y-3 text-[0.9375rem]" style="color:var(--ink-700)">
                                @php
                                    $lines = [
                                        $plan->max_invoices ? $plan->max_invoices . ' invoices/month' : 'Unlimited invoices',
                                        $plan->max_clients ? $plan->max_clients . ' clients' : 'Unlimited clients',
                                        $plan->max_users ? $plan->max_users . ' team member' . ($plan->max_users > 1 ? 's' : '') : 'Unlimited team',
                                        $plan->max_products ? $plan->max_products . ' products' : 'Unlimited products',
                                    ];
                                    if (!empty($plan->stripe_enabled)) $lines[] = 'Stripe payments';
                                    if (!empty($plan->custom_branding)) $lines[] = 'Custom branding';
                                    if (!empty($plan->api_access)) $lines[] = 'API access';
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
                @endforeach
            </div>

            <ul class="mt-14 grid sm:grid-cols-3 gap-4 max-w-4xl mx-auto">
                @foreach([
                    ['fa-credit-card', 'var(--brand-50)', 'var(--brand-600)', 'No credit card required'],
                    ['fa-sync-alt', '#ECFDF5', '#059669', 'Cancel anytime'],
                    ['fa-lock', '#F5F3FF', '#7C3AED', 'Secure & private'],
                ] as [$icon, $tint, $fg, $label])
                    <li class="card-flat flex items-center gap-3.5 p-5" style="background:var(--surface-2)">
                        <span class="icon-tile" style="background:{{ $tint }};color:{{ $fg }}">
                            <i class="fas {{ $icon }}"></i>
                        </span>
                        <span class="font-semibold text-[0.9375rem]" style="color:var(--ink-900)">{{ $label }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    <section class="band section">
        <div class="wrap-sm text-center">
            <h2 class="h2">Questions about plans?</h2>
            <p class="lead mt-4">Our FAQ covers billing, upgrades, refunds and more.</p>
            <div class="mt-8 flex flex-col sm:flex-row gap-3 justify-center">
                <a href="{{ route('landing.faq') }}" class="btn btn-primary btn-lg">
                    Read the FAQ <i class="fas fa-arrow-right"></i>
                </a>
                <a href="{{ route('landing.contact') }}" class="btn btn-ghost btn-lg">Talk to us</a>
            </div>
        </div>
    </section>
@endsection
