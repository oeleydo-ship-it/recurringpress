@extends('layouts.landing')

@php $siteName = \App\Models\SystemSetting::get('site_name', 'RecurringPress'); @endphp

@section('title', 'About Us - ' . $siteName)
@section('meta_description', "We're on a mission to help freelancers and small businesses get paid faster with professional invoicing tools that are simple to use.")

@section('content')
    <section class="canvas section">
        <div class="wrap-sm text-center">
            <span class="eyebrow mb-5"><i class="fas fa-users"></i> About us</span>
            <h1 class="display">Built for freelancers,<br>loved by businesses</h1>
            <p class="lead mt-6">
                We're on a mission to help freelancers and small businesses get paid faster with professional
                invoicing tools that are simple to use.
            </p>
        </div>
    </section>

    <section class="band section">
        <div class="wrap">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <div>
                    <span class="kicker">Our story</span>
                    <h2 class="h1 mt-2">Why we built {{ $siteName }}</h2>
                    <div class="mt-6 space-y-4 lead">
                        <p>{{ $siteName }} was born out of frustration with complicated invoicing software. As freelancers ourselves, we knew there had to be a better way.</p>
                        <p>We built it to be simple, beautiful, and powerful — everything you need to create professional invoices and get paid faster, without the complexity.</p>
                        <p>Today, thousands of freelancers and small businesses trust {{ $siteName }} to manage their invoicing and grow their businesses.</p>
                    </div>
                </div>

                <dl class="grid grid-cols-2 gap-4">
                    @foreach([
                        ['10K+', 'Active users', 'var(--brand-500)', null],
                        ['$50M+', 'Invoiced', '#10B981', null],
                        ['100K+', 'Invoices sent', '#8B5CF6', null],
                        ['4.9', 'User rating', '#F97316', 'star'],
                    ] as [$num, $label, $color, $star])
                        <div class="card p-6">
                            <dt class="sr-only">{{ $label }}</dt>
                            <dd>
                                <span class="block text-3xl font-extrabold" style="color:{{ $color }};letter-spacing:-0.035em">
                                    {{ $num }}@if($star)<i class="fas fa-star align-middle ml-1" style="font-size:0.875rem"></i>@endif
                                </span>
                                <span class="block text-sm mt-1.5" style="color:var(--ink-600)">{{ $label }}</span>
                            </dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>
    </section>

    <section class="section">
        <div class="wrap">
            <div class="text-center max-w-2xl mx-auto mb-14">
                <span class="kicker">Our values</span>
                <h2 class="h1 mt-2">What guides us every day</h2>
            </div>

            @php
                $values = [
                    ['icon'=>'fa-heart','tint'=>'var(--brand-50)','fg'=>'var(--brand-600)','bar'=>'var(--brand-500)','title'=>'Customer First','desc'=>'Everything we build is designed with our customers in mind. Your success is our success.'],
                    ['icon'=>'fa-bolt','tint'=>'#ECFDF5','fg'=>'#059669','bar'=>'#10B981','title'=>'Simplicity','desc'=>'Powerful software doesn\'t have to be complicated. Simple is always better.'],
                    ['icon'=>'fa-shield-alt','tint'=>'#F5F3FF','fg'=>'#7C3AED','bar'=>'#8B5CF6','title'=>'Trust &amp; Security','desc'=>'Your data is safe with us. We use industry-standard security to protect your information.'],
                ];
            @endphp
            <div class="grid md:grid-cols-3 gap-5">
                @foreach($values as $v)
                    <article class="card card-hover">
                        <span class="accent-bar" style="background:{{ $v['bar'] }}"></span>
                        <div class="p-8 text-center">
                            <span class="icon-tile icon-tile-lg mx-auto mb-5"
                                style="background:{{ $v['tint'] }};color:{{ $v['fg'] }}">
                                <i class="fas {{ $v['icon'] }} text-xl"></i>
                            </span>
                            <h3 class="h3 mb-2">{!! $v['title'] !!}</h3>
                            <p class="body-sm">{!! $v['desc'] !!}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </section>

    <section class="band section">
        <div class="wrap-md">
            <div class="cta-panel">
                <h2 class="h1 text-white">Join thousands of happy users</h2>
                <p class="lead mt-4 mx-auto max-w-xl" style="color:rgba(255,255,255,.9)">
                    Start your journey today and see why businesses love {{ $siteName }}.
                </p>
                <a href="{{ route('register') }}" class="btn btn-white btn-lg mt-8">
                    Get Started Free <i class="fas fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </section>
@endsection
