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

    @include('partials.tracking-and-livechat')

    @viteReactRefresh
    @vite(['resources/js/app.jsx', "resources/js/Pages/{$page['component']}.jsx"])
    <x-inertia::head>
        <title>{{ \App\Models\SystemSetting::get('site_name', 'RecurringPress') }}</title>
    </x-inertia::head>
</head>
<body class="antialiased">
    <x-inertia::app />
</body>
</html>
