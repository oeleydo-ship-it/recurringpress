<?php

namespace App\Http\Controllers;

use App\Models\CmsPost;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BlogController extends Controller
{
    public function index()
    {
        $posts = CmsPost::with('author:id,name')
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->orderBy('published_at', 'desc')
            ->paginate(9);

        return Inertia::render('Marketing/Blog/Index', [
            'posts' => $posts->through(fn (CmsPost $p) => $this->card($p)),
        ]);
    }

    public function show($slug)
    {
        $post = CmsPost::with('author:id,name')->where('slug', $slug)
            ->where('is_published', true)
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->firstOrFail();

        return Inertia::render('Marketing/Blog/Show', [
            'post' => [
                ...$this->card($post),
                // Superadmin-authored HTML (same trust model as before).
                'content' => $post->content,
                'meta_title' => $post->meta_title ?: $post->title,
                'meta_description' => $post->meta_description ?: $post->excerpt ?: Str::limit(trim(strip_tags((string) $post->content)), 160),
                'updated_at' => $post->updated_at?->toIso8601String(),
            ],
            'related' => CmsPost::where('is_published', true)
                ->where(function ($q) {
                    $q->whereNull('published_at')->orWhere('published_at', '<=', now());
                })
                ->whereKeyNot($post->id)
                ->orderBy('published_at', 'desc')->take(3)->get()
                ->map(fn (CmsPost $p) => $this->card($p)),
        ]);
    }

    private function card(CmsPost $p): array
    {
        $text = trim(strip_tags((string) $p->content));

        return [
            'slug' => $p->slug,
            'title' => $p->title,
            'excerpt' => $p->excerpt ?: Str::limit($text, 180),
            'image' => $p->featured_image ? (Str::startsWith($p->featured_image, ['http://', 'https://']) ? $p->featured_image : asset('storage/' . $p->featured_image)) : null,
            'author' => $p->author?->name,
            'published_at' => ($p->published_at ?? $p->created_at)?->toIso8601String(),
            'reading_minutes' => max(1, (int) ceil(str_word_count($text) / 220)),
        ];
    }
}
