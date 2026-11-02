<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php($favicon = \App\Models\SystemSetting::get('site_favicon'))
    @if($favicon)
        <link rel="icon" href="{{ asset('storage/' . $favicon) }}">
    @endif

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800|poppins:400,500,600,700|roboto:400,500,700|open-sans:400,500,600,700|lato:400,700|nunito:400,600,700&display=swap" rel="stylesheet">

    @php($seo = \App\Support\Seo::forPage($page, request()))
    @include('partials.seo-tags')

    @include('partials.tracking-and-livechat')

    @viteReactRefresh
    @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
    <x-inertia::head>
        @if($seo)
            <title data-inertia="">{{ $seo['title'] }}</title>
            @if($seo['description'])
                <meta name="description" content="{{ $seo['description'] }}" data-inertia="description">
            @endif
            @if($seo['keywords'])
                <meta name="keywords" content="{{ $seo['keywords'] }}" data-inertia="keywords">
            @endif
            <link rel="canonical" href="{{ $seo['canonical'] }}" data-inertia="canonical">
        @else
            <title>{{ \App\Models\SystemSetting::get('site_name', 'RecurringPress') }}</title>
        @endif
    </x-inertia::head>
</head>
<body class="antialiased" data-site="{{ \App\Models\SystemSetting::get('site_name', 'RecurringPress') }}">
    <x-inertia::app />
</body>
</html>
