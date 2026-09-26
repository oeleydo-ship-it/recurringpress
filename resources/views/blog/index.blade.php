@extends('layouts.landing')

@php
    $siteName = \App\Models\SystemSetting::get('site_name', 'RecurringPress');
@endphp

@section('title', 'Blog - ' . $siteName)

@section('meta_description', 'Tips, guides, and insights for freelancers and small businesses. Read our latest articles on invoicing, billing and business growth.')

@section('content')
    <section class="canvas section">
        <div class="wrap-sm text-center">
            <span class="eyebrow mb-5"><i class="fas fa-newspaper"></i> Blog</span>
            <h1 class="display">Blog</h1>
            <p class="lead mt-6">Tips, guides, and insights for freelancers and small businesses.</p>
        </div>
    </section>

    <section class="band section">
        <div class="wrap">
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
                @forelse($posts as $post)
                    <article class="card card-hover flex flex-col">
                        <a href="{{ route('blog.show', $post->slug) }}" class="block" tabindex="-1" aria-hidden="true">
                            @if($post->featured_image)
                                <img src="{{ asset('storage/' . $post->featured_image) }}" alt=""
                                    class="w-full aspect-[16/9] object-cover">
                            @else
                                <div class="w-full aspect-[16/9] flex items-center justify-center"
                                    style="background:linear-gradient(135deg,var(--brand-50),var(--brand-100))">
                                    <i class="fas fa-newspaper text-4xl" style="color:var(--brand-200)"></i>
                                </div>
                            @endif
                        </a>
                        <div class="p-6 flex flex-col flex-1">
                            <p class="text-xs font-semibold uppercase" style="color:var(--brand-600);letter-spacing:0.1em">
                                <time datetime="{{ ($post->published_at ?? $post->created_at)->toDateString() }}">
                                    {{ $post->published_at?->format('M d, Y') ?? $post->created_at->format('M d, Y') }}
                                </time>
                            </p>
                            <h2 class="mt-2.5 text-base font-bold" style="color:var(--ink-900);letter-spacing:-0.018em;line-height:1.35">
                                <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-[var(--brand-600)] transition">
                                    {{ $post->title }}
                                </a>
                            </h2>
                            <p class="body-sm mt-3 flex-1">
                                {{ $post->excerpt ?? Str::limit(strip_tags($post->content), 120) }}
                            </p>
                            <a href="{{ route('blog.show', $post->slug) }}"
                                class="mt-5 inline-flex items-center gap-1.5 font-semibold text-[0.9375rem]"
                                style="color:var(--brand-600)">
                                Read more <i class="fas fa-arrow-right text-xs"></i>
                            </a>
                        </div>
                    </article>
                @empty
                    <div class="md:col-span-2 lg:col-span-3 card-flat text-center py-16 px-6">
                        <i class="fas fa-newspaper text-5xl mb-4" style="color:var(--line-strong)"></i>
                        <p class="lead">No blog posts yet. Check back soon!</p>
                    </div>
                @endforelse
            </div>

            @if($posts->hasPages())
                <div class="mt-12">
                    {{ $posts->links() }}
                </div>
            @endif
        </div>
    </section>
@endsection
