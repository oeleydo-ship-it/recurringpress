{{-- Tags only crawlers read, so they stay outside Inertia's head management. Every value is escaped; the JSON-LD is encoded by App\Support\Seo. --}}
@if($seo)
    @if($seo['robots'])
        <meta name="robots" content="{{ $seo['robots'] }}">
    @endif
    @if($seo['google_verification'])
        <meta name="google-site-verification" content="{{ $seo['google_verification'] }}">
    @endif
    @if($seo['bing_verification'])
        <meta name="msvalidate.01" content="{{ $seo['bing_verification'] }}">
    @endif
    <meta property="og:site_name" content="{{ $seo['site_name'] }}">
    <meta property="og:type" content="{{ $seo['og_type'] }}">
    <meta property="og:title" content="{{ $seo['og_title'] }}">
    <meta property="og:url" content="{{ $seo['canonical'] }}">
    @if($seo['description'])
        <meta property="og:description" content="{{ $seo['description'] }}">
    @endif
    @if($seo['image'])
        <meta property="og:image" content="{{ $seo['image'] }}">
    @endif
    @if($seo['published_at'])
        <meta property="article:published_time" content="{{ $seo['published_at'] }}">
    @endif
    @if($seo['modified_at'])
        <meta property="article:modified_time" content="{{ $seo['modified_at'] }}">
    @endif
    <meta name="twitter:card" content="{{ $seo['image'] ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $seo['og_title'] }}">
    @if($seo['description'])
        <meta name="twitter:description" content="{{ $seo['description'] }}">
    @endif
    @if($seo['image'])
        <meta name="twitter:image" content="{{ $seo['image'] }}">
    @endif
    @if($seo['twitter_site'])
        <meta name="twitter:site" content="{{ $seo['twitter_site'] }}">
    @endif
    @if($seo['json_ld'])
        <script type="application/ld+json">{!! $seo['json_ld'] !!}</script>
    @endif
@elseif(! \App\Support\Seo::isIndexableAppPage($page['component']))
    <meta name="robots" content="noindex, nofollow">
@endif
