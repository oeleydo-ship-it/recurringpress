<!DOCTYPE html>
<html lang="en">
@php
    $siteName = \App\Models\SystemSetting::get('site_name', 'RecurringPress');
    $siteLogo = \App\Models\SystemSetting::get('site_logo');
    $hideSiteName = \App\Models\SystemSetting::get('hide_site_name');
@endphp

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', $siteName)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @if($siteLogo)
        <link rel="icon" type="image/png" href="{{ asset('storage/' . $siteLogo) }}">
    @endif
    <script>
        // Retained so existing `primary-*` utilities in the auth views keep working.
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { "50": "#f0f9ff", "100": "#e0f2fe", "200": "#bae6fd", "300": "#7dd3fc", "400": "#38bdf8", "500": "#0ea5e9", "600": "#0284c7", "700": "#0369a1", "800": "#075985", "900": "#0c4a6e" }
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    }
                }
            }
        }
    </script>
    @include('partials.marketing-css')
    <style>
        /* Auth split-screen shell. The left panel reuses the marketing CTA gradient
           so signing in feels continuous with the landing pages. */
        .auth-aside {
            position: relative;
            overflow: hidden;
            background:
                radial-gradient(ellipse 80% 120% at 15% 0%, rgba(255, 255, 255, .2), transparent 60%),
                linear-gradient(160deg, var(--brand-700) 0%, var(--brand-600) 45%, var(--brand-500) 100%);
        }

        .auth-aside::after {
            content: "";
            position: absolute;
            inset: 0;
            background-image: radial-gradient(rgba(255, 255, 255, .18) 1px, transparent 1px);
            background-size: 22px 22px;
            -webkit-mask-image: radial-gradient(ellipse 70% 70% at 30% 20%, #000, transparent 75%);
            mask-image: radial-gradient(ellipse 70% 70% at 30% 20%, #000, transparent 75%);
            pointer-events: none;
        }

        .auth-aside>* {
            position: relative;
            z-index: 1;
        }

        .glass {
            background: rgba(255, 255, 255, .12);
            border: 1px solid rgba(255, 255, 255, .18);
            backdrop-filter: blur(6px);
            -webkit-backdrop-filter: blur(6px);
        }
    </style>
    @yield('head')
</head>

<body>
    <div class="min-h-screen grid lg:grid-cols-2">
        {{-- Left: social proof --}}
        <aside class="auth-aside hidden lg:flex items-center justify-center p-12">
            <div class="max-w-md">
                <figure class="glass rounded-[var(--r-xl)] p-8 text-white">
                    <blockquote class="text-lg leading-relaxed">
                        “I can create, send, and track invoices in minutes. It just works.”
                    </blockquote>
                    <figcaption class="flex items-center justify-between gap-4 mt-7 pt-6"
                        style="border-top:1px solid rgba(255,255,255,.18)">
                        <span class="font-semibold">Small Business Owner</span>
                        <span class="glass rounded-[var(--r-sm)] w-10 h-10 inline-flex items-center justify-center">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </span>
                    </figcaption>
                </figure>

                <ul class="flex flex-wrap items-center gap-2.5 mt-7">
                    @foreach([
                        ['fa-star', '#FCD34D', '4.9 Rating'],
                        ['fa-heart', '#FCA5A5', 'Users Love Us'],
                        ['fa-shield-alt', '#6EE7B7', 'Secure'],
                    ] as [$icon, $color, $label])
                        <li class="glass rounded-full px-3.5 py-2 flex items-center gap-2">
                            <i class="fas {{ $icon }} text-xs" style="color:{{ $color }}"></i>
                            <span class="text-[0.8125rem] font-semibold text-white">{{ $label }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </aside>

        {{-- Right: form --}}
        <main class="flex items-center justify-center px-5 py-12 sm:px-8" style="background:var(--surface-2)">
            <div class="w-full max-w-md">
                <div class="text-center mb-8">
                    <a href="{{ route('landing') }}" class="brand-mark justify-center mb-5">
                        @if($siteLogo)
                            <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ $siteName }}" class="h-9 w-auto object-contain">
                        @else
                            <span class="icon-tile" style="width:2.25rem;height:2.25rem;background:#fff;border:1px solid var(--brand-200);color:var(--brand-600)">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </span>
                        @endif
                        @if(!$hideSiteName)
                            <span>{{ $siteName }}</span>
                        @endif
                    </a>
                    @yield('auth-header')
                </div>

                <div class="card p-7 sm:p-8">
                    @if($errors->any())
                        <div class="flex items-start gap-3 p-4 mb-5 rounded-[var(--r)] text-sm"
                            style="background:#FEF2F2;border:1px solid #FECACA;color:#B91C1C">
                            <i class="fas fa-exclamation-circle mt-0.5"></i>
                            <ul class="space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if(session('status'))
                        <div class="flex items-start gap-3 p-4 mb-5 rounded-[var(--r)] text-sm"
                            style="background:var(--ok-50);border:1px solid #A7F3D0;color:var(--ok-600)">
                            <i class="fas fa-check-circle mt-0.5"></i>
                            <span>{{ session('status') }}</span>
                        </div>
                    @endif

                    @yield('content')
                </div>

                <p class="text-center text-xs mt-6" style="color:var(--ink-400)">
                    &copy; {{ date('Y') }} {{ $siteName }}. All rights reserved.
                </p>
            </div>
        </main>
    </div>

    @include('partials.tracking-and-livechat')
</body>

</html>
