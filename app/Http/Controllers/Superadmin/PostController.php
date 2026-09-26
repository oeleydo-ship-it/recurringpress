<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\CmsPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PostController extends Controller
{
    public function index()
    {
        $posts = CmsPost::with('author:id,name')->latest()->paginate(20);

        return Inertia::render('Superadmin/Posts/Index', [
            'posts' => $posts->through(fn (CmsPost $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'author' => $p->author?->name,
                'is_published' => (bool) $p->is_published,
                'published_at' => $p->published_at?->toIso8601String(),
                'updated_at' => $p->updated_at?->toIso8601String(),
                'image' => $this->imageUrl($p),
                'url' => route('blog.show', $p->slug),
            ]),
        ]);
    }

    public function create()
    {
        return Inertia::render('Superadmin/Posts/Form', ['post' => null]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated['author_id'] = Auth::guard('superadmin')->id();
        $validated['published_at'] = $validated['is_published'] ? now() : null;

        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('posts', 'public');
        }

        $post = CmsPost::create($validated);

        return redirect()->route('superadmin.posts.edit', $post)->with('success', 'Post created.');
    }

    public function edit(CmsPost $post)
    {
        return Inertia::render('Superadmin/Posts/Form', [
            'post' => [
                ...$post->only('id', 'title', 'slug', 'content', 'excerpt', 'meta_title', 'meta_description'),
                'is_published' => (bool) $post->is_published,
                'image' => $this->imageUrl($post),
                'url' => route('blog.show', $post->slug),
            ],
        ]);
    }

    public function update(Request $request, CmsPost $post)
    {
        $validated = $this->validated($request, $post);

        // Keep the original publish date; set it the first time a post goes live.
        if ($validated['is_published'] && ! $post->published_at) {
            $validated['published_at'] = now();
        }

        if ($request->hasFile('featured_image')) {
            $this->deleteImage($post);
            $validated['featured_image'] = $request->file('featured_image')->store('posts', 'public');
        } elseif ($request->boolean('remove_image')) {
            $this->deleteImage($post);
            $validated['featured_image'] = null;
        }

        $post->update($validated);

        return redirect()->route('superadmin.posts.edit', $post)->with('success', 'Post saved.');
    }

    public function destroy(CmsPost $post)
    {
        $this->deleteImage($post);
        $post->delete();

        return redirect()->route('superadmin.posts.index')->with('success', 'Post deleted.');
    }

    private function validated(Request $request, ?CmsPost $post = null): array
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', Rule::unique('cms_posts', 'slug')->ignore($post?->id)],
            'content' => 'nullable|string|max:500000',
            'excerpt' => 'nullable|string|max:500',
            'featured_image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:4096',
            'remove_image' => 'nullable|boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:255',
            'is_published' => 'boolean',
        ]);

        unset($validated['featured_image'], $validated['remove_image']);
        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['title']);
        $validated['is_published'] = $request->boolean('is_published');

        return $validated;
    }

    private function deleteImage(CmsPost $post): void
    {
        if ($post->featured_image && ! Str::startsWith($post->featured_image, ['http://', 'https://'])) {
            Storage::disk('public')->delete($post->featured_image);
        }
    }

    private function imageUrl(CmsPost $p): ?string
    {
        if (! $p->featured_image) {
            return null;
        }

        return Str::startsWith($p->featured_image, ['http://', 'https://']) ? $p->featured_image : asset('storage/' . $p->featured_image);
    }
}
