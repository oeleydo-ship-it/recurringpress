@extends('layouts.landing')

@section('title', 'Contact Us - ' . \App\Models\SystemSetting::get('site_name', 'RecurringPress'))
@section('meta_description', "Have questions? Send us a message and we'll reply within 24 hours.")

@section('content')
    <section class="canvas section">
        <div class="wrap-sm text-center">
            <span class="eyebrow mb-5"><i class="fas fa-envelope-open-text"></i> Contact</span>
            <h1 class="display">Let's talk</h1>
            <p class="lead mt-6">
                Have questions? We'd love to hear from you. Send us a message and we'll reply within 24 hours.
            </p>
        </div>
    </section>

    <section class="pb-[var(--section-y)]">
        <div class="wrap">
            <div class="grid lg:grid-cols-5 gap-6 lg:gap-8 max-w-6xl mx-auto">
                {{-- Form --}}
                <div class="lg:col-span-3">
                    <div class="card p-7 sm:p-9">
                        <h2 class="h2" style="font-size:1.25rem">Send us a message</h2>
                        <p class="body-sm mt-1.5 mb-7">We'll get back to you as soon as possible.</p>

                        @if(session('success'))
                            <div class="flex items-start gap-3 p-4 mb-6 rounded-[var(--r)] text-[0.9375rem]"
                                style="background:var(--ok-50);border:1px solid #A7F3D0;color:var(--ok-600)">
                                <i class="fas fa-check-circle mt-0.5"></i>
                                <span>{{ session('success') }}</span>
                            </div>
                        @endif

                        @if($errors->any())
                            <div class="flex items-start gap-3 p-4 mb-6 rounded-[var(--r)] text-[0.9375rem]"
                                style="background:#FEF2F2;border:1px solid #FECACA;color:#B91C1C">
                                <i class="fas fa-exclamation-circle mt-0.5"></i>
                                <ul class="space-y-1">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form action="{{ route('landing.contact.submit') }}" method="POST" class="space-y-5">
                            @csrf
                            <x-honeypot />
                            <div class="grid sm:grid-cols-2 gap-5">
                                <div>
                                    <label for="name" class="label">Your Name</label>
                                    <input id="name" type="text" name="name" required value="{{ old('name') }}"
                                        placeholder="Jane Doe" class="field">
                                </div>
                                <div>
                                    <label for="email" class="label">Email Address</label>
                                    <input id="email" type="email" name="email" required value="{{ old('email') }}"
                                        placeholder="jane@company.com" class="field">
                                </div>
                            </div>
                            <div>
                                <label for="subject" class="label">Subject</label>
                                <input id="subject" type="text" name="subject" required value="{{ old('subject') }}"
                                    placeholder="How can we help?" class="field">
                            </div>
                            <div>
                                <label for="message" class="label">Message</label>
                                <textarea id="message" name="message" rows="6" required
                                    placeholder="Tell us a little more…" class="field">{{ old('message') }}</textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-paper-plane"></i> Send Message
                            </button>
                        </form>
                    </div>
                </div>

                {{-- Info sidebar --}}
                <div class="lg:col-span-2 space-y-4">
                    @foreach([
                        ['fa-envelope', 'var(--brand-50)', 'var(--brand-600)', 'var(--brand-500)', 'Email', 'support@recurringpress.com'],
                        ['fa-clock', '#ECFDF5', '#059669', '#10B981', 'Support Hours', 'Mon – Fri, 9am – 6pm EST'],
                        ['fa-comments', '#F5F3FF', '#7C3AED', '#8B5CF6', 'Live Chat', 'Available for Pro &amp; Enterprise'],
                    ] as [$icon, $tint, $fg, $bar, $title, $val])
                        <div class="card card-hover">
                            <span class="accent-bar" style="background:{{ $bar }}"></span>
                            <div class="p-5 flex items-start gap-4">
                                <span class="icon-tile" style="background:{{ $tint }};color:{{ $fg }}">
                                    <i class="fas {{ $icon }}"></i>
                                </span>
                                <div>
                                    <h3 class="font-semibold" style="color:var(--ink-900)">{{ $title }}</h3>
                                    <p class="body-sm mt-0.5">{!! $val !!}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <div class="p-6 rounded-[var(--r-lg)]"
                        style="background:var(--brand-50);border:1px solid var(--brand-200)">
                        <div class="flex items-center gap-2.5 mb-2">
                            <i class="fas fa-question-circle" style="color:var(--brand-600)"></i>
                            <h3 class="font-bold" style="color:var(--ink-900)">Looking for quick answers?</h3>
                        </div>
                        <p class="body-sm mb-4">Check out our frequently asked questions.</p>
                        <a href="{{ route('landing.faq') }}"
                            class="inline-flex items-center gap-1.5 font-semibold text-[0.9375rem]"
                            style="color:var(--brand-600)">
                            View FAQ <i class="fas fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
