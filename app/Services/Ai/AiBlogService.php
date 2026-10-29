<?php

namespace App\Services\Ai;

use App\Jobs\GenerateAiBlogPostJob;
use App\Models\AiBlogRun;
use App\Models\CmsPost;
use App\Models\Superadmin;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AiBlogService
{
    /** Curated collection of high quality royalty-free business & invoicing Unsplash images */
    private const CURATED_IMAGES = [
        'https://images.unsplash.com/photo-1554224155-8d04cb21cd6c?auto=format&fit=crop&w=1200&q=80', // Invoicing & calculator
        'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&w=1200&q=80', // Financial analytics chart
        'https://images.unsplash.com/photo-1450133064473-71024230f91b?auto=format&fit=crop&w=1200&q=80', // Laptop contract & signing
        'https://images.unsplash.com/photo-1554224154-26032ffc0d07?auto=format&fit=crop&w=1200&q=80', // Tax & accounting papers
        'https://images.unsplash.com/photo-1507679799987-c73779587ccf?auto=format&fit=crop&w=1200&q=80', // Modern businessman workspace
        'https://images.unsplash.com/photo-1559526324-4b87b5e36e44?auto=format&fit=crop&w=1200&q=80', // Business growth & cashflow
        'https://images.unsplash.com/photo-1521791136064-7986c2920216?auto=format&fit=crop&w=1200&q=80', // Handshake & business agreement
        'https://images.unsplash.com/photo-1579621970563-ebec7560ff3e?auto=format&fit=crop&w=1200&q=80', // Budget and coins growth
    ];

    /** Banned phrases still present in the last generated post (see AiWritingStyle). */
    public array $lastStyleIssues = [];

    public function __construct(private AiClient $ai) {}

    /**
     * Generate structured SEO blog post data using the configured AI client.
     */
    public function generatePostData(string $keyword, array $options = []): array
    {
        if (! AiClient::enabled()) {
            throw new AiException('AI provider is not configured or disabled in Superadmin → AI.');
        }

        $tone = $options['tone'] ?? SystemSetting::get('ai_blog_tone', 'professional and engaging');
        $language = $options['language'] ?? SystemSetting::get('ai_blog_language', 'English');
        $includeFaq = $options['include_faq'] ?? filter_var(SystemSetting::get('ai_blog_include_faq', true), FILTER_VALIDATE_BOOL);

        $systemPrompt = implode("\n\n", [
            'You are an expert SEO content strategist, copywriter, and digital marketer specializing in B2B SaaS, small business finance, invoicing, accounting, and billing solutions.',
            'Your goal is to write a comprehensive, authoritative, engaging, and search-optimized blog post for the platform described below (an invoicing and payment collection application).',
            'Platform name: '.SystemSetting::get('site_name', 'Invoice Manager').'.',
            'Do not invent statistics, citations, customer stories, or legal claims. Avoid scripts, inline styles, and event handlers.',
            "Tone: {$tone}.",
            "Language: {$language}.",
            'Follow these strict editorial rules:',
            '1. Title: Compelling, click-worthy, includes the primary keyword naturally, between 45 and 65 characters.',
            '2. Slug: Clean, hyphen-separated, lowercase SEO URL slug matching the primary topic.',
            '3. Meta Description: Action-oriented snippet that includes the keyword, enticing searchers to click, 140-160 characters.',
            '4. Excerpt: A sharp 1-2 sentence preview for blog listing cards, 120-180 characters.',
            '5. Content format: Return complete, valid HTML inside the content field.',
            '   - Use <h2> for main topics and <h3> for subpoints.',
            '   - Write insightful paragraphs wrapped in <p> tags.',
            '   - Include bulleted lists (<ul><li>) for actionable steps or key takeaways.',
            '   - Include blockquotes (<blockquote>) for notable insights or expert tips.',
            '   - Bold (<strong>) important terms and statistics.',
            '   - NEVER use <h1> or <html>/<body> tags.',
            $includeFaq ? '   - Include an FAQ section near the end with 3-4 pertinent questions formatted with <h3> for questions and <p> for detailed answers.' : '',
            '   - Conclude with a practical summary and a natural call to action recommending streamlined invoicing practices.',
            AiWritingStyle::promptRules(),
        ]);

        $userPrompt = "Please generate a complete, high-ranking SEO blog post centered around the keyword/topic: \"{$keyword}\". Provide thorough depth, real value, practical examples, and compelling structure.";

        $schema = [
            'type' => 'object',
            'properties' => [
                'title' => [
                    'type' => 'string',
                    'description' => 'Compelling SEO title including the keyword, under 70 characters',
                ],
                'slug' => [
                    'type' => 'string',
                    'description' => 'URL-friendly slug (e.g., how-to-manage-invoicing-effectively)',
                ],
                'meta_title' => [
                    'type' => 'string',
                    'description' => 'SEO meta title under 65 characters',
                ],
                'meta_description' => [
                    'type' => 'string',
                    'description' => 'Search engine meta description between 140 and 160 characters',
                ],
                'excerpt' => [
                    'type' => 'string',
                    'description' => 'Crisp 1-2 sentence excerpt for blog cards, under 200 characters',
                ],
                'content' => [
                    'type' => 'string',
                    'description' => 'Comprehensive HTML formatted article with h2, h3, p, ul, li, blockquote',
                ],
                'suggested_tags' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                    'description' => '3 to 5 related SEO keywords or tags',
                ],
            ],
            'required' => ['title', 'slug', 'meta_title', 'meta_description', 'excerpt', 'content', 'suggested_tags'],
            'additionalProperties' => false,
        ];

        $data = $this->ai->json(
            company: null,
            feature: 'ai_blog_generator',
            system: $systemPrompt,
            prompt: $userPrompt,
            schema: $schema,
            effort: 'medium',
            maxTokens: 8000,
        );

        foreach (['title', 'content', 'slug', 'excerpt', 'meta_title', 'meta_description'] as $field) {
            if (! is_string($data[$field] ?? null) || trim($data[$field]) === '') {
                throw new AiException('The AI returned an incomplete article. Please try again.');
            }
        }
        $data['content'] = $this->cleanHtml($data['content']);
        if (trim(strip_tags($data['content'])) === '') {
            throw new AiException('The AI returned empty article content.');
        }

        // Banned words/phrases (Superadmin writing controls): one rewrite pass if any slipped through.
        [$data, $this->lastStyleIssues] = AiWritingStyle::enforce(
            $this->ai,
            'ai_blog_revise',
            $data,
            $schema,
            ['title', 'excerpt', 'meta_title', 'meta_description', 'content'],
            function (array $revised) {
                foreach (['title', 'content', 'slug', 'excerpt', 'meta_title', 'meta_description'] as $field) {
                    if (! is_string($revised[$field] ?? null) || trim($revised[$field]) === '') {
                        throw new AiException('Incomplete revision.');
                    }
                }
                $revised['content'] = $this->cleanHtml($revised['content']);

                return $revised;
            },
        );
        foreach (['title' => 255, 'excerpt' => 500, 'meta_title' => 255, 'meta_description' => 255] as $field => $limit) {
            $data[$field] = mb_substr(strip_tags($data[$field]), 0, $limit);
        }
        // Pick a relevant curated image based on keyword hash
        $imageIndex = abs(crc32($keyword)) % count(self::CURATED_IMAGES);
        $data['featured_image'] = self::CURATED_IMAGES[$imageIndex];
        $data['keyword'] = $keyword;

        return $data;
    }

    /**
     * Generate blog post and save to database (draft, scheduled, or published).
     */
    public function generateAndCreate(string $keyword, array $options = []): CmsPost
    {
        $data = $this->generatePostData($keyword, $options);

        $status = $options['status'] ?? SystemSetting::get('ai_blog_status', 'scheduled');
        $authorId = $options['author_id'] ?? Superadmin::query()->value('id');
        $postsPerDay = (int) ($options['posts_per_day'] ?? SystemSetting::get('ai_blog_posts_per_day', 1));

        $isPublished = false;
        $publishedAt = null;

        if ($status === 'published') {
            $isPublished = true;
            $publishedAt = now();
        } elseif ($status === 'scheduled') {
            $isPublished = false;
            $publishedAt = ! empty($options['published_at']) ? Carbon::parse($options['published_at'])->utc() : $this->calculateNextScheduleTime($postsPerDay);
        } else {
            // Draft
            $isPublished = false;
            $publishedAt = null;
        }

        // Ensure unique slug
        $slug = Str::limit(Str::slug($data['slug'] ?: $data['title']), 220, '') ?: 'article-'.Str::lower(Str::random(10));
        $baseSlug = $slug;
        $counter = 1;
        while (CmsPost::where('slug', $slug)->exists()) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        $post = CmsPost::create([
            'title' => $data['title'],
            'slug' => $slug,
            'content' => $data['content'],
            'featured_image' => $options['featured_image'] ?? $data['featured_image'],
            'excerpt' => $data['excerpt'],
            'meta_title' => $data['meta_title'] ?: $data['title'],
            'meta_description' => $data['meta_description'] ?: $data['excerpt'],
            'author_id' => $authorId,
            'is_published' => $isPublished,
            'is_ai_generated' => true,
            'target_keyword' => $keyword,
            'published_at' => $publishedAt,
        ]);

        $this->markKeywordAsUsed($keyword);
        SystemSetting::set('ai_blog_last_generated_at', now()->toIso8601String());

        return $post;
    }

    /**
     * Calculate optimal future publish time based on posts per day and current schedule.
     */
    public function calculateNextScheduleTime(int $postsPerDay = 1): Carbon
    {
        $postsPerDay = max(1, min(12, $postsPerDay));
        $timezone = SystemSetting::get('ai_blog_timezone', config('app.timezone'));
        $start = SystemSetting::get('ai_blog_start_time', '09:00');
        $latest = CmsPost::where('published_at', '>', now())->max('published_at');
        $after = $latest ? Carbon::parse($latest) : now();
        $day = $after->copy()->timezone($timezone)->startOfDay()->subDay();
        for ($d = 0; $d < 3; $d++) {
            for ($i = 0; $i < $postsPerDay; $i++) {
                $slot = $day->copy()->setTimeFromTimeString($start)->addMinutes((int) floor($i * 1440 / $postsPerDay));
                if ($slot->greaterThan($after)) {
                    return $slot->utc();
                }
            }
            $day->addDay();
        }

        return $after->copy()->addDay();
    }

    /** Reserve each daily slot before dispatch, including queued and failed work. */
    public function runScheduledGeneration(): ?string
    {
        if (! filter_var(SystemSetting::get('ai_blog_enabled', false), FILTER_VALIDATE_BOOL) || ! AiClient::enabled()) {
            return null;
        }

        return Cache::lock('ai-blog-scheduler', 30)->get(function () {
            $count = max(1, min(12, (int) SystemSetting::get('ai_blog_posts_per_day', 1)));
            $timezone = SystemSetting::get('ai_blog_timezone', config('app.timezone'));
            $local = now()->timezone($timezone);
            $start = $local->copy()->startOfDay()->setTimeFromTimeString(SystemSetting::get('ai_blog_start_time', '09:00'));
            if ($local->lessThan($start)) {
                $start->subDay();
            }
            $i = min($count - 1, (int) floor($start->diffInMinutes($local) / (1440 / $count)));
            $slot = $start->format('Y-m-d').'-'.$i;
            if (AiBlogRun::where('slot', $slot)->exists()) {
                return null;
            }
            $keyword = $this->getNextKeyword();
            if (! $keyword) {
                return null;
            }
            $this->queuePost($keyword, [
                'status' => SystemSetting::get('ai_blog_status', 'scheduled'),
                'tone' => SystemSetting::get('ai_blog_tone', 'professional and engaging'),
                'language' => SystemSetting::get('ai_blog_language', 'English'),
                'include_faq' => filter_var(SystemSetting::get('ai_blog_include_faq', true), FILTER_VALIDATE_BOOL),
                'posts_per_day' => $count,
            ], $slot);

            return $keyword;
        });
    }

    public function queuePost(string $keyword, array $options, ?string $slot = null): AiBlogRun
    {
        $run = AiBlogRun::create(['keyword' => $keyword, 'options' => $options, 'slot' => $slot]);
        try {
            GenerateAiBlogPostJob::dispatch($keyword, $options, $run->id);
        } catch (\Throwable $e) {
            $run->update(['status' => 'failed', 'error' => 'Could not queue generation. Check the queue connection.']);
            throw $e;
        }

        return $run;
    }

    /** Keep generated HTML limited to article formatting and safe links. */
    public static function cleanHtml(string $html): string
    {
        $doc = new \DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        foreach (iterator_to_array($doc->getElementsByTagName('*')) as $node) {
            if (in_array(strtolower($node->nodeName), ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'form'], true)) {
                $node->parentNode?->removeChild($node);

                continue;
            }
            foreach (iterator_to_array($node->attributes) as $attr) {
                $safeLink = $node->nodeName === 'a' && $attr->name === 'href' && preg_match('~^(https?://|/[^/]|#|mailto:)~i', trim($attr->value));
                if (! $safeLink) {
                    $node->removeAttribute($attr->name);
                }
            }
        }
        $body = $doc->getElementsByTagName('body')->item(0);
        $result = '';
        foreach ($body->childNodes as $child) {
            $result .= $doc->saveHTML($child);
        }

        return strip_tags($result, '<p><h2><h3><h4><ul><ol><li><blockquote><strong><b><em><i><a><br><table><thead><tbody><tr><th><td><pre><code>');
    }

    /**
     * Check and publish any posts whose scheduled published_at date has arrived.
     */
    public function publishDuePosts(): int
    {
        return CmsPost::where('is_published', false)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['is_published' => true]);
    }

    /**
     * Parse raw keywords string into an array of trimmed lines.
     */
    public static function parseKeywords(?string $raw): array
    {
        if (blank($raw)) {
            return [];
        }

        $lines = preg_split('/[\r\n]+/', (string) $raw);
        $keywords = [];

        foreach ($lines as $line) {
            $trimmed = trim((string) $line, " \t\n\r\0\x0B,-*•");
            if ($trimmed !== '') {
                $keywords[] = $trimmed;
            }
        }

        return array_values(array_unique($keywords));
    }

    /**
     * Get the next unused keyword from the configured list.
     */
    public function getNextKeyword(): ?string
    {
        $raw = SystemSetting::get('ai_blog_keywords', '');
        $keywords = self::parseKeywords($raw);

        if (empty($keywords)) {
            return null;
        }

        $used = array_unique([...$this->getUsedKeywords(), ...AiBlogRun::whereIn('status', ['queued', 'running'])->pluck('keyword')->map(fn ($kw) => mb_strtolower(trim($kw)))->all()]);

        foreach ($keywords as $kw) {
            if (! in_array(mb_strtolower($kw), $used, true)) {
                return $kw;
            }
        }

        return null; // Exhaustion pauses generation until new topics are supplied.
    }

    public function getUsedKeywords(): array
    {
        $raw = SystemSetting::get('ai_blog_used_keywords', '[]');
        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function markKeywordAsUsed(string $keyword): void
    {
        $used = $this->getUsedKeywords();
        $normalized = mb_strtolower(trim($keyword));

        if (! in_array($normalized, $used, true)) {
            $used[] = $normalized;
            SystemSetting::set('ai_blog_used_keywords', json_encode($used));
        }
    }

    public function resetUsedKeywords(): void
    {
        SystemSetting::set('ai_blog_used_keywords', '[]');
    }
}
