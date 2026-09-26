@extends('layouts.landing')

@php
    $siteName = \App\Models\SystemSetting::get('site_name', 'RecurringPress');

    // Categories and Q&A live here as data so the markup stays a single loop.
    $groups = [
        [
            'label' => 'General',
            'icon' => 'fa-info-circle',
            'tint' => 'var(--brand-50)',
            'fg' => 'var(--brand-600)',
            'items' => [
                ['q' => 'What is RecurringPress?', 'a' => "RecurringPress is a professional invoicing and billing platform designed to help businesses create, send, and manage invoices effortlessly. It supports recurring billing, quotations, and multiple payment methods to streamline your financial operations."],
                ['q' => 'How do I get started?', 'a' => "Getting started is easy! Simply create a free account, set up your company profile, and you can start creating invoices right away. Our onboarding wizard will guide you through the initial setup process."],
                ['q' => 'Is there a free trial?', 'a' => "Yes! We offer a free plan that includes basic invoicing features. You can upgrade to a paid plan anytime to unlock advanced features like recurring invoices, custom branding, and priority support."],
            ],
        ],
        [
            'label' => 'Billing & Payments',
            'icon' => 'fa-credit-card',
            'tint' => '#ECFDF5',
            'fg' => '#059669',
            'items' => [
                ['q' => 'What payment methods do you accept?', 'a' => "We accept all major credit cards through Stripe, including Visa, Mastercard, American Express, and Discover. Bank transfers and other payment methods may be available depending on your region."],
                ['q' => 'Can I change my plan later?', 'a' => "Absolutely! You can upgrade or downgrade your plan at any time from your account settings. Changes take effect immediately, and we'll prorate any payments accordingly."],
                ['q' => 'Do you offer refunds?', 'a' => "Yes, we offer a 30-day money-back guarantee for all paid plans. If you're not satisfied with our service, contact our support team within 30 days of your purchase for a full refund."],
            ],
        ],
        [
            'label' => 'Features',
            'icon' => 'fa-star',
            'tint' => '#F5F3FF',
            'fg' => '#7C3AED',
            'items' => [
                ['q' => 'Can I customize my invoices?', 'a' => "Yes! You can customize your invoices with your company logo, colors, and choose from multiple professional templates. Premium plans offer additional customization options including custom fields and branding."],
                ['q' => 'What are recurring invoices?', 'a' => "Recurring invoices are automatically generated and sent to your clients on a schedule you define (weekly, monthly, yearly, etc.). This is perfect for subscription-based businesses or clients on retainer agreements."],
                ['q' => 'Can I send quotations to clients?', 'a' => "Yes! You can create and send professional quotations to your clients. Once accepted, quotations can be converted to invoices with a single click, saving you time and ensuring accuracy."],
            ],
        ],
        [
            'label' => 'Support',
            'icon' => 'fa-headset',
            'tint' => '#FFF7ED',
            'fg' => '#EA580C',
            'items' => [
                ['q' => 'How can I contact support?', 'a' => "You can reach our support team by email at support@recurringpress.com or through the contact form on our website. Pro and Enterprise users also have access to live chat support during business hours."],
                ['q' => 'What are your support hours?', 'a' => "Our support team is available Monday through Friday, 9am to 6pm EST. We typically respond to emails within 24 hours. Enterprise customers receive priority support with faster response times."],
                ['q' => 'Is my data secure?', 'a' => "Absolutely! We take security seriously. All data is encrypted in transit and at rest. We use industry-standard security practices and regularly audit our systems to ensure your information is protected."],
            ],
        ],
    ];

    // Precomputed here rather than inline in the @json directive: Blade's directive
    // argument scanner cannot parse a multi-line nested array expression.
    $faqSchema = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => collect($groups)
            ->flatMap(fn($g) => collect($g['items'])->map(fn($i) => [
                '@type' => 'Question',
                'name' => $i['q'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $i['a']],
            ]))
            ->values()
            ->all(),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
@endphp

@section('title', 'FAQ - ' . $siteName)
@section('meta_description', 'Find answers to common questions about ' . $siteName . ' — billing, features, security and support.')

@section('head')
    {{-- FAQPage structured data, generated from the same source as the visible copy. --}}
    <script type="application/ld+json">{!! $faqSchema !!}</script>
@endsection

@section('content')
    <section class="canvas section">
        <div class="wrap-sm text-center">
            <span class="eyebrow mb-5"><i class="fas fa-question-circle"></i> Help Center</span>
            <h1 class="display">Frequently Asked Questions</h1>
            <p class="lead mt-6">Find answers to common questions about {{ $siteName }}.</p>
        </div>
    </section>

    {{-- One shared scope so opening an answer closes the others, matching the
         previous accordion behaviour. --}}
    <section class="band section" x-data="{ open: null }">
        <div class="wrap-sm">
            @foreach($groups as $gi => $group)
                <div class="{{ $gi > 0 ? 'mt-12' : '' }}">
                    <h2 class="flex items-center gap-3 mb-5">
                        <span class="icon-tile" style="background:{{ $group['tint'] }};color:{{ $group['fg'] }}">
                            <i class="fas {{ $group['icon'] }}"></i>
                        </span>
                        <span class="text-base font-bold" style="color:var(--ink-900);letter-spacing:-0.02em">
                            {{ $group['label'] }}
                        </span>
                    </h2>

                    <div class="space-y-3">
                        @foreach($group['items'] as $ii => $item)
                            @php $key = "$gi-$ii"; @endphp
                            <div class="card">
                                <h3>
                                    <button type="button" @click="open = (open === '{{ $key }}') ? null : '{{ $key }}'"
                                        :aria-expanded="open === '{{ $key }}'" aria-controls="faq-{{ $key }}"
                                        class="w-full flex items-center justify-between gap-4 text-left px-6 py-5 transition hover:bg-[var(--surface-2)]">
                                        <span class="font-semibold text-[0.9375rem]"
                                            style="color:var(--ink-900);letter-spacing:-0.012em">{{ $item['q'] }}</span>
                                        <span class="icon-tile shrink-0"
                                            style="width:1.75rem;height:1.75rem;border-radius:999px;background:var(--surface-2);color:var(--ink-500)">
                                            <i class="fas fa-chevron-down text-[0.625rem] transition-transform duration-200"
                                                :class="open === '{{ $key }}' && 'rotate-180'"></i>
                                        </span>
                                    </button>
                                </h3>
                                <div id="faq-{{ $key }}" x-show="open === '{{ $key }}'" x-cloak
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0">
                                    <p class="px-6 pb-5 body-sm" style="max-width:62ch">{{ $item['a'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <div class="cta-panel mt-16">
                <h2 class="h2 text-white">Still have questions?</h2>
                <p class="lead mt-3.5 mx-auto max-w-lg" style="color:rgba(255,255,255,.9)">
                    Can't find the answer you're looking for? Our support team is here to help.
                </p>
                <a href="{{ route('landing.contact') }}" class="btn btn-white btn-lg mt-7">
                    <i class="fas fa-envelope"></i> Contact Support
                </a>
            </div>
        </div>
    </section>
@endsection
