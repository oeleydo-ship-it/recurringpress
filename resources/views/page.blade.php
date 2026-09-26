@extends('layouts.landing')

@section('title', $page->meta_title ?? $page->title)

@section('meta_description', $page->meta_description ?? Str::limit(strip_tags($page->content), 160))

@section('og_type', 'article')

@section('head')
    @if($page->meta_description)
        <meta name="description" content="{{ $page->meta_description }}">
    @endif

    {{-- Article-specific OG tags --}}
    <meta property="article:published_time" content="{{ $page->created_at->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $page->updated_at->toIso8601String() }}">
@endsection

@section('content')
    @if($page->editor_mode === 'blocks' && !empty($page->blocks))
        @if($page->show_title ?? true)
            <section class="pt-8 pb-4 bg-white">
                <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h1 class="text-4xl font-bold text-gray-900">{{ $page->title }}</h1>
                </div>
            </section>
        @endif
        @include('partials.block-renderer', ['blocks' => $page->blocks])
    @else
        <section class="py-12 bg-white">
            <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                @if($page->show_title ?? true)
                    <h1 class="text-4xl font-bold text-gray-900 mb-8">{{ $page->title }}</h1>
                @endif
                <div class="prose prose-lg max-w-none">
                    {!! $page->content !!}
                </div>
            </div>
        </section>
    @endif
@endsection