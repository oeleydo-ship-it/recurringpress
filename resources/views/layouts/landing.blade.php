<!DOCTYPE html>
<html lang="en">
@php
    $siteName = \App\Models\SystemSetting::get('site_name', 'RecurringPress');
    $siteLogo = \App\Models\SystemSetting::get('site_logo');
    $hideSiteName = \App\Models\SystemSetting::get('hide_site_name');
    $headerMenu = \App\Models\Menu::getItems('header');
    $footerProduct = \App\Models\Menu::getItems('footer_product');
    $footerCompany = \App\Models\Menu::getItems('footer_company');
    $footerLegal = \App\Models\Menu::getItems('footer_legal');

    // SEO Settings
    $seoDescription = \App\Models\SystemSetting::get('seo_description', 'Professional invoicing software for freelancers and small businesses.');
    $seoKeywords = \App\Models\SystemSetting::get('seo_keywords', 'invoicing, billing, invoice software, freelancer invoicing');
    $seoOgImage = \App\Models\SystemSetting::get('seo_og_image');
    $siteUrl = url('/');
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', $siteName)</title>

    {{-- Basic SEO --}}
    <meta name="description" content="@yield('meta_description', $seoDescription)">
    <meta name="keywords" content="@yield('meta_keywords', $seoKeywords)">
    <meta name="author" content="{{ $siteName }}">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    {{-- Open Graph / Facebook --}}
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="@yield('title', $siteName)">
    <meta property="og:description" content="@yield('meta_description', $seoDescription)">
    <meta property="og:site_name" content="{{ $siteName }}">
    @if($seoOgImage)
        <meta property="og:image" content="{{ asset('storage/' . $seoOgImage) }}">
    @endif
    @yield('og_image')

    {{-- Twitter Card --}}
    <meta name="twitter:card" content="@yield('twitter_card', 'summary_large_image')">
    <meta name="twitter:title" content="@yield('title', $siteName)">
    <meta name="twitter:description" content="@yield('meta_description', $seoDescription)">
    @if($seoOgImage)
        <meta name="twitter:image" content="{{ asset('storage/' . $seoOgImage) }}">
    @endif
    @yield('twitter_image')

    {{-- Favicon --}}
    @if($siteLogo)
        <link rel="icon" type="image/png" href="{{ asset('storage/' . $siteLogo) }}">
    @endif

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @include('partials.marketing-css')
    @yield('head')
</head>

<body>
    <a href="#main"
        class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] focus:px-4 focus:py-2 focus:bg-white focus:rounded-lg focus:shadow-lg focus:text-slate-900 focus:font-semibold">
        Skip to content
    </a>

    <header x-data="{ open: false, scrolled: false }" x-init="scrolled = window.scrollY > 8"
        @scroll.window="scrolled = window.scrollY > 8" :data-scrolled="scrolled" class="site-header">
        <div class="wrap h-full">
            <div class="flex h-full items-center justify-between gap-4">
                <a href="{{ route('landing') }}" class="brand-mark shrink-0">
                    @if($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ $siteName }}" class="h-8 w-auto object-contain">
                    @else
                        <span class="icon-tile" style="width:2.25rem;height:2.25rem;background:var(--brand-50);border:1px solid var(--brand-200);color:var(--brand-600)">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </span>
                    @endif
                    @if(!$hideSiteName)
                        <span>{{ $siteName }}</span>
                    @endif
                </a>

                {{-- Desktop nav. Admin-managed menu wins; the built-in links are the fallback. --}}
                <nav class="hidden lg:flex items-center gap-8" aria-label="Main">
                    @forelse($headerMenu as $item)
                        <a href="{{ $item['url'] ?? '#' }}" class="navlink">{{ $item['label'] ?? '' }}</a>
                    @empty
                        <a href="{{ route('landing.features') }}" class="navlink"
                            @if(request()->routeIs('landing.features')) aria-current="page" @endif>Features</a>
                        <a href="{{ route('landing.pricing') }}" class="navlink"
                            @if(request()->routeIs('landing.pricing')) aria-current="page" @endif>Pricing</a>
                        <a href="{{ route('landing.about') }}" class="navlink"
                            @if(request()->routeIs('landing.about')) aria-current="page" @endif>About</a>
                        <a href="{{ route('landing.faq') }}" class="navlink"
                            @if(request()->routeIs('landing.faq')) aria-current="page" @endif>FAQ</a>
                        <a href="{{ route('landing.contact') }}" class="navlink"
                            @if(request()->routeIs('landing.contact')) aria-current="page" @endif>Contact</a>
                        <a href="{{ route('blog.index') }}" class="navlink"
                            @if(request()->routeIs('blog.*')) aria-current="page" @endif>Blog</a>
                    @endforelse
                </nav>

                <div class="hidden lg:flex items-center gap-2">
                    <a href="{{ route('login') }}" class="btn btn-quiet">Sign In</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Get Started Free</a>
                </div>

                <button @click="open = !open" :aria-expanded="open" aria-controls="mobile-nav"
                    class="lg:hidden -mr-2 p-2.5 rounded-xl text-slate-600 hover:bg-slate-100 transition">
                    <span class="sr-only">Toggle navigation</span>
                    <i x-show="!open" class="fas fa-bars text-lg"></i>
                    <i x-show="open" x-cloak class="fas fa-times text-lg"></i>
                </button>
            </div>
        </div>

        <div id="mobile-nav" x-show="open" x-cloak @click.outside="open = false"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-2"
            class="lg:hidden bg-white border-t" style="border-color:var(--line);box-shadow:var(--sh-card)">
            <div class="wrap py-4 space-y-1">
                @forelse($headerMenu as $item)
                    <a href="{{ $item['url'] ?? '#' }}" class="navlink-mobile">{{ $item['label'] ?? '' }}</a>
                @empty
                    <a href="{{ route('landing.features') }}" class="navlink-mobile">Features</a>
                    <a href="{{ route('landing.pricing') }}" class="navlink-mobile">Pricing</a>
                    <a href="{{ route('landing.about') }}" class="navlink-mobile">About</a>
                    <a href="{{ route('landing.faq') }}" class="navlink-mobile">FAQ</a>
                    <a href="{{ route('landing.contact') }}" class="navlink-mobile">Contact</a>
                    <a href="{{ route('blog.index') }}" class="navlink-mobile">Blog</a>
                @endforelse
                <hr class="rule my-3">
                <a href="{{ route('login') }}" class="navlink-mobile">Sign In</a>
                <a href="{{ route('register') }}" class="btn btn-primary w-full mt-1">Get Started Free</a>
            </div>
        </div>
    </header>

    <main id="main" class="site-main">
        @yield('content')
    </main>

    <footer class="band" style="background:var(--surface-2);border-bottom:0">
        <div class="wrap py-16">
            <div class="grid gap-10 md:grid-cols-2 lg:grid-cols-4">
                <div class="lg:pr-8">
                    <a href="{{ route('landing') }}" class="brand-mark mb-4">
                        @if($siteLogo)
                            <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ $siteName }}" class="h-8 w-auto object-contain">
                        @else
                            <span class="icon-tile" style="width:2.25rem;height:2.25rem;background:#fff;border:1px solid var(--brand-200);color:var(--brand-600)">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </span>
                        @endif
                        @if(!$hideSiteName)
                            <span>{{ $siteName }}</span>
                        @endif
                    </a>
                    <p class="body-sm max-w-xs">
                        {{ \App\Models\SystemSetting::get('landing_footer_text', 'Professional invoicing software for freelancers and small businesses.') }}
                    </p>
                </div>

                <div>
                    <h4 class="foot-head mb-4">Product</h4>
                    <ul class="space-y-2.5">
                        @forelse($footerProduct as $item)
                            <li><a href="{{ $item['url'] ?? '#' }}" class="foot-link">{{ $item['label'] ?? '' }}</a></li>
                        @empty
                            <li><a href="{{ route('landing.features') }}" class="foot-link">Features</a></li>
                            <li><a href="{{ route('landing.pricing') }}" class="foot-link">Pricing</a></li>
                            <li><a href="{{ route('landing.faq') }}" class="foot-link">FAQ</a></li>
                        @endforelse
                    </ul>
                </div>

                <div>
                    <h4 class="foot-head mb-4">Company</h4>
                    <ul class="space-y-2.5">
                        @forelse($footerCompany as $item)
                            <li><a href="{{ $item['url'] ?? '#' }}" class="foot-link">{{ $item['label'] ?? '' }}</a></li>
                        @empty
                            <li><a href="{{ route('landing.about') }}" class="foot-link">About</a></li>
                            <li><a href="{{ route('blog.index') }}" class="foot-link">Blog</a></li>
                            <li><a href="{{ route('landing.contact') }}" class="foot-link">Contact</a></li>
                        @endforelse
                    </ul>
                </div>

                <div>
                    <h4 class="foot-head mb-4">Get started</h4>
                    <ul class="space-y-2.5">
                        <li><a href="{{ route('register') }}" class="foot-link">Create an account</a></li>
                        <li><a href="{{ route('login') }}" class="foot-link">Sign in</a></li>
                    </ul>
                    @if(!empty($footerLegal))
                        <h4 class="foot-head mt-8 mb-4">Legal</h4>
                        <ul class="space-y-2.5">
                            @foreach($footerLegal as $item)
                                <li><a href="{{ $item['url'] ?? '#' }}" class="foot-link">{{ $item['label'] ?? '' }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="mt-14 pt-8 flex flex-col sm:flex-row items-center justify-between gap-5"
                style="border-top:1px solid var(--line)">
                <p class="body-sm">&copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.</p>
                <div class="flex items-center gap-2.5">
                    <a href="#" class="social-dot" aria-label="Twitter"><i class="fab fa-twitter"></i></a>
                    <a href="#" class="social-dot" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-dot" aria-label="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" class="social-dot" aria-label="GitHub"><i class="fab fa-github"></i></a>
                </div>
            </div>
        </div>
    </footer>

    @include('partials.tracking-and-livechat')
</body>

</html>
