<?php

namespace App\Http\Controllers\Superadmin;

use App\Http\Controllers\Controller;
use App\Models\AiBlogRun;
use App\Models\CmsPost;
use App\Models\SystemSetting;
use App\Services\Ai\AiBlogService;
use App\Services\Ai\AiClient;
use App\Services\Ai\AiWritingStyle;
use App\Services\Ai\AiProviders;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class PostController extends Controller
{
    public function index(AiBlogService $blogService)
    {
        $posts = CmsPost::with('author:id,name')->latest()->paginate(20);

        $now = now();
        $keywordsRaw = (string) SystemSetting::get('ai_blog_keywords', '');
        $parsedKeywords = AiBlogService::parseKeywords($keywordsRaw);
        $usedKeywords = $blogService->getUsedKeywords();
        $unusedCount = count(array_filter($parsedKeywords, fn ($kw) => ! in_array(mb_strtolower($kw), $usedKeywords, true)));

        return Inertia::render('Superadmin/Posts/Index', [
            'posts' => $posts->through(fn (CmsPost $p) => [
                'id' => $p->id,
                'title' => $p->title,
                'slug' => $p->slug,
                'author' => $p->author?->name,
                'is_published' => (bool) $p->is_published,
                'is_scheduled' => ! $p->is_published && $p->published_at && $p->published_at->isFuture(),
                'is_ai_generated' => (bool) $p->is_ai_generated,
                'target_keyword' => $p->target_keyword,
                'published_at' => $p->published_at?->toIso8601String(),
                'updated_at' => $p->updated_at?->toIso8601String(),
                'image' => $this->imageUrl($p),
                'url' => route('blog.show', $p->slug),
            ]),
            'generationRuns' => AiBlogRun::latest()->limit(10)->get()->map(fn ($run) => [
                'id' => $run->id, 'keyword' => $run->keyword, 'status' => $run->status,
                'post_id' => $run->post_id, 'error' => $run->error, 'at' => $run->created_at->toIso8601String(),
            ]),
            'aiSettings' => [
                'timezone' => SystemSetting::get('ai_blog_timezone', config('app.timezone')),
                'start_time' => SystemSetting::get('ai_blog_start_time', '09:00'),
                'provider' => AiProviders::chain()[0] ?? null,
                'model' => ($provider = AiProviders::chain()[0] ?? null) ? AiProviders::model($provider) : null,

                'enabled' => filter_var(SystemSetting::get('ai_blog_enabled', false), FILTER_VALIDATE_BOOL),
                'keywords' => $keywordsRaw,
                'posts_per_day' => (int) SystemSetting::get('ai_blog_posts_per_day', 1),
                'tone' => (string) SystemSetting::get('ai_blog_tone', 'professional and engaging'),
                'language' => (string) SystemSetting::get('ai_blog_language', 'English'),
                'status' => (string) SystemSetting::get('ai_blog_status', 'scheduled'),
                'include_faq' => filter_var(SystemSetting::get('ai_blog_include_faq', true), FILTER_VALIDATE_BOOL),
                // Writing controls, shared with AI page generation (see AiWritingStyle).
                'writing_human_style' => AiWritingStyle::humanStyle(),
                'writing_banned_phrases' => AiWritingStyle::bannedText(),
                'writing_notes' => AiWritingStyle::notes(),
                'last_generated_at' => SystemSetting::get('ai_blog_last_generated_at'),
                'total_keywords' => count($parsedKeywords),
                'unused_keywords' => $unusedCount,
                'ai_available' => AiClient::enabled(),
            ],
            'stats' => [
                'total' => CmsPost::count(),
                'published' => CmsPost::where('is_published', true)->count(),
                'scheduled' => CmsPost::where('is_published', false)->where('published_at', '>', $now)->count(),
                'drafts' => CmsPost::where('is_published', false)->where(function ($q) use ($now) {
                    $q->whereNull('published_at')->orWhere('published_at', '<=', $now);
                })->count(),
                'ai_generated' => CmsPost::where('is_ai_generated', true)->count(),
            ],
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
        $validated['published_at'] = $validated['is_published'] ? now() : ($validated['published_at'] ?? null);

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
                'published_at' => $post->published_at?->toIso8601String(),
                'image' => $this->imageUrl($post),
                'url' => route('blog.show', $post->slug),
            ],
        ]);
    }

    public function update(Request $request, CmsPost $post)
    {
        $validated = $this->validated($request, $post);

        // Publishing now clears a future date; unpublishing clears the schedule unless supplied.
        $validated['published_at'] = $validated['is_published']
            ? ($post->is_published && $post->published_at?->isPast() ? $post->published_at : now())
            : ($validated['published_at'] ?? null);

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

    public function publishNow(CmsPost $post)
    {
        $post->update([
            'is_published' => true,
            'published_at' => now(),
        ]);

        return back()->with('success', "Post “{$post->title}” is now published live.");
    }

    public function updateAiSettings(Request $request)
    {
        $validated = $request->validate([
            'ai_blog_enabled' => 'nullable|boolean',
            'ai_blog_timezone' => 'required|timezone',
            'ai_blog_start_time' => 'required|date_format:H:i',
            'ai_blog_keywords' => 'nullable|string|max:20000',
            'ai_blog_posts_per_day' => 'required|integer|min:1|max:12',
            'ai_blog_tone' => 'required|string|max:100',
            'ai_blog_language' => 'required|string|max:50',
            'ai_blog_status' => 'required|in:scheduled,published,draft',
            'ai_blog_include_faq' => 'nullable|boolean',
            'ai_writing_human_style' => 'nullable|boolean',
            'ai_writing_banned_phrases' => 'nullable|string|max:10000',
            'ai_writing_notes' => 'nullable|string|max:2000',
        ]);

        if ($request->boolean('ai_blog_enabled')) {
            if (! AiClient::enabled()) {
                throw ValidationException::withMessages(['ai_blog_enabled' => 'Configure and enable an AI provider first.']);
            }
            if (! AiBlogService::parseKeywords($validated['ai_blog_keywords'] ?? '')) {
                throw ValidationException::withMessages(['ai_blog_keywords' => 'Add at least one topic before enabling automation.']);
            }
        }
        foreach (AiBlogService::parseKeywords($validated['ai_blog_keywords'] ?? '') as $keyword) {
            if (mb_strlen($keyword) > 255) {
                throw ValidationException::withMessages(['ai_blog_keywords' => 'Each topic must be 255 characters or fewer.']);
            }
        }
        SystemSetting::set('ai_blog_timezone', $validated['ai_blog_timezone']);
        SystemSetting::set('ai_blog_start_time', $validated['ai_blog_start_time']);
        SystemSetting::set('ai_blog_enabled', $request->boolean('ai_blog_enabled') ? '1' : '0');
        SystemSetting::set('ai_blog_keywords', $validated['ai_blog_keywords'] ?? '');
        SystemSetting::set('ai_blog_posts_per_day', (string) $validated['ai_blog_posts_per_day']);
        SystemSetting::set('ai_blog_tone', $validated['ai_blog_tone']);
        SystemSetting::set('ai_blog_language', $validated['ai_blog_language']);
        SystemSetting::set('ai_blog_status', $validated['ai_blog_status']);
        SystemSetting::set('ai_blog_include_faq', $request->boolean('ai_blog_include_faq') ? '1' : '0');
        AiWritingStyle::save($request->boolean('ai_writing_human_style'), (string) ($validated['ai_writing_banned_phrases'] ?? ''), (string) ($validated['ai_writing_notes'] ?? ''));

        return back()->with('success', 'AI Auto-Blogger settings saved.');
    }

    public function generateAiPost(Request $request, AiBlogService $blogService)
    {
        if (! AiClient::enabled()) {
            return back()->with('error', 'AI is not enabled. Configure an AI API key in Superadmin → AI first.');
        }

        $validated = $request->validate([
            'keyword' => 'required|string|min:2|max:255',
            'tone' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:50',
            'status' => 'nullable|in:scheduled,published,draft',
            'include_faq' => 'nullable|boolean',
            'mode' => 'nullable|in:queue,instant',
            'published_at' => 'nullable|date|after:now',
        ]);

        $options = [
            'status' => $validated['status'] ?? SystemSetting::get('ai_blog_status', 'scheduled'),
            'tone' => $validated['tone'] ?? SystemSetting::get('ai_blog_tone', 'professional and engaging'),
            'language' => $validated['language'] ?? SystemSetting::get('ai_blog_language', 'English'),
            'include_faq' => $request->boolean('include_faq', true),
            'author_id' => Auth::guard('superadmin')->id(),
            'published_at' => $validated['published_at'] ?? null,
        ];

        $mode = $validated['mode'] ?? 'queue';

        if ($mode === 'instant') {
            try {
                $post = $blogService->generateAndCreate($validated['keyword'], $options);

                $redirect = redirect()->route('superadmin.posts.edit', $post)->with('success', "AI blog post successfully generated for “{$validated['keyword']}”.");

                return $blogService->lastStyleIssues
                    ? $redirect->with('warning', 'Still contains banned wording: ' . implode(', ', $blogService->lastStyleIssues) . '. Please edit before publishing.')
                    : $redirect;
            } catch (\Throwable $e) {
                return back()->with('error', 'Generation failed. Check your provider connection and AI usage errors, then try again.');
            }
        }

        // Default: dispatch to background queue
        $blogService->queuePost($validated['keyword'], $options);

        return back()->with('success', "AI blog generation job queued for “{$validated['keyword']}”. It will appear in the posts list shortly.");
    }

    public function triggerSchedulerNow(AiBlogService $blogService)
    {
        if (! AiClient::enabled()) {
            return back()->with('error', 'AI is not enabled. Configure an AI API key in Superadmin → AI first.');
        }

        $keyword = $blogService->runScheduledGeneration();

        if ($keyword) {
            return back()->with('success', "Dispatched generation job for keyword: “{$keyword}”.");
        }

        return back()->with('info', 'No job queued. Check that automation is enabled, the current time slot is available, and unused topics remain.');
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
            'published_at' => 'nullable|date',
        ]);

        unset($validated['featured_image'], $validated['remove_image']);
        $validated['slug'] = ($validated['slug'] ?? null) ?: Str::slug($validated['title']);
        $validated['is_published'] = $request->boolean('is_published');

        if (! $validated['is_published'] && ! empty($validated['published_at'])) {
            $date = Carbon::parse($validated['published_at']);
            $unchanged = $post?->published_at?->equalTo($date) ?? false;
            if ($date->isPast() && ! $unchanged) {
                throw ValidationException::withMessages(['published_at' => 'Choose a future publish time, or clear it to save a draft.']);
            }
            $validated['published_at'] = $date->utc();
        }

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

        return Str::startsWith($p->featured_image, ['http://', 'https://']) ? $p->featured_image : asset('storage/'.$p->featured_image);
    }
}
