@extends('layouts.landing')

@php
    $siteName = \App\Models\SystemSetting::get('site_name', 'RecurringPress');
    $publishedAt = $post->published_at ?? $post->created_at;
@endphp

@section('title', $post->meta_title ?? $post->title . ' - ' . $siteName . ' Blog')

@section('meta_description', $post->meta_description ?? $post->excerpt ?? Str::limit(strip_tags($post->content), 160))

@section('og_type', 'article')

@section('head')
    {{-- Article-specific meta --}}
    <meta property="article:published_time" content="{{ $publishedAt->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $post->updated_at->toIso8601String() }}">
    @if($post->author)
        <meta property="article:author" content="{{ $post->author->name }}">
    @endif

    {{-- Featured image for OG --}}
    @if($post->featured_image)
        <meta property="og:image" content="{{ asset('storage/' . $post->featured_image) }}">
        <meta name="twitter:image" content="{{ asset('storage/' . $post->featured_image) }}">
    @endif
@endsection

@section('content')
    <article class="section">
        <div class="wrap-sm">
            <a href="{{ route('blog.index') }}"
                class="inline-flex items-center gap-2 font-semibold text-[0.9375rem] mb-8"
                style="color:var(--brand-600)">
                <i class="fas fa-arrow-left text-xs"></i> Back to Blog
            </a>

            <header>
                <p class="kicker">
                    <time datetime="{{ $publishedAt->toDateString() }}">{{ $publishedAt->format('F d, Y') }}</time>
                    @if($post->author)
                        <span aria-hidden="true" class="mx-1.5">·</span> By {{ $post->author->name }}
                    @endif
                </p>
                <h1 class="h1 mt-3">{{ $post->title }}</h1>
                @if($post->excerpt)
                    <p class="lead mt-5">{{ $post->excerpt }}</p>
                @endif
            </header>

            @if($post->featured_image)
                <img src="{{ asset('storage/' . $post->featured_image) }}" alt="{{ $post->title }}"
                    class="w-full h-auto mt-10 rounded-[var(--r-lg)]" style="border:1px solid var(--line)">
            @endif

            {{-- Styling comes from the .prose rules in layouts/landing.blade.php. --}}
            <div class="prose prose-lg max-w-none mt-10">
                {!! $post->formatted_content ?: $post->content !!}
            </div>

            <footer class="mt-14 pt-8" style="border-top:1px solid var(--line)">
                <h2 class="h3 mb-4">Share this article</h2>
                <div class="flex items-center gap-3">
                    @php
                        $url = urlencode(request()->url());
                        $title = urlencode($post->title);
                        $shares = [
                            ['fab fa-twitter', 'Share on Twitter', "https://twitter.com/intent/tweet?url={$url}&text={$title}"],
                            ['fab fa-facebook-f', 'Share on Facebook', "https://www.facebook.com/sharer/sharer.php?u={$url}"],
                            ['fab fa-linkedin-in', 'Share on LinkedIn', "https://www.linkedin.com/shareArticle?mini=true&url={$url}&title={$title}"],
                        ];
                    @endphp
                    @foreach($shares as [$icon, $label, $href])
                        <a href="{{ $href }}" target="_blank" rel="noopener noreferrer" class="social-dot"
                            aria-label="{{ $label }}" title="{{ $label }}">
                            <i class="{{ $icon }}"></i>
                        </a>
                    @endforeach
                </div>
            </footer>
        </div>
    </article>
@endsection
